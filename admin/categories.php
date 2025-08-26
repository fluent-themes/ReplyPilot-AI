<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';

use App\Support\CategoryRules;
use App\Support\Settings;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$saved = false;
$testResult = null;
$error = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_general_settings':
                $aiEnabled = isset($_POST['ai_categorization_enabled']);
                $threshold = (float)($_POST['ai_confidence_threshold'] ?? 0.8);
                $defaultCategory = trim($_POST['default_category'] ?? 'General');
                
                Settings::set('ai_categorization_enabled', $aiEnabled);
                Settings::set('ai_categorization_confidence_threshold', $threshold);
                Settings::set('default_category', $defaultCategory);
                $saved = true;
                break;
                
            case 'save_rules':
                $rulesJson = $_POST['rules_json'] ?? '';
                
                // Add size limit check (1MB max)
                if (strlen($rulesJson) > 1048576) {
                    $error = 'JSON data too large (max 1MB)';
                    break;
                }
                
                $rules = json_decode($rulesJson, true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($rules)) {
                    if (CategoryRules::saveRules($rules)) {
                        $saved = true;
                    } else {
                        $error = 'Failed to save rules';
                    }
                } else {
                    $error = 'Invalid JSON format';
                }
                break;
                
            case 'test_message':
                $testMessage = trim($_POST['test_message'] ?? '');
                $testSubject = trim($_POST['test_subject'] ?? '');
                
                if ($testMessage !== '') {
                    $testResult = CategoryRules::testCategorization($testMessage, $testSubject ?: null);
                }
                break;
                
            case 'add_simple_rule':
                $ruleName = trim($_POST['rule_name'] ?? '');
                $ruleCategory = trim($_POST['rule_category'] ?? '');
                $rulePriority = (int)($_POST['rule_priority'] ?? 50);
                $ruleKeywords = trim($_POST['rule_keywords'] ?? '');
                
                if ($ruleName && $ruleCategory && $ruleKeywords) {
                    $rules = CategoryRules::loadRules();
                    $maxId = 0;
                    foreach ($rules as $rule) {
                        $maxId = max($maxId, $rule['id'] ?? 0);
                    }
                    
                    $keywords = array_filter(array_map('trim', explode(',', $ruleKeywords)));
                    $conditions = [];
                    foreach ($keywords as $keyword) {
                        $conditions[] = ['field' => 'message', 'operator' => 'contains', 'value' => $keyword];
                    }
                    
                    $newRule = [
                        'id' => $maxId + 1,
                        'name' => $ruleName,
                        'priority' => $rulePriority,
                        'category' => $ruleCategory,
                        'conditions' => ['any' => $conditions]
                    ];
                    
                    $rules[] = $newRule;
                    if (CategoryRules::saveRules($rules)) {
                        $saved = true;
                    } else {
                        $error = 'Failed to add rule';
                    }
                }
                break;
        }
    }
}

