<?php
/**
 * Test Bootstrap File
 * 
 * This file sets up the testing environment for ReplyPilot AI.
 * It can work with or without PHPUnit installed.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set('UTC');

// Define test constants
define('TESTING', true);
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'app');
define('STORAGE_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'storage');
define('TEST_PATH', __DIR__);

// Load environment for testing if .env.testing exists
$envTestFile = BASE_PATH . DIRECTORY_SEPARATOR . '.env.testing';
if (file_exists($envTestFile)) {
    $_ENV['APP_ENV'] = 'testing';
}

// Try to load Composer autoloader if available
$composerAutoload = BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
    echo "✓ Composer autoloader loaded\n";
} else {
    // Fallback to manual autoloading
    spl_autoload_register(function ($class) {
        // Convert namespace to file path
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        
        // Check in app directory
        $appFile = APP_PATH . DIRECTORY_SEPARATOR . $file;
        if (file_exists($appFile)) {
            require_once $appFile;
            return;
        }
        
        // Check in tests directory
        $testFile = TEST_PATH . DIRECTORY_SEPARATOR . $file;
        if (file_exists($testFile)) {
            require_once $testFile;
            return;
        }
    });
    echo "✓ Custom autoloader registered\n";
}

// Load the main bootstrap if not already loaded
if (!defined('APP_LOADED')) {
    $mainBootstrap = BASE_PATH . DIRECTORY_SEPARATOR . 'bootstrap.php';
    if (file_exists($mainBootstrap)) {
        require_once $mainBootstrap;
        echo "✓ Main bootstrap loaded\n";
    }
}

// Simple test runner if PHPUnit is not available
if (!class_exists('PHPUnit\Framework\TestCase')) {
    echo "ℹ PHPUnit not found. Using simple test runner.\n\n";
    
    /**
     * Simple TestCase class for when PHPUnit is not installed
     */
    class SimpleTestCase {
        protected $assertions = 0;
        protected $failures = [];
        
        public function assertEquals($expected, $actual, $message = '') {
            $this->assertions++;
            if ($expected !== $actual) {
                $this->failures[] = $message ?: "Expected '$expected' but got '$actual'";
                return false;
            }
            return true;
        }
        
        public function assertTrue($condition, $message = '') {
            $this->assertions++;
            if (!$condition) {
                $this->failures[] = $message ?: "Expected true but got false";
                return false;
            }
            return true;
        }
        
        public function assertFalse($condition, $message = '') {
            $this->assertions++;
            if ($condition) {
                $this->failures[] = $message ?: "Expected false but got true";
                return false;
            }
            return true;
        }
        
        public function assertNotNull($value, $message = '') {
            $this->assertions++;
            if ($value === null) {
                $this->failures[] = $message ?: "Expected non-null value";
                return false;
            }
            return true;
        }
        
        public function run() {
            $methods = get_class_methods($this);
            $testMethods = array_filter($methods, function($method) {
                return strpos($method, 'test') === 0;
            });
            
            $results = [
                'tests' => 0,
                'passed' => 0,
                'failed' => 0,
                'assertions' => 0
            ];
            
            foreach ($testMethods as $method) {
                $this->assertions = 0;
                $this->failures = [];
                
                try {
                    // Run setUp if exists
                    if (method_exists($this, 'setUp')) {
                        $this->setUp();
                    }
                    
                    // Run test method
                    $this->$method();
                    
                    // Run tearDown if exists
                    if (method_exists($this, 'tearDown')) {
                        $this->tearDown();
                    }
                    
                    $results['tests']++;
                    if (empty($this->failures)) {
                        $results['passed']++;
                        echo "✓ $method\n";
                    } else {
                        $results['failed']++;
                        echo "✗ $method\n";
                        foreach ($this->failures as $failure) {
                            echo "  - $failure\n";
                        }
                    }
                } catch (Exception $e) {
                    $results['tests']++;
                    $results['failed']++;
                    echo "✗ $method - Exception: " . $e->getMessage() . "\n";
                }
                
                $results['assertions'] += $this->assertions;
            }
            
            return $results;
        }
    }
    
    // Run example test if executed directly
    if (basename($_SERVER['SCRIPT_NAME']) === 'bootstrap.php') {
        echo "Running example tests...\n";
        echo "========================\n\n";
        
        // Try to load and run ExampleTest
        $exampleTest = TEST_PATH . DIRECTORY_SEPARATOR . 'ExampleTest.php';
        if (file_exists($exampleTest)) {
            require_once $exampleTest;
            if (class_exists('ExampleTest')) {
                $test = new ExampleTest();
                $results = $test->run();
                
                echo "\n========================\n";
                echo "Test Results:\n";
                echo "Tests: {$results['tests']}\n";
                echo "Passed: {$results['passed']}\n";
                echo "Failed: {$results['failed']}\n";
                echo "Assertions: {$results['assertions']}\n";
                
                exit($results['failed'] > 0 ? 1 : 0);
            }
        } else {
            echo "No tests found. Create ExampleTest.php to get started.\n";
        }
    }
} else {
    echo "✓ PHPUnit detected\n";
}

echo "✓ Test environment ready\n\n";
