
<?php namespace App\Installer;
class SqlSchema {
    public static function createTable(){
        return <<<SQL
CREATE TABLE IF NOT EXISTS submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190),
  email VARCHAR(190),
  message TEXT,
  tone VARCHAR(50),
  purchase_code VARCHAR(100) UNIQUE,
  product_name VARCHAR(190),
  category VARCHAR(50),
  ai_reply TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    }
}
?>
