<?php namespace App\Installer;
class SqlSchema {
    public static function createTable(){
        // Backward-compat for older callers
        return self::createSubmissionsTable();
    }

    public static function createSubmissionsTable(){
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

    public static function createEmailsTable(){
        return <<<SQL
CREATE TABLE IF NOT EXISTS emails (
  id INT AUTO_INCREMENT PRIMARY KEY,
  submission_id INT NOT NULL,
  direction ENUM('outbound','inbound') DEFAULT 'outbound',
  `to` VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  sent_at TIMESTAMP NULL DEFAULT NULL,
  status ENUM('sent','failed') DEFAULT 'sent',
  provider_message_id VARCHAR(190) NULL,
  error VARCHAR(255) NULL,
  CONSTRAINT fk_emails_submission FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    }

    public static function createAnalyticsTable(){
        return <<<SQL
CREATE TABLE IF NOT EXISTS ai_analytics (
  id int AUTO_INCREMENT PRIMARY KEY,
  provider varchar(50) NOT NULL,
  model varchar(100) NOT NULL,
  message_length int DEFAULT 0,
  response_length int DEFAULT 0,
  tokens_used int DEFAULT 0,
  response_time decimal(8,3) DEFAULT 0.000,
  category varchar(50) DEFAULT 'Support',
  confidence decimal(3,2) DEFAULT 0.00,
  cached boolean DEFAULT false,
  tone varchar(20) DEFAULT 'friendly',
  product_name varchar(255) DEFAULT '',
  success boolean DEFAULT true,
  error_message text NULL,
  created_at datetime NOT NULL,
  
  KEY idx_created_at (created_at),
  KEY idx_provider (provider),
  KEY idx_success (success),
  KEY idx_cached (cached),
  KEY idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    }

    public static function createLicenseAnalyticsTable(){
        return <<<SQL
CREATE TABLE IF NOT EXISTS license_analytics (
  id int AUTO_INCREMENT PRIMARY KEY,
  validator varchar(50) NOT NULL,
  code_length int DEFAULT 0,
  validation_time decimal(8,3) DEFAULT 0.000,
  success boolean DEFAULT false,
  error_message text NULL,
  product_name varchar(255) DEFAULT '',
  created_at datetime NOT NULL,
  
  KEY idx_created_at (created_at),
  KEY idx_validator (validator),
  KEY idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    }

    public static function createResponseCacheTable(){
        return <<<SQL
CREATE TABLE IF NOT EXISTS response_cache (
  id int AUTO_INCREMENT PRIMARY KEY,
  message text NOT NULL,
  message_hash varchar(64) NOT NULL,
  tone varchar(20) NOT NULL,
  product_name varchar(255) NOT NULL,
  reply text NOT NULL,
  category varchar(50) NOT NULL,
  confidence decimal(3,2) DEFAULT 0.00,
  tokens_used int DEFAULT 0,
  hit_count int DEFAULT 1,
  expires_at datetime NOT NULL,
  created_at datetime NOT NULL,
  last_accessed datetime NULL,
  
  KEY idx_hash_tone_product (message_hash, tone, product_name),
  KEY idx_expires (expires_at),
  KEY idx_product_tone (product_name, tone),
  KEY idx_hit_count (hit_count),
  
  UNIQUE KEY unique_cache (message_hash, tone, product_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }

    public static function createPerformanceAnalyticsTable(){
        return <<<SQL
CREATE TABLE IF NOT EXISTS performance_analytics (
  id int AUTO_INCREMENT PRIMARY KEY,
  endpoint varchar(100) NOT NULL,
  response_time decimal(8,3) DEFAULT 0.000,
  memory_usage bigint DEFAULT 0,
  cpu_usage decimal(5,2) DEFAULT 0.00,
  requests_per_minute int DEFAULT 0,
  errors_per_minute int DEFAULT 0,
  created_at datetime NOT NULL,
  
  KEY idx_created_at (created_at),
  KEY idx_endpoint (endpoint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    }

    /**
     * Create all tables
     */
    public static function createAllTables(){
        return [
            self::createSubmissionsTable(),
            self::createEmailsTable(),
            self::createAnalyticsTable(),
            self::createLicenseAnalyticsTable(),
            self::createResponseCacheTable(),
            self::createPerformanceAnalyticsTable()
        ];
    }
}
?>