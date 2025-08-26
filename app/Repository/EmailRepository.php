<?php namespace App\Repository;
use PDO;

class EmailRepository {
    protected PDO $pdo;
    public function __construct(PDO $pdo){ $this->pdo = $pdo; }

    public function logOutbound(int $submissionId, string $to, string $subject, string $body, string $status='sent', ?string $providerId=null, ?string $error=null): void {
        $stmt = $this->pdo->prepare("INSERT INTO emails (submission_id, direction, `to`, subject, body, sent_at, status, provider_message_id, error) VALUES (?,?,?,?,?, NOW(), ?, ?, ?)");
        $stmt->execute([$submissionId, 'outbound', $to, $subject, $body, $status, $providerId, $error]);
    }

    /**
     * @param int[] $submissionIds
     * @return array<int,array> map submission_id => array of rows
     */
    public function getBySubmissionIds(array $submissionIds): array {
        if (empty($submissionIds)) return [];
        $placeholders = implode(',', array_fill(0, count($submissionIds), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM emails WHERE submission_id IN ($placeholders) ORDER BY sent_at ASC, id ASC");
        $stmt->execute($submissionIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r){
            $sid = (int)$r['submission_id'];
            if (!isset($map[$sid])) $map[$sid] = [];
            $map[$sid][] = $r;
        }
        return $map;
    }
}
?>