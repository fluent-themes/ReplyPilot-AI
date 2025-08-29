<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';

use App\Support\Settings;
use App\Factories\AIProviderFactory;
use App\Factories\LicenseValidatorFactory;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Get current settings
$aiProvider = Settings::get('ai_provider', 'openai');
$licenseValidator = Settings::get('license_validator', 'envato');
$purchaseValidation = Settings::get('purchase_validation_enabled', false);
$purchaseCodeEnabled = Settings::get('purchase_code_enabled', false);
$purchaseCodeRequired = Settings::get('purchase_code_required', false);

// Get available providers
$availableAI = AIProviderFactory::getAvailableProviders();
$availableLicense = LicenseValidatorFactory::getAvailableValidators();
$aiSchemas = AIProviderFactory::getConfigSchemas();
$licenseSchemas = LicenseValidatorFactory::getConfigSchemas();

$saved = isset($_GET['saved']) && $_GET['saved'] == '1';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Advanced Settings — ReplyPilot-AI</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .settings-tabs{display:flex;border-bottom:2px solid #eee;margin:20px 0 0}
    .tab{padding:12px 20px;cursor:pointer;border:none;background:none;font-size:14px;color:#666}
    .tab.active{color:#007cba;border-bottom:2px solid #007cba;margin-bottom:-2px}
    .tab-content{display:none;padding:20px 0}
    .tab-content.active{display:block}
    .setting-group{background:#fff;border:1px solid #eee;border-radius:8px;padding:20px;margin:15px 0}
    .setting-group h3{margin:0 0 15px;color:#333;display:flex;align-items:center;gap:8px}
    .setting-row{display:grid;grid-template-columns:200px 1fr;gap:15px;align-items:start;margin:15px 0}
    .setting-label{font-weight:600;color:#555}
    .setting-control{display:flex;flex-direction:column;gap:5px}
    .setting-help{font-size:12px;color:#666;margin-top:5px}
    .provider-status{display:inline-flex;align-items:center;gap:5px;padding:4px 8px;border-radius:12px;font-size:11px;font-weight:bold}
    .status-available{background:#d4edda;color:#155724}
    .status-unavailable{background:#f8d7da;color:#721c24}
    .provider-card{border:1px solid #eee;border-radius:6px;padding:15px;margin:10px 0;background:#f8f9fa}
    .provider-card.selected{border-color:#007cba;background:#e3f2fd}
    .provider-header{display:flex;justify-content:between;align-items:center;margin-bottom:10px}
    .btn-test{background:#28a745;color:white;border:none;padding:6px 12px;border-radius:4px;font-size:12px;cursor:pointer}
    .btn-test:hover{background:#218838}
    .toast{position:fixed;right:16px;top:16px;background:#0b875b;color:#fff;padding:10px 14px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.15);display:none;z-index:9999}
    .toast.show{display:block}
    .email-error{color:#dc3545;font-size:12px;margin-top:5px}
    input[type="email"]:invalid{border-color:#dc3545}
    input[type="email"]:valid{border-color:#28a745}
    @media(max-width:768px){.setting-row{grid-template-columns:1fr;gap:8px}}
  </style>
</head>
<body>
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h1>⚙️ Advanced Settings</h1>
      <a class="btn" href="index.php">← Back to Dashboard</a>
    </div>

    <!-- Settings Tabs -->
    <div class="settings-tabs">
      <button class="tab active" data-tab="ai">🤖 AI Providers</button>
      <button class="tab" data-tab="license">🛡️ License Validation</button>
      <button class="tab" data-tab="analytics">📊 Analytics</button>
      <button class="tab" data-tab="cache">💾 Response Cache</button>
      <button class="tab" data-tab="prompt">🎯 Prompt Optimization</button>
      <button class="tab" data-tab="email">📧 Email Settings</button>
      <button class="tab" data-tab="security">🔒 Security & Limits</button>
    </div>

    <form method="post" action="update_advanced_settings.php">
      <?php 
      // Generate CSRF token
      if (!isset($_SESSION['csrf_token'])) {
          $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }
      ?>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      
      <!-- AI Providers Tab -->
      <div class="tab-content active" id="tab-ai">
        <div class="setting-group">
          <h3>🤖 AI Provider Configuration</h3>
          
          <div class="setting-row">
            <div class="setting-label">Active Provider</div>
            <div class="setting-control">
              <select name="ai_provider">
                <?php foreach ($availableAI as $name => $info): ?>
                  <option value="<?= $name ?>" <?= $aiProvider === $name ? 'selected' : '' ?>>
                    <?= ucfirst($name) ?> 
                    <?= $info['available'] ? '✅' : '❌' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="setting-help">Select your preferred AI provider for generating responses</div>
            </div>
          </div>

          <?php foreach ($aiSchemas as $provider => $schema): ?>
            <div class="provider-card <?= $aiProvider === $provider ? 'selected' : '' ?>" data-provider="<?= $provider ?>">
              <div class="provider-header">
                <strong><?= ucfirst($provider) ?> Configuration</strong>
                <span class="provider-status <?= $availableAI[$provider]['available'] ? 'status-available' : 'status-unavailable' ?>">
                  <?= $availableAI[$provider]['available'] ? 'Available' : 'Not Configured' ?>
                </span>
              </div>
              
              <?php if (isset($schema['schema'])): ?>
                <?php foreach ($schema['schema'] as $field => $config): ?>
                  <div class="setting-row">
                    <div class="setting-label"><?= $config['label'] ?></div>
                    <div class="setting-control">
                      <?php if ($config['type'] === 'select'): ?>
                        <select name="ai_<?= $provider ?>_<?= $field ?>">
                          <?php foreach ($config['options'] as $value => $label): ?>
                            <option value="<?= $value ?>"><?= $label ?></option>
                          <?php endforeach; ?>
                        </select>
                      <?php elseif ($config['type'] === 'password'): ?>
                        <input type="password" name="ai_<?= $provider ?>_<?= $field ?>" placeholder="Enter <?= $config['label'] ?>">
                      <?php elseif ($config['type'] === 'range'): ?>
                        <input type="range" name="ai_<?= $provider ?>_<?= $field ?>" 
                               min="<?= $config['min'] ?>" max="<?= $config['max'] ?>" 
                               step="<?= $config['step'] ?>" value="<?= $config['default'] ?>">
                      <?php elseif ($config['type'] === 'number'): ?>
                        <input type="number" name="ai_<?= $provider ?>_<?= $field ?>" 
                               min="<?= $config['min'] ?? '' ?>" max="<?= $config['max'] ?? '' ?>" 
                               value="<?= $config['default'] ?? '' ?>">
                      <?php else: ?>
                        <div style="color:#666;font-style:italic"><?= $config['help'] ?? 'Information only' ?></div>
                      <?php endif; ?>
                      <?php if (isset($config['help'])): ?>
                        <div class="setting-help"><?= $config['help'] ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
              
              <button type="button" class="btn-test" onclick="testAIProvider('<?= $provider ?>')">Test Connection</button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- License Validation Tab -->
      <div class="tab-content" id="tab-license">
        <div class="setting-group">
          <h3>🛡️ Purchase Code Validation</h3>
          
          <div class="setting-row">
            <div class="setting-label">Enable Validation</div>
            <div class="setting-control">
              <label><input type="checkbox" name="purchase_validation_enabled" <?= $purchaseValidation ? 'checked' : '' ?>> Enable purchase code validation</label>
              <div class="setting-help">When enabled, users can submit purchase codes for verification</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Show Code Field</div>
            <div class="setting-control">
              <label><input type="checkbox" name="purchase_code_enabled" <?= $purchaseCodeEnabled ? 'checked' : '' ?>> Show purchase code field in form</label>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Require Code</div>
            <div class="setting-control">
              <label><input type="checkbox" name="purchase_code_required" <?= $purchaseCodeRequired ? 'checked' : '' ?>> Make purchase code required</label>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Validation Provider</div>
            <div class="setting-control">
              <select name="license_validator">
                <?php foreach ($availableLicense as $name => $info): ?>
                  <option value="<?= $name ?>" <?= $licenseValidator === $name ? 'selected' : '' ?>>
                    <?= ucfirst($name) ?> 
                    <?= $info['available'] ? '✅' : '❌' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <?php foreach ($licenseSchemas as $provider => $schema): ?>
            <div class="provider-card <?= $licenseValidator === $provider ? 'selected' : '' ?>" data-provider="<?= $provider ?>">
              <div class="provider-header">
                <strong><?= ucfirst($provider) ?> Configuration</strong>
                <span class="provider-status <?= $availableLicense[$provider]['available'] ? 'status-available' : 'status-unavailable' ?>">
                  <?= $availableLicense[$provider]['available'] ? 'Available' : 'Not Configured' ?>
                </span>
              </div>
              
              <?php foreach ($schema as $field => $config): ?>
                <div class="setting-row">
                  <div class="setting-label"><?= $config['label'] ?></div>
                  <div class="setting-control">
                    <?php if ($config['type'] === 'checkbox'): ?>
                      <label><input type="checkbox" name="license_<?= $provider ?>_<?= $field ?>" <?= $config['default'] ? 'checked' : '' ?>> <?= $config['label'] ?></label>
                    <?php elseif ($config['type'] === 'password'): ?>
                      <input type="password" name="license_<?= $provider ?>_<?= $field ?>" placeholder="Enter <?= $config['label'] ?>">
                    <?php else: ?>
                      <input type="text" name="license_<?= $provider ?>_<?= $field ?>" placeholder="<?= $config['placeholder'] ?? '' ?>">
                    <?php endif; ?>
                    <?php if (isset($config['help'])): ?>
                      <div class="setting-help"><?= $config['help'] ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
              
              <button type="button" class="btn-test" onclick="testLicenseProvider('<?= $provider ?>')">Test Connection</button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Email Settings Tab -->
      <div class="tab-content" id="tab-email">
        <div class="setting-group">
          <h3>📧 Email Configuration</h3>
          <p style="color:#666;margin:0 0 20px">Configure email delivery and templates</p>
          
          <div class="setting-row">
            <div class="setting-label">Email Transport</div>
            <div class="setting-control">
              <select name="mail_transport">
                <option value="smtp">SMTP</option>
                <option value="sendmail">Sendmail</option>
                <option value="file">File (Testing)</option>
              </select>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">From Name</div>
            <div class="setting-control">
              <input type="text" name="mail_from_name" placeholder="ReplyPilot AI">
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">From Email</div>
            <div class="setting-control">
              <input type="email" name="mail_from_address" placeholder="noreply@example.com" 
                     pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" 
                     title="Please enter a valid email address"
                     maxlength="254" required>
              <div class="setting-help">Must be a valid email address (used as From address for outgoing emails)</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Analytics Tab -->
      <div class="tab-content" id="tab-analytics">
        <div class="setting-group">
          <h3>📊 Analytics Configuration</h3>
          
          <div class="setting-row">
            <div class="setting-label">Enable Analytics</div>
            <div class="setting-control">
              <label><input type="checkbox" name="analytics_enabled" <?= Settings::get('analytics_enabled', true) ? 'checked' : '' ?>> Track AI usage and performance metrics</label>
              <div class="setting-help">Collect data on AI responses, token usage, and system performance</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Data Retention</div>
            <div class="setting-control">
              <input type="number" name="analytics_retention_days" value="<?= Settings::get('analytics_retention_days', 90) ?>" min="7" max="365">
              <div class="setting-help">How many days to keep analytics data (7-365 days)</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Performance Tracking</div>
            <div class="setting-control">
              <label><input type="checkbox" name="performance_analytics_enabled" <?= Settings::get('performance_analytics_enabled', true) ? 'checked' : '' ?>> Monitor response times and system metrics</label>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">License Analytics</div>
            <div class="setting-control">
              <label><input type="checkbox" name="license_analytics_enabled" <?= Settings::get('license_analytics_enabled', true) ? 'checked' : '' ?>> Track purchase code validation attempts</label>
            </div>
          </div>
        </div>

        <div class="setting-group">
          <h3>📈 Real-time Monitoring</h3>
          
          <div class="setting-row">
            <div class="setting-label">Dashboard Refresh</div>
            <div class="setting-control">
              <select name="dashboard_refresh_interval">
                <option value="30" <?= Settings::get('dashboard_refresh_interval', 30) == 30 ? 'selected' : '' ?>>30 seconds</option>
                <option value="60" <?= Settings::get('dashboard_refresh_interval', 30) == 60 ? 'selected' : '' ?>>1 minute</option>
                <option value="300" <?= Settings::get('dashboard_refresh_interval', 30) == 300 ? 'selected' : '' ?>>5 minutes</option>
                <option value="0" <?= Settings::get('dashboard_refresh_interval', 30) == 0 ? 'selected' : '' ?>>Disabled</option>
              </select>
              <div class="setting-help">Auto-refresh interval for analytics dashboard</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Error Alerting</div>
            <div class="setting-control">
              <label><input type="checkbox" name="error_alerting_enabled" <?= Settings::get('error_alerting_enabled', false) ? 'checked' : '' ?>> Email alerts for high error rates</label>
              <div class="setting-help">Send email when error rate exceeds threshold</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Error Threshold (%)</div>
            <div class="setting-control">
              <input type="number" name="error_threshold_percent" value="<?= Settings::get('error_threshold_percent', 10) ?>" min="1" max="50">
              <div class="setting-help">Send alert when error rate exceeds this percentage</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Alert Email</div>
            <div class="setting-control">
              <input type="email" name="alert_email_address" 
                     placeholder="admin@example.com" 
                     pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" 
                     title="Please enter a valid email address"
                     maxlength="254">
              <div class="setting-help">Email address to receive error alerts (optional)</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cache Tab -->
      <div class="tab-content" id="tab-cache">
        <div class="setting-group">
          <h3>💾 Response Cache Settings</h3>
          
          <div class="setting-row">
            <div class="setting-label">Enable Response Cache</div>
            <div class="setting-control">
              <label><input type="checkbox" name="response_cache_enabled" <?= Settings::get('response_cache_enabled', true) ? 'checked' : '' ?>> Cache similar AI responses to save tokens</label>
              <div class="setting-help">Automatically reuse responses for similar messages</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Cache TTL (seconds)</div>
            <div class="setting-control">
              <input type="number" name="cache_ttl" value="<?= Settings::get('cache_ttl', 3600) ?>" min="300" max="86400">
              <div class="setting-help">How long to keep cached responses (300-86400 seconds)</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Similarity Threshold</div>
            <div class="setting-control">
              <input type="range" name="cache_similarity_threshold" value="<?= Settings::get('cache_similarity_threshold', 0.85) ?>" min="0.5" max="1.0" step="0.05" oninput="this.nextElementSibling.textContent = this.value">
              <span><?= Settings::get('cache_similarity_threshold', 0.85) ?></span>
              <div class="setting-help">Minimum similarity to use cached response (0.5-1.0)</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Max Cache Entries</div>
            <div class="setting-control">
              <input type="number" name="cache_max_entries" value="<?= Settings::get('cache_max_entries', 10000) ?>" min="100" max="50000">
              <div class="setting-help">Maximum number of cached responses</div>
            </div>
          </div>
        </div>

        <div class="setting-group">
          <h3>🔧 Cache Management</h3>
          
          <div class="setting-row">
            <div class="setting-label">Auto-cleanup</div>
            <div class="setting-control">
              <label><input type="checkbox" name="cache_auto_cleanup" <?= Settings::get('cache_auto_cleanup', true) ? 'checked' : '' ?>> Automatically clean expired entries</label>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Cleanup Frequency</div>
            <div class="setting-control">
              <select name="cache_cleanup_frequency">
                <option value="3600" <?= Settings::get('cache_cleanup_frequency', 3600) == 3600 ? 'selected' : '' ?>>Hourly</option>
                <option value="21600" <?= Settings::get('cache_cleanup_frequency', 3600) == 21600 ? 'selected' : '' ?>>Every 6 hours</option>
                <option value="86400" <?= Settings::get('cache_cleanup_frequency', 3600) == 86400 ? 'selected' : '' ?>>Daily</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Prompt Optimization Tab -->
      <div class="tab-content" id="tab-prompt">
        <div class="setting-group">
          <h3>🎯 Prompt Optimization</h3>
          
          <div class="setting-row">
            <div class="setting-label">Enable Optimization</div>
            <div class="setting-control">
              <label><input type="checkbox" name="prompt_optimization_enabled" <?= Settings::get('prompt_optimization_enabled', true) ? 'checked' : '' ?>> Automatically optimize prompts for better responses</label>
              <div class="setting-help">Use AI to improve prompt quality and reduce token usage</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Optimization Level</div>
            <div class="setting-control">
              <select name="prompt_optimization_level">
                <option value="conservative" <?= Settings::get('prompt_optimization_level', 'moderate') == 'conservative' ? 'selected' : '' ?>>Conservative</option>
                <option value="moderate" <?= Settings::get('prompt_optimization_level', 'moderate') == 'moderate' ? 'selected' : '' ?>>Moderate</option>
                <option value="aggressive" <?= Settings::get('prompt_optimization_level', 'moderate') == 'aggressive' ? 'selected' : '' ?>>Aggressive</option>
              </select>
              <div class="setting-help">How much to optimize prompts</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Track Optimizations</div>
            <div class="setting-control">
              <label><input type="checkbox" name="prompt_optimization_analytics" <?= Settings::get('prompt_optimization_analytics', true) ? 'checked' : '' ?>> Track optimization performance and results</label>
            </div>
          </div>
        </div>

        <div class="setting-group">
          <h3>📝 Prompt Templates</h3>
          
          <div class="setting-row">
            <div class="setting-label">Support Template</div>
            <div class="setting-control">
              <textarea name="prompt_template_support" rows="3" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"><?= htmlspecialchars(Settings::get('prompt_template_support', 'You are a helpful support agent for {product_name}. Respond in a {tone} tone to: {message}')) ?></textarea>
              <div class="setting-help">Default template for support queries</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Sales Template</div>
            <div class="setting-control">
              <textarea name="prompt_template_sales" rows="3" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"><?= htmlspecialchars(Settings::get('prompt_template_sales', 'You are a knowledgeable sales representative for {product_name}. Use a {tone} approach to help with: {message}')) ?></textarea>
              <div class="setting-help">Template for sales-related queries</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Token Optimization</div>
            <div class="setting-control">
              <label><input type="checkbox" name="prompt_token_optimization" <?= Settings::get('prompt_token_optimization', true) ? 'checked' : '' ?>> Optimize prompts to reduce token usage</label>
              <div class="setting-help">Automatically shorten prompts while maintaining quality</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Security & Limits Tab -->
      <div class="tab-content" id="tab-security">
        <div class="setting-group">
          <h3>🔒 Security & Rate Limiting</h3>
          
          <div class="setting-row">
            <div class="setting-label">AJAX Rate Limit</div>
            <div class="setting-control">
              <input type="number" name="ajax_rate_limit" value="6" min="1" max="60">
              <div class="setting-help">Maximum AJAX requests per minute per session</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Session Timeout</div>
            <div class="setting-control">
              <input type="number" name="session_timeout" value="3600" min="300" max="86400">
              <div class="setting-help">Session timeout in seconds (default: 1 hour)</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">AI Token Limit</div>
            <div class="setting-control">
              <input type="number" name="ai_token_limit" value="1000" min="100" max="4000">
              <div class="setting-help">Maximum tokens per AI request</div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Installer Token</div>
            <div class="setting-control">
              <input type="password" name="installer_token" placeholder="Leave blank to keep current">
              <div class="setting-help">Change the admin access token (updates .env file)</div>
              <div class="setting-help" style="color:#666;font-size:11px">
                Current token is set (hidden for security). Change only if needed.
              </div>
            </div>
          </div>

          <div class="setting-row">
            <div class="setting-label">Debug Mode</div>
            <div class="setting-control">
              <label><input type="checkbox" name="app_debug" <?= \App\Core\Env::get('APP_DEBUG', false) ? 'checked' : '' ?>> Enable debug mode (shows detailed errors)</label>
              <div class="setting-help">⚠️ Disable in production</div>
            </div>
          </div>
        </div>
      </div>

      <div style="margin:30px 0;padding:20px;border-top:1px solid #eee">
        <button class="btn primary" type="submit">💾 Save All Settings</button>
        <button class="btn" type="reset">🔄 Reset Form</button>
        <button class="btn" type="button" onclick="testAnalytics()" style="background:#28a745">📊 Test Analytics</button>
        <button class="btn" type="button" onclick="clearAnalytics()" style="background:#dc3545">🗑️ Clear Analytics</button>
      </div>
    </form>
  </div>

  <div id="toast" class="toast">Settings saved successfully!</div>

  <script>
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
        
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
      });
    });

    // Show saved toast
    <?php if ($saved): ?>
      const toast = document.getElementById('toast');
      <?php if (isset($_SESSION['token_updated_notice'])): ?>
        toast.textContent = '<?= htmlspecialchars($_SESSION['token_updated_notice']) ?>';
        <?php unset($_SESSION['token_updated_notice']); ?>
      <?php endif; ?>
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 5000);
    <?php endif; ?>

    // Provider testing functions
    function testAIProvider(provider) {
      // AJAX call to test AI provider
      fetch('test_provider.php?type=ai&provider=' + provider)
        .then(r => r.json())
        .then(data => {
          alert(data.message || 'Test completed');
        })
        .catch(e => alert('Test failed: ' + e.message));
    }

    function testLicenseProvider(provider) {
      // AJAX call to test license provider
      fetch('test_provider.php?type=license&provider=' + provider)
        .then(r => r.json())
        .then(data => {
          alert(data.message || 'Test completed');
        })
        .catch(e => alert('Test failed: ' + e.message));
    }

    // Provider selection highlighting
    document.querySelectorAll('select[name="ai_provider"], select[name="license_validator"]').forEach(select => {
      select.addEventListener('change', () => {
        const type = select.name.includes('ai') ? 'ai' : 'license';
        const container = select.closest('.tab-content');
        
        container.querySelectorAll('.provider-card').forEach(card => {
          card.classList.remove('selected');
          if (card.dataset.provider === select.value) {
            card.classList.add('selected');
          }
        });
      });
    });

    // Email validation function
    function validateEmail(email) {
      const re = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;
      return re.test(email);
    }

    // Form validation before submit
    document.querySelector('form').addEventListener('submit', function(e) {
      const emailInputs = this.querySelectorAll('input[type="email"]');
      let hasError = false;
      
      emailInputs.forEach(input => {
        if (input.value && !validateEmail(input.value)) {
          input.style.borderColor = '#dc3545';
          hasError = true;
          
          // Show error message
          let errorMsg = input.parentNode.querySelector('.email-error');
          if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'email-error';
            errorMsg.style.color = '#dc3545';
            errorMsg.style.fontSize = '12px';
            errorMsg.style.marginTop = '5px';
            input.parentNode.appendChild(errorMsg);
          }
          errorMsg.textContent = 'Please enter a valid email address';
        } else {
          input.style.borderColor = '';
          const errorMsg = input.parentNode.querySelector('.email-error');
          if (errorMsg) errorMsg.remove();
        }
      });
      
      if (hasError) {
        e.preventDefault();
        alert('Please correct the email format errors before saving.');
      }
    });

    // Analytics testing functions
    function testAnalytics() {
      fetch('test_analytics.php')
        .then(r => r.json())
        .then(data => {
          alert(data.message || 'Analytics test completed');
        })
        .catch(e => alert('Test failed: ' + e.message));
    }

    function clearAnalytics() {
      if (confirm('Clear ALL analytics data? This cannot be undone.')) {
        fetch('clear_analytics.php', {method: 'POST'})
          .then(r => r.json())
          .then(data => {
            alert(data.message || 'Analytics data cleared');
          })
          .catch(e => alert('Clear failed: ' + e.message));
      }
    }
  </script>
</body>
</html>
