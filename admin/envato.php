<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';

use App\Support\Settings;
use App\Services\LicenseValidator;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$saved = false;
$testResult = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_settings':
                $enabled = isset($_POST['purchase_validation_enabled']);
                $codeEnabled = isset($_POST['purchase_code_enabled']);
                $codeRequired = isset($_POST['purchase_code_required']);
                $token = trim($_POST['envato_token'] ?? '');
                $allowedIds = trim($_POST['allowed_item_ids'] ?? '');
                
                Settings::set('purchase_validation_enabled', $enabled);
                Settings::set('purchase_code_enabled', $codeEnabled);
                Settings::set('purchase_code_required', $codeRequired);
                Settings::set('envato_allowed_item_ids', $allowedIds);
                
                if ($token !== '') {
                    Settings::setSecure('envato_personal_token', $token);
                }
                
                $saved = true;
                break;
                
            case 'test_connection':
                $testResult = LicenseValidator::testConnection();
                break;
                
            case 'remove_token':
                Settings::removeSecure('envato_personal_token');
                $saved = true;
                break;
        }
    }
}

// Get current settings
$enabled = Settings::get('purchase_validation_enabled', false);
$codeEnabled = Settings::get('purchase_code_enabled', false);  
$codeRequired = Settings::get('purchase_code_required', false);
$allowedIds = Settings::get('envato_allowed_item_ids', '');
$hasToken = Settings::getSecure('envato_personal_token', '') !== '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Envato Settings — ReplyPilot-AI</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .settings-card{border:1px solid #eee;border-radius:8px;padding:20px;margin:16px 0;background:#fff}
    .settings-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
    .form-group{margin:15px 0}.form-group label{display:block;margin-bottom:5px;font-weight:600}
    .form-group input,.form-group textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:4px}
    .form-group small{color:#666;font-size:12px;margin-top:5px;display:block}
    .status-indicator{padding:8px 12px;border-radius:4px;font-size:14px;margin:10px 0}
    .status-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
    .status-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
    .status-warning{background:#fff3cd;color:#856404;border:1px solid #ffeaa7}
    .btn-group{display:flex;gap:10px;margin-top:15px}
    .token-status{background:#f8f9fa;padding:15px;border-radius:6px;margin:10px 0}
  </style>
</head>
<body>
  <div class="container">
    <div class="settings-header">
      <h1>🛡️ Envato Integration</h1>
      <div>
        <a class="btn" href="categories.php">Category Rules</a>
        <a class="btn" href="index.php">← Back to Admin</a>
      </div>
    </div>

    <?php if ($saved): ?>
      <div class="status-indicator status-success">✅ Settings saved successfully!</div>
    <?php endif; ?>

    <?php if ($testResult): ?>
      <div class="status-indicator <?= $testResult[0] ? 'status-success' : 'status-error' ?>">
        <?= $testResult[0] ? '✅' : '❌' ?> <?= htmlspecialchars($testResult[1]) ?>
      </div>
    <?php endif; ?>

    <div class="settings-card">
      <h3>Purchase Code Validation</h3>
      <p>Configure Envato Market purchase code validation for your products.</p>
      
      <form method="post">
        <input type="hidden" name="action" value="save_settings">
        
        <div class="form-group">
          <label>
            <input type="checkbox" name="purchase_validation_enabled" <?= $enabled ? 'checked' : '' ?>>
            Enable Envato purchase validation
          </label>
          <small>When enabled, purchase codes will be validated against Envato Market API</small>
        </div>

        <div class="form-group">
          <label>
            <input type="checkbox" name="purchase_code_enabled" <?= $codeEnabled ? 'checked' : '' ?>>
            Show purchase code field in contact form
          </label>
          <small>Display the purchase code input field to users</small>
        </div>

        <div class="form-group">
          <label>
            <input type="checkbox" name="purchase_code_required" <?= $codeRequired ? 'checked' : '' ?>>
            Require purchase code
          </label>
          <small>Make the purchase code field mandatory (only works if field is enabled)</small>
        </div>

        <div class="form-group">
          <label for="envato_token">Envato Personal Token</label>
          <input type="password" id="envato_token" name="envato_token" placeholder="Enter your Envato personal token">
          <small>
            Get your token from <a href="https://build.envato.com/create-tokens/" target="_blank">Envato API</a>. 
            Required permissions: View and search Envato sites
          </small>
          
          <?php if ($hasToken): ?>
            <div class="token-status">
              ✅ Token is configured
              <div style="margin-top:10px">
                <button type="submit" name="action" value="test_connection" class="btn">Test Connection</button>
                <button type="submit" name="action" value="remove_token" class="btn" 
                        onclick="return confirm('Remove Envato token?')" style="background:#dc3545">Remove Token</button>
              </div>
            </div>
          <?php else: ?>
            <div class="token-status">
              ⚠️ No token configured - purchase validation will not work
            </div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="allowed_item_ids">Allowed Item IDs (Optional)</label>
          <textarea id="allowed_item_ids" name="allowed_item_ids" rows="3" 
                    placeholder="12345, 67890, 11111"><?= htmlspecialchars($allowedIds) ?></textarea>
          <small>
            Comma-separated list of Envato item IDs to accept. Leave empty to allow all items.
            You can find item IDs in your Envato dashboard.
          </small>
        </div>

        <div class="btn-group">
          <button type="submit" class="btn primary">💾 Save Settings</button>
        </div>
      </form>
    </div>

    <div class="settings-card">
      <h3>📚 How to Setup</h3>
      <ol style="line-height:1.8">
        <li><strong>Get Envato Personal Token:</strong> Visit <a href="https://build.envato.com/create-tokens/" target="_blank">Envato API</a> and create a token with "View and search Envato sites" permission</li>
        <li><strong>Enter Token:</strong> Paste the token in the field above and save</li>
        <li><strong>Test Connection:</strong> Use the "Test Connection" button to verify your token works</li>
        <li><strong>Configure Item IDs:</strong> Optionally restrict validation to specific products by adding item IDs</li>
        <li><strong>Enable Validation:</strong> Check the boxes to enable purchase code validation and display</li>
      </ol>
    </div>

  </div>
</body>
</html>
