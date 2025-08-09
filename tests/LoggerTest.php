<?php
use PHPUnit\Framework\TestCase;
use App\Support\Logger;

final class LoggerTest extends TestCase
{
    public function testLoggerWritesEntries(): void
    {
        $logPath = __DIR__ . '/../storage/logs/app.log';
        if (file_exists($logPath)) {
            unlink($logPath);
        }
        $logger = Logger::create();
        $logger->warning('Test warning');
        $logger->error('Test error');
        $this->assertFileExists($logPath);
        $log = file_get_contents($logPath);
        $this->assertStringContainsString('Test warning', $log);
        $this->assertStringContainsString('Test error', $log);
    }
}
