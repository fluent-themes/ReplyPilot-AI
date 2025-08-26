<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';

use App\Registry\ProviderRegistry;

$health = ProviderRegistry::getHealthCheck();
$config = ProviderRegistry::getConfiguration();
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>System Health — ReplyPilot-AI</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .health-overview{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin:20px 0}
    .health-card{background:#fff;border:1px solid #eee;border-radius:8px;padding:20px}
    .health-status{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:16px;font-size:12px;font-weight:bold;margin-bottom:15px}
    .status-healthy{background:#d4edda;color:#155724}
    .status-degraded{background:#fff3cd;color:#856404}
    .status-unhealthy{background:#f8d7da;color:#721c24}
    .provider-list{list-style:none;padding:0;margin:0}
    .provider-item{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f0f0f0}
    .provider-item:last-child{border-bottom:none}
    .provider-name{font-weight:600}
    .provider-status{padding:2px 6px;border-radius:8px;font-size:10px;font-weight:bold}
    .btn-refresh{background:#007cba;color:white;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;font-size:12px}
    .btn-refresh:hover{background:#005a87}
    .issue-list{background:#f8d7da;border:1px solid #f5c6cb;border-radius:6px;padding:15px;margin:15px 0}
    .issue-item{color:#721c24;margin:5px 0;font-size:14px}
    .config-summary{background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;padding:15px;margin:15px 0}
    .config-item{display:flex;justify-content:space-between;margin:5px 0;font-size:14px}
    .config-label{font-weight:600;color:#495057}
    .config-value{color:#6c757d}
  </style>
</head>
<body>
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h1>🔍 System Health</h1>
      <div>
        <button class="btn-refresh" onclick="location.reload()">🔄 Refresh</button>
        <a class="btn" href="index.php">← Back to Dashboard</a>
      </div>
    </div>

    <!-- Overall Health Status -->
    <div class="health-card">
      <div class="health-status status-<?= $health['overall_status'] ?>">
        <?php if ($health['overall_status'] === 'healthy'): ?>
          ✅ System Healthy
        <?php elseif ($health['overall_status'] === 'degraded'): ?>
          ⚠️ System Degraded
        <?php else: ?>
          ❌ System Unhealthy
        <?php endif; ?>
      </div>
      
      <h3>Overall System Status</h3>
      <p>Last checked: <strong><?= date('Y-m-d H:i:s') ?></strong></p>
      
      <?php if (!empty($health['issues'])): ?>
        <div class="issue-list">
          <h4>🚨 Issues Detected:</h4>
          <?php foreach ($health['issues'] as $issue): ?>
            <div class="issue-item">• <?= htmlspecialchars($issue) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Provider Health Overview -->
    <div class="health-overview">
      
      <!-- AI Providers -->
      <div class="health-card">
        <h3>🤖 AI Providers</h3>
        <ul class="provider-list">
          <?php foreach ($health['ai_providers'] as $name => $status): ?>
            <li class="provider-item">
              <span class="provider-name"><?= ucfirst($name) ?></span>
              <span class="provider-status status-<?= $status['status'] ?>">
                <?= $status['status'] === 'healthy' ? '✅' : ($status['status'] === 'unhealthy' ? '❌' : '❓') ?>
                <?= ucfirst($status['status']) ?>
              </span>
            </li>
            <?php if ($status['message'] !== 'Not tested'): ?>
              <li style="font-size:12px;color:#666;padding-left:20px;margin-top:-5px">
                <?= htmlspecialchars($status['message']) ?>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
        
        <div style="margin-top:15px">
          <strong>Active:</strong> <?= ucfirst($config['active_providers']['ai']) ?>
        </div>
      </div>

      <!-- License Validators -->
      <div class="health-card">
        <h3>🛡️ License Validators</h3>
        <ul class="provider-list">
          <?php foreach ($health['license_validators'] as $name => $status): ?>
            <li class="provider-item">
              <span class="provider-name"><?= ucfirst($name) ?></span>
              <span class="provider-status status-<?= $status['status'] ?>">
                <?= $status['status'] === 'healthy' ? '✅' : ($status['status'] === 'unhealthy' ? '❌' : '❓') ?>
                <?= ucfirst($status['status']) ?>
              </span>
            </li>
            <?php if ($status['message'] !== 'Not tested'): ?>
              <li style="font-size:12px;color:#666;padding-left:20px;margin-top:-5px">
                <?= htmlspecialchars($status['message']) ?>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
        
        <div style="margin-top:15px">
          <strong>Active:</strong> <?= ucfirst($config['active_providers']['license']) ?>
        </div>
      </div>
    </div>

    <!-- Configuration Summary -->
    <div class="health-card">
      <h3>⚙️ System Configuration</h3>
      
      <div class="config-summary">
        <h4>Purchase Validation</h4>
        <div class="config-item">
          <span class="config-label">Validation Enabled:</span>
          <span class="config-value"><?= $config['system_settings']['purchase_validation_enabled'] ? 'Yes' : 'No' ?></span>
        </div>
        <div class="config-item">
          <span class="config-label">Code Field Shown:</span>
          <span class="config-value"><?= $config['system_settings']['purchase_code_enabled'] ? 'Yes' : 'No' ?></span>
        </div>
        <div class="config-item">
          <span class="config-label">Code Required:</span>
          <span class="config-value"><?= $config['system_settings']['purchase_code_required'] ? 'Yes' : 'No' ?></span>
        </div>
      </div>

      <div class="config-summary">
        <h4>AI & Processing</h4>
        <div class="config-item">
          <span class="config-label">AI Categorization:</span>
          <span class="config-value"><?= $config['system_settings']['ai_categorization_enabled'] ? 'Enabled' : 'Disabled' ?></span>
        </div>
        <div class="config-item">
          <span class="config-label">Token Limit:</span>
          <span class="config-value"><?= $config['system_settings']['ai_token_limit'] ?> tokens</span>
        </div>
        <div class="config-item">
          <span class="config-label">AJAX Rate Limit:</span>
          <span class="config-value"><?= $config['system_settings']['ajax_rate_limit'] ?> req/min</span>
        </div>
      </div>

      <div class="config-summary">
        <h4>Email Configuration</h4>
        <div class="config-item">
          <span class="config-label">Transport:</span>
          <span class="config-value"><?= ucfirst($config['system_settings']['mail_transport']) ?></span>
        </div>
        <div class="config-item">
          <span class="config-label">From Name:</span>
          <span class="config-value"><?= htmlspecialchars($config['system_settings']['mail_from_name']) ?></span>
        </div>
        <div class="config-item">
          <span class="config-label">From Address:</span>
          <span class="config-value"><?= htmlspecialchars($config['system_settings']['mail_from_address']) ?></span>
        </div>
      </div>
    </div>

    <!-- Provider Details -->
    <div class="health-overview">
      <?php foreach ($config['ai_providers'] as $name => $provider): ?>
        <div class="health-card">
          <h4>🤖 <?= $provider['display_name'] ?> Details</h4>
          
          <div class="config-item">
            <span class="config-label">Status:</span>
            <span class="config-value">
              <?= $provider['available'] ? '✅ Available' : '❌ Not Available' ?>
              <?= $provider['active'] ? ' (Active)' : '' ?>
            </span>
          </div>
          
          <?php if (isset($provider['info']['model'])): ?>
            <div class="config-item">
              <span class="config-label">Model:</span>
              <span class="config-value"><?= $provider['info']['model'] ?></span>
            </div>
          <?php endif; ?>
          
          <?php if (isset($provider['info']['features'])): ?>
            <div class="config-item">
              <span class="config-label">Features:</span>
              <span class="config-value"><?= implode(', ', $provider['info']['features']) ?></span>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Actions -->
    <div style="margin:30px 0;padding:20px;border-top:1px solid #eee;text-align:center">
      <a href="advanced_settings.php" class="btn primary">⚙️ Configure Providers</a>
      <a href="test_all_providers.php" class="btn">🧪 Run Full Test Suite</a>
      <button class="btn" onclick="exportHealthReport()">📊 Export Health Report</button>
    </div>
  </div>

  <script>
    function exportHealthReport() {
      const healthData = <?= json_encode($health) ?>;
      const configData = <?= json_encode($config) ?>;
      
      const report = {
        generated_at: new Date().toISOString(),
        health: healthData,
        configuration: configData
      };
      
      const blob = new Blob([JSON.stringify(report, null, 2)], {type: 'application/json'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'replypilot-health-report-' + new Date().toISOString().split('T')[0] + '.json';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }

    // Auto-refresh every 5 minutes
    setTimeout(() => {
      location.reload();
    }, 300000);
  </script>
</body>
</html>
