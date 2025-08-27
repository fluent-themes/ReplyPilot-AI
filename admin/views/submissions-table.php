<?php
ob_start();

// compute base url for public form
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/'), '/');
$appBase = preg_replace('#/admin$#', '', $scriptDir);
$publicUrl = $scheme . '://' . $host . $appBase . '/public/index.php';
?>
<form method="get" action="./" id="search-form" style="margin:12px 0; display:flex; gap:8px; align-items:center">
  <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Search name, email, product, message..." style="flex:1; padding:6px 8px">
  <button class="btn" type="submit">Search</button>
  <?php if (!empty($_GET['q'])): ?><a class="btn ghost" href="./">Clear</a><?php endif; ?>
</form>

<div style="margin:16px 0;padding:12px;border:1px solid #ddd;border-radius:6px">
  <h2 style="margin:0 0 8px;">Embed the Customer Support Form</h2>
  <details>
    <summary style="cursor:pointer">Iframe embed (easy)</summary>
    <pre style="white-space:pre-wrap;border:1px solid #eee;padding:8px;border-radius:4px;margin-top:8px">&lt;iframe src="<?= htmlspecialchars($publicUrl) ?>" width="100%" height="700" frameborder="0"&gt;&lt;/iframe&gt;</pre>
  </details>
  <details style="margin-top:8px">
    <summary style="cursor:pointer">Direct HTML form (posts to your app)</summary>
<pre style="white-space:pre-wrap;border:1px solid #eee;padding:8px;border-radius:4px;margin-top:8px">&lt;form method="post" action="<?= htmlspecialchars($publicUrl) ?>" accept-charset="utf-8" style="max-width:720px;margin:0 auto;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif"&gt;
  &lt;label style="display:block;margin:8px 0"&gt;Name
    &lt;input name="name" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px"&gt;
  &lt;/label&gt;
  &lt;label style="display:block;margin:8px 0"&gt;Email
    &lt;input type="email" name="email" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px"&gt;
  &lt;/label&gt;
  &lt;label style="display:block;margin:8px 0"&gt;Message
    &lt;textarea name="message" required rows="6" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px"&gt;&lt;/textarea&gt;
  &lt;/label&gt;
  &lt;label style="display:block;margin:8px 0"&gt;Product Name
    &lt;input name="product_name" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px"&gt;
  &lt;/label&gt;
  &lt;label style="display:block;margin:8px 0"&gt;Tone
    &lt;select name="tone" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px"&gt;
      &lt;option value="friendly"&gt;Friendly&lt;/option&gt;
      &lt;option value="professional"&gt;Professional&lt;/option&gt;
    &lt;/select&gt;
  &lt;/label&gt;
  &lt;label style="display:block;margin:8px 0"&gt;Purchase Code (optional)
    &lt;input name="purchase_code" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px"&gt;
  &lt;/label&gt;
  &lt;button type="submit" style="display:inline-block;padding:10px 16px;border:0;border-radius:6px;cursor:pointer"&gt;Send&lt;/button&gt;
&lt;/form&gt;</pre>
  </details>

  <div style="margin-top:12px">
    <a href="export_csv.php" class="btn" style="display:inline-block;padding:8px 12px;border:1px solid #ccc;border-radius:4px;text-decoration:none">Export CSV</a>
  </div>
</div>

<table style="width:100%;border-collapse:collapse">
<thead>
<tr>
  <th style="text-align:left;border-bottom:1px solid #ddd">ID</th>
  <th style="text-align:left;border-bottom:1px solid #ddd">Name</th>
  <th style="text-align:left;border-bottom:1px solid #ddd">Email</th>
  <th style="text-align:left;border-bottom:1px solid #ddd">Category</th>
  <th style="text-align:left;border-bottom:1px solid #ddd">Product</th>
  <th style="text-align:left;border-bottom:1px solid #ddd">Date</th>
  <th style="text-align:left;border-bottom:1px solid #ddd">Action</th>
