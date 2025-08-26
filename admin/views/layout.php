<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ReplyPilot Admin</title>
  <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
<div style="padding:8px 12px;border-bottom:1px solid #eee"><a href="/admin/settings.php">Settings</a></div>
  <div class="header">
    <div class="brand">ReplyPilot Admin</div>
    <div class="toolbar">
      <a class="btn ghost sm" href="/admin/">Home</a>
      <a class="btn sm" href="/admin/export_csv.php">Export CSV</a>
      <!-- Example dropdown (commented)
      <div class="dropdown">
        <button type="button" class="btn sm dropdown-toggle">Actions ▾</button>
        <div class="dropdown-menu" hidden>
          <a href="#">Bulk: Mark as Support</a>
          <a href="#">Bulk: Mark as Sales</a>
          <a href="#">Bulk: Mark as Spam</a>
        </div>
      </div>
      -->
    </div>
  </div>

  <main class="container">
    <!-- Example notice (commented)
    <div class="note info">Tip: You can export CSV and filter later in Excel.</div>
    -->
    <div class="card">
      <div class="card-body">
        <?= $content ?? '' ?>
      </div>
    </div>
  </main>

  <!-- Toast container -->
  <div id="toast" class="toast" role="status" aria-live="polite"></div>

  <!-- Example Modal (commented)
  <div class="modal-backdrop" id="exampleModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="ex-title">
      <div class="modal-header">
        <span id="ex-title" class="modal-title">Example Modal</span>
        <button class="modal-close" type="button" data-modal-close>✕</button>
      </div>
      <div class="modal-body">
        <p>This is a demo modal. Put any content here.</p>
      </div>
      <div class="modal-footer">
        <button class="btn ghost" type="button" data-modal-close>Cancel</button>
        <button class="btn primary" type="button" data-modal-close>OK</button>
      </div>
    </div>
  </div>
  -->

  <!-- Example Sticky Actions (commented)
  <div class="sticky-actions show" data-sticky-for="#someFormId">
    <div class="inner">
      <button class="btn ghost sm" type="reset" form="someFormId">Reset</button>
      <button class="btn primary sm" type="submit" form="someFormId">Save</button>
    </div>
  </div>
  -->

  <script src="/admin/assets/js/admin.js" defer></script>
  <script src="/admin/assets/js/ui.js" defer></script>
  <script src="/admin/assets/js/ux.js" defer></script>
</body>
</html>
