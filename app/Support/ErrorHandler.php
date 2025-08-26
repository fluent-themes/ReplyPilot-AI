<?php namespace App\Support;

class ErrorHandler {
    private static $logger = null;
    private static $initialized = false;
    
    public static function initialize() {
        if (self::$initialized) return;
        
        // Use existing logger if available
        if (isset($GLOBALS['container']['logger'])) {
            self::$logger = $GLOBALS['container']['logger'];
        }
        
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
        
        self::$initialized = true;
    }
    
    public static function handleError($severity, $message, $file, $line) {
        $correlationId = self::getCorrelationId();
        $route = self::getCurrentRoute();
        $paramKeys = self::getRequestParamKeys();
        
        $context = [
            'type' => 'error',
            'severity' => $severity,
            'file' => $file,
            'line' => $line,
            'route' => $route,
            'param_keys' => $paramKeys,
            'correlation_id' => $correlationId
        ];
        
        self::log('PHP Error: ' . $message, $context);
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    public static function handleException(\Throwable $exception) {
        $correlationId = self::getCorrelationId();
        $route = self::getCurrentRoute();
        $paramKeys = self::getRequestParamKeys();
        
        $context = [
            'type' => 'exception',
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'route' => $route,
            'param_keys' => $paramKeys,
            'correlation_id' => $correlationId
        ];
        
        self::log('Uncaught Exception: ' . $exception->getMessage(), $context);
        
        // For AJAX requests, return JSON error envelope
        if (self::isAjaxRequest()) {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'error' => [
                        'id' => 'server_error',
                        'message' => 'Server error occurred',
                        'hint' => 'Please try again or contact support'
                    ],
                    'request_id' => $correlationId
                ]);
                exit;
            }
        } else {
            // For non-AJAX requests, show generic error page
            if (!headers_sent()) {
                http_response_code(500);
                echo '<h1>Server Error</h1><p>An error occurred. Please try again.</p>';
                exit;
            }
        }
    }
    
    public static function handleShutdown() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $correlationId = self::getCorrelationId();
            $route = self::getCurrentRoute();
            $paramKeys = self::getRequestParamKeys();
            
            $context = [
                'type' => 'fatal_error',
                'file' => $error['file'],
                'line' => $error['line'],
                'route' => $route,
                'param_keys' => $paramKeys,
                'correlation_id' => $correlationId
            ];
            
            self::log('Fatal Error: ' . $error['message'], $context);
            
            // For AJAX requests, return JSON error envelope
            if (self::isAjaxRequest() && !headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'error' => [
                        'id' => 'fatal_error',
                        'message' => 'Fatal server error',
                        'hint' => 'Please try again or contact support'
                    ],
                    'request_id' => $correlationId
                ]);
            }
        }
    }
    
    private static function getCurrentRoute() {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        $route = $method . ' ' . $script;
        if ($query) {
            // Only log query parameter keys, not values
            parse_str($query, $params);
            $route .= '?' . implode('&', array_keys($params));
        }
        
        return $route;
    }
    
    private static function getRequestParamKeys() {
        $keys = [];
        
        // GET parameters
        if (!empty($_GET)) {
            $keys['GET'] = array_keys($_GET);
        }
        
        // POST parameters (keys only, no values for security)
        if (!empty($_POST)) {
            $keys['POST'] = array_keys($_POST);
        }
        
        return $keys;
    }
    
    private static function getCorrelationId() {
        static $id = null;
        if ($id === null) {
            $id = bin2hex(random_bytes(8));
        }
        return $id;
    }
    
    private static function isAjaxRequest() {
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            !empty($_SERVER['CONTENT_TYPE']) && 
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
        ) || (
            !empty($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        );
    }
    
    private static function log($message, $context = []) {
        if (self::$logger) {
            // Use existing logger
            self::$logger->error($message, $context);
        } else {
            // Fallback to file logging
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => $message,
                'context' => $context
            ];
            
            $logDir = __DIR__ . '/../../storage/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            
            $logFile = $logDir . '/error-' . date('Y-m-d') . '.log';
            @error_log(json_encode($logEntry) . "\n", 3, $logFile);
        }
    }
}
