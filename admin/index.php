<?php
require __DIR__.'/guard.php';
use App\Repository\SubmissionRepository;
use App\Support\Settings;
use App\Helpers\ModeHelper;
use App\Registry\ProviderRegistry;

$db = $GLOBALS['container']['db'];

// Handle mock mode gracefully
if (ModeHelper::isMock() || !$db) {
    $rows = [
        ['id' => '1', 'name' => 'Mock User', 'email' => 'mock@example.com', 
         'message' => 'Mock support message', 'category' => 'Mock', 
         'created_at' => date('Y-m-d H:i:s')]
    ];
} else {
    try {
        // Pagination support
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        // Handle search with prepared statements to prevent SQL injection
        if (!empty($_GET['q'])) {
            $searchTerm = '%' . $_GET['q'] . '%';
            $stmt = $db->prepare('SELECT * FROM submissions WHERE name LIKE ? OR email LIKE ? OR message LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?');
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
        } else {
            $stmt = $db->prepare('SELECT * FROM submissions ORDER BY id DESC LIMIT ? OFFSET ?');
            $stmt->execute([$limit, $offset]);
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        $rows = [];
        $dbError = $e->getMessage();
    }
}

// Get current settings for dashboard
$purchaseValidation = Settings::get('purchase_validation_enabled', false);
$aiCategorization = Settings::get('ai_categorization_enabled', true);
$totalSubmissions = count($rows);
$categories = [];
foreach ($rows as $row) {
    $cat = $row['category'] ?? 'Unknown';
    $categories[$cat] = ($categories[$cat] ?? 0) + 1;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard — ReplyPilot-AI</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .dashboard-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin:20px 0}
    .dashboard-card{background:#fff;border:1px solid #eee;border-radius:8px;padding:20px;text-align:center}
    .dashboard-card h3{margin:0 0 10px;color:#007cba}
    .dashboard-card .number{font-size:2em;font-weight:bold;color:#333;margin:10px 0}
    .quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin:20px 0}
    .action-card{background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:15px}
    .action-card h4{margin:0 0 10px;color:#495057}
    .action-card p{margin:0 0 15px;color:#6c757d;font-size:14px}
    .action-card .btn{display:inline-block;text-decoration:none}
    .status-indicator{padding:4px 8px;border-radius:12px;font-size:12px;font-weight:bold}
    .status-enabled{background:#d4edda;color:#155724}
    .status-disabled{background:#f8d7da;color:#721c24}
    .category-list{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
    .category-tag{background:#e3f2fd;padding:4px 8px;border-radius:12px;font-size:12px}
    @media(max-width:768px){.dashboard-grid,.quick-actions{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h1>🚀 ReplyPilot-AI Admin</h1>
      <div>
        <a href="/" class="btn">🏠 Public Site</a>
        <a href="#" onclick="location.reload()" class="btn">🔄 Refresh</a>
      </div>
    </div>

    <?php if (isset($dbError)): ?>
      <div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:8px;margin:20px 0">
        ⚠️ Database Error: <?= htmlspecialchars($dbError) ?>
      </div>
    <?php endif; ?>

    <?php if (ModeHelper::isMock()): ?>
      <div style="background:#fff3cd;color:#856404;padding:15px;border-radius:8px;margin:20px 0">
        🧪 Mock Mode Active - showing sample data only
      </div>
    <?php endif; ?>

    <!-- Dashboard Stats -->
    <div class="dashboard-grid">
      <div class="dashboard-card">
        <h3>📧 Total Submissions</h3>
        <div class="number"><?= $totalSubmissions ?></div>
        <small>All time submissions</small>
      </div>
      
      <div class="dashboard-card">
        <h3>🛡️ Purchase Validation</h3>
        <div class="status-indicator <?= $purchaseValidation ? 'status-enabled' : 'status-disabled' ?>">
          <?= $purchaseValidation ? 'ENABLED' : 'DISABLED' ?>
        </div>
        <div><a href="envato.php" class="btn" style="margin-top:10px">Configure</a></div>
      </div>
      
      <div class="dashboard-card">
        <h3>🤖 AI Categorization</h3>
        <div class="status-indicator <?= $aiCategorization ? 'status-enabled' : 'status-disabled' ?>">
          <?= $aiCategorization ? 'ENABLED' : 'DISABLED' ?>
        </div>
        <div><a href="categories.php" class="btn" style="margin-top:10px">Configure</a></div>
      </div>
    </div>

    <!-- Categories Overview -->
    <?php if (!empty($categories)): ?>
      <div class="dashboard-card" style="margin:20px 0">
        <h3>📊 Categories Distribution</h3>
        <div class="category-list">
          <?php foreach ($categories as $cat => $count): ?>
            <span class="category-tag"><?= htmlspecialchars($cat) ?> (<?= $count ?>)</span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <div class="action-card">
        <h4>⚙️ Advanced Settings</h4>
        <p>Configure AI providers, license validation, email settings, and security options.</p>
        <a href="advanced_settings.php" class="btn primary">Advanced Settings</a>
      </div>
      
      <div class="action-card">
        <h4>🛡️ Envato Integration</h4>
        <p>Configure purchase code validation, API tokens, and allowed products.</p>
        <a href="envato.php" class="btn primary">Manage Envato Settings</a>
      </div>
      
      <div class="action-card">
        <h4>🏷️ Category Management</h4>
        <p>Set up categorization rules, enable AI assistance, and test message classification.</p>
        <a href="categories.php" class="btn primary">Manage Categories</a>
      </div>
      
      <div class="action-card">
        <h4>📧 Email Templates</h4>
        <p>Customize automated email responses and notification templates.</p>
        <a href="send_email.php" class="btn">Manage Emails</a>
      </div>
      
      <div class="action-card">
        <h4>📊 Export Data</h4>
        <p>Download submission data and analytics reports in CSV format.</p>
        <a href="export_csv.php" class="btn">Export CSV</a>
      </div>
      
      <div class="action-card">
        <h4>🔍 System Health</h4>
        <p>Monitor provider status, connection health, and system performance.</p>
        <a href="system_health.php" class="btn">View Health</a>
      </div>
    </div>

    <!-- Recent Submissions -->
    <div style="margin-top:30px">
      <h2>📋 Recent Submissions</h2>
      <?php include __DIR__.'/views/submissions-table.php'; ?>
    </div>
  </div>
</body>
</html>
