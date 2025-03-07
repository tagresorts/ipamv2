<?php
// Function to load environment variables from a file
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        die("Missing .env file! Please create one from .env.example.");
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        list($name, $value) = explode("=", $line, 2);
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Load environment variables from the .env file
loadEnv(__DIR__ . '/.env');

// Ensure required variables are set
$requiredEnvVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($requiredEnvVars as $var) {
    if (!getenv($var)) {
        die("Error: Missing required environment variable $var in .env file.");
    }
}

// Database credentials from environment variables
$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
