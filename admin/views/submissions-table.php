
<?php ob_start(); ?>
<table>
<thead>
<tr>
  <th>ID</th><th>Name</th><th>Email</th><th>Category</th><th>Product</th><th>Date</th>
</tr>
</thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><?= $r['id'] ?></td>
  <td><?= htmlspecialchars($r['name']) ?></td>
  <td><?= htmlspecialchars($r['email']) ?></td>
  <td><?= htmlspecialchars($r['category']) ?></td>
  <td><?= htmlspecialchars($r['product_name']) ?></td>
  <td><?= $r['created_at'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php $content = ob_get_clean(); include __DIR__.'/layout.php'; ?>