// Get current settings and rules
$aiEnabled = Settings::get('ai_categorization_enabled', true);
$threshold = Settings::get('ai_categorization_confidence_threshold', 0.8);
$defaultCategory = Settings::get('default_category', 'General');
$rules = CategoryRules::loadRules();
$categories = CategoryRules::getAvailableCategories();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Category Management — ReplyPilot-AI</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .settings-card{border:1px solid #eee;border-radius:8px;padding:20px;margin:16px 0;background:#fff}
    .settings-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
    .form-group{margin:15px 0}.form-group label{display:block;margin-bottom:5px;font-weight:600}
    .form-group input,.form-group textarea,.form-group select{width:100%;padding:10px;border:1px solid #ddd;border-radius:4px}
    .form-group small{color:#666;font-size:12px;margin-top:5px;display:block}
    .status-indicator{padding:8px 12px;border-radius:4px;font-size:14px;margin:10px 0}
    .status-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
    .status-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
    .status-warning{background:#fff3cd;color:#856404;border:1px solid #ffeaa7}
    .rule-item{background:#f8f9fa;padding:15px;margin:10px 0;border-radius:6px;border-left:4px solid #007cba}
    .rule-header{display:flex;justify-content:between;align-items:center;margin-bottom:10px}
    .rule-priority{background:#007cba;color:white;padding:2px 8px;border-radius:12px;font-size:12px}
    .test-result{background:#f8f9fa;padding:15px;border-radius:6px;margin:15px 0}
    .json-editor{font-family:monospace;font-size:13px;line-height:1.4}
    .btn-group{display:flex;gap:10px;margin-top:15px}
    .tabs{display:flex;border-bottom:2px solid #eee;margin-bottom:20px}
    .tab{padding:10px 20px;cursor:pointer;border-bottom:2px solid transparent}
    .tab.active{border-bottom-color:#007cba;color:#007cba}
    .tab-content{display:none}.tab-content.active{display:block}
  </style>
</head>
<body>
  <div class="container">
    <div class="settings-header">
      <h1>🏷️ Category Management</h1>
      <div>
        <a class="btn" href="envato.php">Envato Settings</a>
        <a class="btn" href="index.php">← Back to Admin</a>
      </div>
    </div>

    <?php if ($saved): ?>
      <div class="status-indicator status-success">✅ Settings saved successfully!</div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="status-indicator status-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="tabs">
      <div class="tab active" onclick="showTab('general')">General Settings</div>
      <div class="tab" onclick="showTab('rules')">Categorization Rules</div>
      <div class="tab" onclick="showTab('test')">Test & Debug</div>
    </div>

    <!-- General Settings Tab -->
    <div id="tab-general" class="tab-content active">
      <div class="settings-card">
        <h3>AI Categorization Settings</h3>
        <form method="post">
          <input type="hidden" name="action" value="save_general_settings">
          
          <div class="form-group">
            <label>
              <input type="checkbox" name="ai_categorization_enabled" <?= $aiEnabled ? 'checked' : '' ?>>
              Enable AI-powered categorization
            </label>
            <small>When enabled, AI will suggest categories when no rules match</small>
          </div>

          <div class="form-group">
            <label for="ai_confidence_threshold">AI Confidence Threshold</label>
            <input type="number" id="ai_confidence_threshold" name="ai_confidence_threshold" 
                   min="0" max="1" step="0.1" value="<?= $threshold ?>">
            <small>Minimum confidence level (0.0-1.0) required to use AI suggestions</small>
          </div>

          <div class="form-group">
            <label for="default_category">Default Category</label>
            <input type="text" id="default_category" name="default_category" value="<?= htmlspecialchars($defaultCategory) ?>">
            <small>Fallback category when no rules match and AI is disabled/unavailable</small>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn primary">💾 Save Settings</button>
          </div>
        </form>
      </div>

      <div class="settings-card">
        <h3>Current Categories</h3>
        <div style="display:flex;flex-wrap:wrap;gap:8px">
          <?php foreach ($categories as $category): ?>
            <span style="background:#e3f2fd;padding:6px 12px;border-radius:16px;font-size:14px">
              <?= htmlspecialchars($category) ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Rules Tab -->
    <div id="tab-rules" class="tab-content">
      <div class="settings-card">
        <h3>Quick Add Rule</h3>
        <form method="post">
          <input type="hidden" name="action" value="add_simple_rule">
          
          <div style="display:grid;grid-template-columns:1fr 1fr 100px;gap:15px">
            <div class="form-group">
              <label for="rule_name">Rule Name</label>
              <input type="text" id="rule_name" name="rule_name" placeholder="e.g., Billing Issues" required>
            </div>
            
            <div class="form-group">
              <label for="rule_category">Category</label>
              <input type="text" id="rule_category" name="rule_category" placeholder="e.g., Billing" required>
            </div>
            
            <div class="form-group">
              <label for="rule_priority">Priority</label>
              <input type="number" id="rule_priority" name="rule_priority" value="50" min="1" max="999">
            </div>
          </div>
          
          <div class="form-group">
            <label for="rule_keywords">Keywords (comma-separated)</label>
            <input type="text" id="rule_keywords" name="rule_keywords" 
                   placeholder="refund, billing, invoice, payment" required>
            <small>Messages containing any of these keywords will be categorized to this category</small>
          </div>
          
          <button type="submit" class="btn primary">➕ Add Rule</button>
        </form>
      </div>

      <div class="settings-card">
        <h3>Current Rules (Priority Order)</h3>
        <?php if (empty($rules)): ?>
          <p style="color:#666;text-align:center;padding:20px">No rules configured yet.</p>
        <?php else: ?>
          <?php foreach ($rules as $rule): ?>
            <div class="rule-item">
              <div class="rule-header">
                <strong><?= htmlspecialchars($rule['name'] ?? 'Rule #' . ($rule['id'] ?? '?')) ?></strong>
                <span class="rule-priority">Priority: <?= $rule['priority'] ?? 0 ?></span>
              </div>
              <div style="color:#666;margin-bottom:10px">
                Category: <strong><?= htmlspecialchars($rule['category'] ?? 'Unknown') ?></strong>
              </div>
              <div style="font-size:13px;color:#888">
                Conditions: <?= formatConditions($rule['conditions'] ?? []) ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="settings-card">
        <h3>Advanced: JSON Editor</h3>
        <p style="color:#666;margin-bottom:15px">
          For advanced users: edit the complete rules configuration in JSON format.
        </p>
        
        <form method="post">
          <input type="hidden" name="action" value="save_rules">
          
          <div class="form-group">
            <textarea name="rules_json" class="json-editor" rows="20"><?= htmlspecialchars(json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
            <small>
              <strong>Warning:</strong> Invalid JSON will break categorization. 
              <a href="#" onclick="showJsonHelp()">View JSON format help</a>
            </small>
          </div>
          
          <div class="btn-group">
            <button type="submit" class="btn primary">💾 Save JSON Rules</button>
            <button type="button" class="btn" onclick="resetRules()">🔄 Reset to Defaults</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Test Tab -->
    <div id="tab-test" class="tab-content">
      <div class="settings-card">
        <h3>Test Categorization</h3>
        <form method="post">
          <input type="hidden" name="action" value="test_message">
          
          <div class="form-group">
            <label for="test_subject">Subject (Optional)</label>
            <input type="text" id="test_subject" name="test_subject" placeholder="Subject line">
          </div>
          
          <div class="form-group">
            <label for="test_message">Test Message</label>
            <textarea id="test_message" name="test_message" rows="4" 
                      placeholder="Enter a test support message to see how it would be categorized..." required></textarea>
          </div>
          
          <button type="submit" class="btn primary">🧪 Test Categorization</button>
        </form>
        
        <?php if ($testResult): ?>
          <div class="test-result">
            <h4>Test Results</h4>
            <p><strong>Message:</strong> "<?= htmlspecialchars($testResult['message']) ?>"</p>
            <p><strong>Final Category:</strong> <span style="background:#e3f2fd;padding:4px 8px;border-radius:4px"><?= htmlspecialchars($testResult['final_category']) ?></span></p>
            
            <?php if ($testResult['rule_match']): ?>
              <p><strong>Matched Rule:</strong> <?= htmlspecialchars($testResult['rule_match']['name'] ?? 'Unknown') ?> (Priority: <?= $testResult['rule_match']['priority'] ?? 0 ?>)</p>
            <?php else: ?>
              <p><strong>Rule Match:</strong> No rules matched</p>
            <?php endif; ?>
            
            <?php if ($testResult['ai_suggestion']): ?>
              <p><strong>AI Suggestion:</strong> <?= htmlspecialchars($testResult['ai_suggestion']) ?></p>
            <?php endif; ?>
            
            <details style="margin-top:15px">
              <summary style="cursor:pointer;color:#007cba">View Debug Details</summary>
              <pre style="background:#f8f9fa;padding:10px;border-radius:4px;font-size:12px;margin-top:10px;overflow:auto"><?= htmlspecialchars(json_encode($testResult, JSON_PRETTY_PRINT)) ?></pre>
            </details>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <script>
    function showTab(tabName) {
      // Hide all tab content
      document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
      
      // Show selected tab
      document.getElementById('tab-' + tabName).classList.add('active');
      event.target.classList.add('active');
    }
    
    function resetRules() {
      if (confirm('Reset all rules to defaults? This will remove any custom rules you have created.')) {
        fetch('<?= $_SERVER['PHP_SELF'] ?>', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'action=save_rules&rules_json=' + encodeURIComponent('[]')
        }).then(() => location.reload());
      }
    }
    
    function showJsonHelp() {
      alert('JSON Rule Format:\n\n' +
            '{\n' +
            '  "id": 1,\n' +
            '  "name": "Rule Name",\n' +
            '  "priority": 100,\n' +
            '  "category": "Category Name",\n' +
            '  "conditions": {\n' +
            '    "any": [\n' +
            '      {"field": "message", "operator": "contains", "value": "keyword"}\n' +
            '    ]\n' +
            '  }\n' +
            '}\n\n' +
            'Operators: contains, starts_with, ends_with, equals, regex\n' +
            'Fields: message, subject, combined, message_length');
    }
  </script>
</body>
</html>

<?php
// Helper function to format conditions for display
function formatConditions($conditions) {
    if (empty($conditions)) return 'None';
    
    if (isset($conditions['any'])) {
        $terms = [];
        foreach ($conditions['any'] as $cond) {
            $field = $cond['field'] ?? 'message';
            $op = $cond['operator'] ?? 'contains';
            $val = $cond['value'] ?? '';
            $terms[] = "{$field} {$op} '{$val}'";
        }
        return 'Any of: ' . implode(', ', $terms);
    }
    
    if (isset($conditions['all'])) {
        $terms = [];
        foreach ($conditions['all'] as $cond) {
            $field = $cond['field'] ?? 'message';
            $op = $cond['operator'] ?? 'contains';
            $val = $cond['value'] ?? '';
            $terms[] = "{$field} {$op} '{$val}'";
        }
        return 'All of: ' . implode(', ', $terms);
    }
    
    return 'Single condition';
}
?>
