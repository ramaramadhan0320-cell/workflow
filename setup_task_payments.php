<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment
if (! empty(getenv('CI_ENVIRONMENT'))) {
    define('ENVIRONMENT', getenv('CI_ENVIRONMENT'));
} else {
    define('ENVIRONMENT', 'development');
}

// Load config
require_once __DIR__ . '/app/Config/Database.php';

$config = new \App\Config\Database();

// Get the db config
$db_config = $config->default;

// Create connection
try {
    $mysqli = new mysqli(
        $db_config['hostname'],
        $db_config['username'],
        $db_config['password'],
        $db_config['database']
    );

    if ($mysqli->connect_error) {
        die('❌ Connection failed: ' . $mysqli->connect_error);
    }

    echo "✅ Connected to database\n";

    // Create task_payments table
    $sql = "CREATE TABLE IF NOT EXISTS `task_payments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `task_id` INT NOT NULL,
        `amount` DECIMAL(15, 2) NOT NULL,
        `payment_method` ENUM('transfer', 'cash', 'e-wallet') DEFAULT 'transfer',
        `status` ENUM('unpaid', 'paid') DEFAULT 'unpaid',
        `payment_date` DATE DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
    )";

    if ($mysqli->query($sql)) {
        echo "✅ task_payments table created successfully!\n";
    } else {
        echo "❌ Error creating table: " . $mysqli->error . "\n";
    }

    $mysqli->close();

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✨ Setup completed!\n";
