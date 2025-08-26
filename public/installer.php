<?php
/**
* SPDX-License-Identifier: GPL-3.0-or-later
* Copyright (c) 2025 Md Mazharul Islam / Fluent-Themes
*/
require __DIR__.'/../bootstrap.php';
if (!defined('INSTALL_FALLBACK_TOKEN')) define('INSTALL_FALLBACK_TOKEN','setup123');
\App\Installer\Installer::run();
?>
