<?php
use PHPUnit\Framework\TestCase;

final class InstallerTest extends TestCase
{
    public function testInstallerMock(): void
    {
        putenv('INSTALLER_MOCK=true');
        $_ENV['INSTALLER_MOCK'] = 'true';
        putenv('INSTALL_TOKEN=');
        $_ENV['INSTALL_TOKEN'] = '';
        $_GET['token'] = '';

        // GET request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        include __DIR__ . '/../public/installer.php';
        $output = ob_get_clean();
        $this->assertStringContainsString('<form', $output);

        // POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'db_host' => 'localhost',
            'db_name' => 'testdb',
            'db_user' => 'user',
            'db_pass' => 'pass'
        ];
        ob_start();
        include __DIR__ . '/../public/installer.php';
        $postOutput = ob_get_clean();
        $this->assertStringContainsString('Env written', $postOutput);
        $this->assertStringContainsString('Done!', $postOutput);
        $log = file_get_contents(__DIR__ . '/../storage/logs/app.log');
        $this->assertStringContainsString('Mock installer ran with DB name: testdb', $log);
    }
}
