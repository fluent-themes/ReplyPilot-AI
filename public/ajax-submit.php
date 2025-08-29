<?php
// Minimal AJAX controller for AI reply + ticket creation
ob_start(); // Start output buffering to prevent headers already sent errors
require __DIR__ . '/../bootstrap.php';

use App\Core\Env;
use App\Core\Request;
use App\Services\OpenAIHandler;
use App\Services\OpenAIHandlerMock;
use App\Support\Settings;
use App\Support\CategoryRules;
use App\Repository\SubmissionRepository;
use App\Repository\SubmissionRepositoryMock;
use App\Support\Mailer;
use App\Support\MailerMock;
use App\Helpers\ModeHelper;
use App\Helpers\TicketHelper;
use App\Support\Analytics;
use App\Support\ResponseCache;
use App\Support\PromptOptimizer;
use App\Factories\AIProviderFactory;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
ob_clean(); // Clear any output that may have occurred during bootstrap
header('Content-Type: application/json; charset=utf-8');
$request_id = bin2hex(random_bytes(6));
try {
    $logger = $GLOBALS['container']['logger'];
    $db     = $GLOBALS['container']['db_factory'](); // Get DB connection from factory
    
    // Check if database is available
    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => [
                'id' => 'database_unavailable',
                'message' => 'Database connection unavailable. Please complete installation.',
                'hint' => 'Visit /?page=install&token=setup123 to install'
            ],
            'request_id' => $request_id
        ]);
        exit;
    }
    // Rate limit (session-based): max 6 requests / 60s
    $now = time();
    if (!isset($_SESSION['ajax_times'])) { $_SESSION['ajax_times'] = []; }
    $_SESSION['ajax_times'] = array_values(array_filter($_SESSION['ajax_times'], function($t) use ($now){ return ($now - $t) < 60; }));
    if (count($_SESSION['ajax_times']) >= 6) {
        error_log('AJAX rate limit hit id='.$request_id.' ip='.($_SERVER['REMOTE_ADDR'] ?? ''));
        http_response_code(429);
        header('X-RateLimit-Limit: 6');
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . (time() + 60));
        header('Retry-After: 60');
        echo json_encode([
            'success' => false,
            'error' => [
                'id' => 'rate_limit_exceeded',
                'message' => 'Rate limit exceeded. Please wait and try again.',
                'hint' => 'Maximum 6 requests per minute allowed'
            ],
            'request_id' => $request_id
        ]);
        exit;
    }
    $_SESSION['ajax_times'][] = $now;

    // Validate CSRF token
    $csrf_token = Request::input('csrf_token', '');
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => [
                'id' => 'csrf_validation_failed',
                'message' => 'Invalid request token. Please refresh the page and try again.',
                'hint' => 'Security token expired or invalid'
            ],
            'request_id' => $request_id
        ]);
        exit;
    }

    $name  = trim(Request::input('name'));
    $email = trim(Request::input('email'));
    $msg   = trim(Request::input('message'));
    $tone  = trim(Request::input('tone', 'friendly'));
    $productName = trim(Request::input('product_name', ''));
    
    // Enhanced input validation
    $allowedTones = ['friendly', 'professional', 'casual', 'formal'];
    if (!in_array($tone, $allowedTones)) {
        $tone = 'friendly'; // Default to safe value
    }

    $purchaseEnabled  = Settings::get('purchase_code_enabled', false);
    $purchaseRequired = Settings::get('purchase_code_required', false);
    $purchase = $purchaseEnabled ? trim(Request::input('purchase_code', '')) : '';
    $errors = [];
    
    // Enhanced validation with length limits and character checks
    if ($name === '') {
        $errors[] = 'Name is required';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Name must be 100 characters or less';
    } elseif (!preg_match('/^[\p{L}\p{M}\p{N}\s\.\'\-]+$/u', $name)) {
        $errors[] = 'Name contains invalid characters';
    }
    
    if ($email === '') {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    } elseif (strlen($email) > 254) {
        $errors[] = 'Email address is too long';
    }
    
    if ($msg === '') {
        $errors[] = 'Message is required';
    } elseif (strlen($msg) > 5000) {
        $errors[] = 'Message must be 5000 characters or less';
    } elseif (strlen($msg) < 10) {
        $errors[] = 'Message must be at least 10 characters';
    }
    
    if (strlen($productName) > 200) {
        $errors[] = 'Product name must be 200 characters or less';
    }
    if ($purchaseEnabled) {
        if ($purchaseRequired && $purchase === '') { 
            $errors[] = 'Purchase code is required'; 
        } elseif ($purchase !== '') {
            if (strlen($purchase) > 100) {
                $errors[] = 'Purchase code is too long';
            } else {
                [$valid, $productNameFromCode, $validationError] = \App\Services\LicenseValidator::validate($purchase);
                if (!$valid) { $errors[] = $validationError ?: 'Invalid purchase code'; }
                else { if ($productName === '' && !empty($productNameFromCode)) $productName = $productNameFromCode; }
            }
        }
    }
    // Check if AI auto-reply is enabled (real OpenAI key present and not in mock mode)
    $apiKey = trim((string)Env::get('OPENAI_API_KEY',''));
    $ajaxMode = ($apiKey !== '' && !ModeHelper::isMock());
    if (!$ajaxMode) {
        echo json_encode([
            'success' => false,
            'error' => [
                'id' => 'ajax_mode_disabled',
                'message' => 'AJAX mode disabled: OpenAI key not set or in mock mode',
                'hint' => 'Configure OpenAI API key and disable mock mode'
            ],
            'request_id' => $request_id
        ]);
        exit;
    }
    if ($errors) {
        echo json_encode([
            'success' => false,
            'error' => [
                'id' => 'validation_failed',
                'message' => implode('. ', $errors),
                'hint' => 'Please check all required fields'
            ],
            'request_id' => $request_id
        ]);
        exit;
    }

    // Initialize analytics and cache
    $analytics = new Analytics();
    $cache = new ResponseCache();
    $optimizer = new PromptOptimizer();
    $startTime = microtime(true);
    
    $contextProductName = $productName !== '' ? $productName : 'Your Product';
    
    // Try cache first
    $cachedResponse = $cache->get($msg, $tone, $contextProductName);
    if ($cachedResponse) {
        $ai = $cachedResponse;
        $analytics->recordAIQuery([
            'provider' => 'cache',
            'model' => 'cached',
            'message_length' => strlen($msg),
            'response_length' => strlen($ai['reply']),
            'tokens_used' => 0,
            'response_time' => microtime(true) - $startTime,
            'category' => $ai['category'],
            'confidence' => $ai['confidence'],
            'cached' => true,
            'tone' => $tone,
            'product_name' => $contextProductName,
            'success' => true
        ]);
    } else {
        // Use AI provider factory
        try {
            $aiProvider = AIProviderFactory::create();
            $prompt = $aiProvider->buildPrompt($msg, $tone, $contextProductName);
            
            // Optimize prompt if enabled
            if (Settings::get('prompt_optimization_enabled', true)) {
                $optimized = $optimizer->optimize($prompt, [
                    'tone' => $tone,
                    'category_hint' => null
                ]);
                $prompt = $optimized['optimized_prompt'];
            }
            
            $ai = $aiProvider->query($prompt);
            
            // Cache the response
            $cache->set($msg, $tone, $contextProductName, $ai);
            
            // Record analytics
            $analytics->recordAIQuery([
                'provider' => $aiProvider->getProviderInfo()['name'] ?? 'unknown',
                'model' => $aiProvider->getProviderInfo()['model'] ?? 'unknown',
                'message_length' => strlen($msg),
                'response_length' => strlen($ai['reply']),
                'tokens_used' => $ai['tokens_used'] ?? 0,
                'response_time' => microtime(true) - $startTime,
                'category' => $ai['category'],
                'confidence' => $ai['confidence'] ?? 0.0,
                'cached' => false,
                'tone' => $tone,
                'product_name' => $contextProductName,
                'success' => true
            ]);
            
        } catch (\Throwable $e) {
            // Record failed analytics
            $analytics->recordAIQuery([
                'provider' => 'unknown',
                'model' => 'unknown',
                'message_length' => strlen($msg),
                'response_length' => 0,
                'tokens_used' => 0,
                'response_time' => microtime(true) - $startTime,
                'category' => 'Support',
                'confidence' => 0.0,
                'cached' => false,
                'tone' => $tone,
                'product_name' => $contextProductName,
                'success' => false,
                'error_message' => $e->getMessage()
            ]);
            
            error_log('AI provider exception id='.$request_id.' msg='.$e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => [
                    'id' => 'ai_service_error',
                    'message' => 'AI service error. Please try again later.',
                    'hint' => 'The AI provider is temporarily unavailable'
                ],
                'request_id' => $request_id
            ]);
            exit;
        }
    }
    $computedCategory = CategoryRules::categorize($productName ?? '', $msg, $ai['reply'] ?? '');
    if (!$computedCategory) { $computedCategory = $ai['category'] ?? 'General'; }

    $repo = ModeHelper::isMock() ? new SubmissionRepositoryMock($db) : new SubmissionRepository($db);
    $ref = $repo->save([
        'name' => $name,
        'email' => $email,
        'message' => $msg,
        'tone' => $tone,
        'purchase_code' => $purchase,
        'product_name' => $productName ?? '',
        'category' => $computedCategory,
        'ai_reply' => $ai['reply']
    ]);
    
    // Allow this session to access the created ticket
    TicketHelper::allowAccess($ref);

    $mailer = ModeHelper::isMock() ? new MailerMock() : new Mailer();
    $mailer->send($email, 'Your Reply from AI', $ai['reply']);
    
    // Check if admin notifications are enabled
    $adminNotificationsEnabled = Settings::get('admin_notifications_enabled', true);
    $admin = trim((string) Env::get('ADMIN_EMAIL', ''));
    
    if ($adminNotificationsEnabled && $admin !== '' && filter_var($admin, FILTER_VALIDATE_EMAIL)) {
        $subjectAdmin = 'New support message from ' . $name . ' <' . $email . '>';
        $mailer->send($admin, $subjectAdmin, $ai['reply']);
    } elseif ($adminNotificationsEnabled && $admin !== '') {
        error_log('Invalid ADMIN_EMAIL format: ' . substr($admin, 0, strpos($admin, '@') + 1) . '[...]');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $ticketUrl = $scheme . '://' . $host . '/?page=ticket&ref=' . rawurlencode((string)$ref);
    echo json_encode(['success'=>true,'ai_reply'=>$ai['reply'],'ticket_url'=>$ticketUrl,'request_id'=>$request_id]);
    exit;
} catch (\Throwable $e) {
    ob_clean(); // Clear any output before sending error response
    error_log('AJAX fatal id='.$request_id.' msg='.$e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => [
            'id' => 'server_error',
            'message' => 'Server error',
            'hint' => 'Please try again or contact support'
        ],
        'request_id' => $request_id
    ]);
    exit;
}