</tr>
</thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr id="row-<?= (int)$r['id'] ?>">
  <td><?= (int)$r['id'] ?></td>
  <td><?= htmlspecialchars($r['name'] ?? '') ?></td>
  <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
  <td><?= htmlspecialchars($r['category'] ?? '') ?></td>
  <td><?= htmlspecialchars($r['product_name'] ?? '') ?></td>
  <td><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
  <td><button type="button" onclick="toggleDetails(<?= (int)$r['id'] ?>)">View</button></td>
</tr>
<tr id="details-<?= (int)$r['id'] ?>" style="display:none">
  <td colspan="7" style="background:#fafafa;border:1px solid #eee">
    <div style="display:flex;gap:20px;flex-wrap:wrap">
      <div style="flex:1;min-width:280px">
        <h3 style="margin:8px 0">Full Message</h3>
        <pre style="white-space:pre-wrap;border:1px solid #eee;padding:8px;border-radius:4px"><?= htmlspecialchars($r['message'] ?? '') ?></pre>

        <h3 style="margin:8px 0">AI Reply (Preview)</h3>
        <pre style="white-space:pre-wrap;border:1px solid #eee;padding:8px;border-radius:4px" id="ai-reply-<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['ai_reply'] ?? '') ?></pre>
      </div>

      <div style="flex:1;min-width:280px">
        <h3 style="margin:8px 0">Reply via Email</h3>
        <form method="post" action="send_email.php" style="margin-bottom:12px">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <label>To<br><input name="to" value="<?= htmlspecialchars($r['email'] ?? '') ?>" style="width:100%"></label><br>
          <label>Subject<br><input name="subject" value="Re: Your message to <?= htmlspecialchars($r['product_name'] ?? '') ?>" style="width:100%"></label><br>
          <label>Body<br><textarea name="body" rows="8" style="width:100%"><?= htmlspecialchars($r['ai_reply'] ?? '') ?></textarea></label><br>
          <button type="submit">Send Email</button>
        </form>

        <h3 style="margin:8px 0">Edit AI Reply / Category</h3>
        <form method="post" action="update_reply.php" id="edit-form-<?= (int)$r['id'] ?>">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <label>Category<br>
            <select name="category" style="width:100%">
              <?php
                $cats = ['Support','Sales','Spam'];
                $current = (string)($r['category'] ?? '');
                foreach ($cats as $c) {
                    $sel = ($c === $current) ? 'selected' : '';
                    echo "<option value=\"$c\" $sel>$c</option>";
                }
              ?>
            </select>
          </label><br>
          <label>AI Reply<br>
            <textarea name="ai_reply" id="ai-reply-input-<?= (int)$r['id'] ?>" rows="10" style="width:100%"><?= htmlspecialchars($r['ai_reply'] ?? '') ?></textarea>
          </label><br>

          <input type="hidden" name="send" id="send-flag-<?= (int)$r['id'] ?>" value="0">
          <input type="hidden" name="to" value="<?= htmlspecialchars($r['email'] ?? '') ?>">
          <input type="hidden" name="subject" value="Re: Your message to <?= htmlspecialchars($r['product_name'] ?? '') ?>">
          <input type="hidden" name="body" id="body-hidden-<?= (int)$r['id'] ?>" value="<?= htmlspecialchars($r['ai_reply'] ?? '', ENT_QUOTES) ?>">

          <button type="submit">Save</button>
          <button type="button" onclick="saveAndEmail(<?= (int)$r['id'] ?>)">Save &amp; Email</button>
        </form>
      </div>
    </div>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<script>
function toggleDetails(id){
  var el = document.getElementById('details-' + id);
  if(!el) return;
  el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'table-row' : 'none';
}
function saveAndEmail(id){
  // copy current input value into hidden body field
  var text = document.getElementById('ai-reply-input-' + id).value;
  document.getElementById('body-hidden-' + id).value = text;
  document.getElementById('send-flag-' + id).value = '1';
  document.getElementById('edit-form-' + id).submit();
}
</script>
<?php $content = ob_get_clean(); include __DIR__.'/layout.php'; ?>
