
<?php
require __DIR__.'/../bootstrap.php';
use App\Repository\SubmissionRepository;

$db = $GLOBALS['container']['db'];
$stmt = $db->query('SELECT * FROM submissions ORDER BY id DESC LIMIT 100');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__.'/views/submissions-table.php';
?>
