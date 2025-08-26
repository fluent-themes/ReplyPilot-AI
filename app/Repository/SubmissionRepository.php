
<?php namespace App\Repository;
use PDO;

class SubmissionRepository {
    protected $pdo;
    public function __construct(PDO $pdo){
        $this->pdo = $pdo;
    }
    public function save(array $data){
        $stmt = $this->pdo->prepare('INSERT INTO submissions
        (name,email,message,tone,purchase_code,product_name,category,ai_reply,created_at)
        VALUES (?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['message'],
            $data['tone'],
            ($data['purchase_code'] === '' ? null : $data['purchase_code']),
            $data['product_name'],
            $data['category'],
            $data['ai_reply'],
        ]);
        return (string)$this->pdo->lastInsertId();
    }
    
    public function findByRef(string $ref): ?array {
        // Validate ref is numeric to prevent type juggling issues
        if (!is_numeric($ref)) {
            return null;
        }
        $id = (int)$ref;
        $stmt = $this->pdo->prepare('SELECT * FROM submissions WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
?>
