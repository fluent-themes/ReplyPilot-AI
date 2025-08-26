<?php
namespace App\Repository;

class SubmissionRepositoryMock {
    public function __construct($pdo = null)
    {
        // no-op
    }

    public function save(array $data)
    {
        // Generate a stable short ref without persistence (mock mode)
        return substr(sha1(json_encode($data) . microtime(true)), 0, 10);
    }
    
    public function findByRef(string $ref): ?array {
        // Mock mode: return sample ticket data
        return [
            'id' => $ref,
            'name' => 'Mock User',
            'email' => 'mock@example.com',
            'message' => 'This is a mock support ticket for testing purposes.',
            'tone' => 'friendly',
            'purchase_code' => '',
            'product_name' => 'Mock Product',
            'category' => 'Mock Category',
            'ai_reply' => 'This is a mock AI reply for testing the ticket viewing functionality.',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
}
