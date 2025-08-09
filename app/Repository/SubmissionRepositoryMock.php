<?php
namespace App\Repository;

class SubmissionRepositoryMock {
    public function __construct($pdo = null)
    {
        // no-op
    }

    public function save(array $data): void
    {
        // intentionally left blank in mock mode
    }
}
