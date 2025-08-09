
<?php
require __DIR__.'/../bootstrap.php';
$useMock = \App\Core\Env::get('DB_CONNECTION') === 'none' && \App\Core\Env::get('INSTALLER_MOCK') === 'true';
if ($useMock) {
    // PRODUCTION NOTE:
    // To run the real installer, set DB_CONNECTION and INSTALLER_MOCK=false in .env.
    \App\Installer\InstallerMock::run();
} else {
    \App\Installer\Installer::run();
}
?>
