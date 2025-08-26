<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';
use App\Support\Settings;
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$pv  = Settings::get('purchase_validation_enabled', false);
$pce = Settings::get('purchase_code_enabled', false);
$pcr = Settings::get('purchase_code_required', false);
$saved = isset($_GET['saved']) && $_GET['saved'] == '1';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Settings — ReplyPilot-AI</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .toast{position:fixed;right:16px;top:16px;background:#0b875b;color:#fff;padding:10px 14px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.15);display:none;z-index:9999}
    .toast.show{display:block}
    .settings-card{border:1px solid #eee;border-radius:8px;padding:12px;margin:16px 0;background:#fff}
    .settings-header{display:flex;align-items:center;justify-content:space-between}
  </style>
</head>
<body>
  <div class="container">
    <div class="settings-header">
      <h1>Settings</h1>
      <p><a class="btn" href="./">← Back to Tickets</a></p>
    </div>
    <div class="settings-card">
      <form method="post" action="update_settings.php">
        <p><label><input type="checkbox" name="purchase_validation_enabled" <?php echo $pv ? 'checked' : ''; ?>> Purchase validation (Envato)</label></p>
        <p><label><input type="checkbox" name="purchase_code_enabled" <?php echo $pce ? 'checked' : ''; ?>> Show “Purchase code” field</label></p>
        <p><label><input type="checkbox" name="purchase_code_required" <?php echo $pcr ? 'checked' : ''; ?>> Require purchase code</label></p>
        <p><button class="btn primary">Save</button></p>
      </form>
    </div>
  </div>
  <div id="toast" class="toast">Saved</div>
  <script>
    (function(){
      var saved = <?php echo $saved ? 'true' : 'false'; ?>;
      if (saved) {
        var t = document.getElementById('toast');
        if (t){ t.classList.add('show'); setTimeout(function(){ t.classList.remove('show'); }, 2000); }
      }
    })();
  </script>
</body>
</html>
