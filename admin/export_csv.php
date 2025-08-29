<?php
require __DIR__ . '/guard.php';

try {
    // CSRF Protection for export
    $token = $_GET['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
    
    // Clean any output buffer before headers
    ob_clean();
    
    $db = $GLOBALS['container']['db'];
    if (!$db) {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }
    $stmt = $db->query('SELECT id,name,email,message,tone,purchase_code,product_name,category,ai_reply,created_at FROM submissions ORDER BY id DESC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prevent premature output before headers
    if (headers_sent()) {
        throw new Exception('Headers already sent, cannot export CSV');
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="submissions.csv"');
    // Disable caching
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fputcsv($out, array_keys($rows[0] ?? [
        'id','name','email','message','tone','purchase_code','product_name','category','ai_reply','created_at'
    ]));

    foreach ($rows as $r) {
        // Normalize newlines to avoid CSV breakage
        foreach (['message','ai_reply'] as $k) {
            if (isset($r[$k])) {
                $r[$k] = preg_replace("/\r\n|\r|\n/", " ", (string) $r[$k]);
            }
        }
        fputcsv($out, $r);
    }
    fclose($out);
    exit; // Exit after streaming CSV

} catch (\Throwable $e) {
    // If headers not sent yet, send error response
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false, 
            'error' => [
                'id' => 'export_failed',
                'message' => 'Failed to export CSV: ' . $e->getMessage(),
                'hint' => 'Please check database connection and try again'
            ],
            'request_id' => bin2hex(random_bytes(6))
        ]);
        exit;
    }
    // If headers already sent, log error
    $logger = $GLOBALS['container']['logger'];
    $logger->error('CSV export error: ' . $e->getMessage());
}
