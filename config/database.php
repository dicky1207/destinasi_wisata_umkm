<?php
// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Konfigurasi database - gunakan environment variables
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'wisata_umkm_production';
$user = getenv('DB_USER') ?: 'wisata_apps';
$pass = getenv('DB_PASS') ?: 'Iman@220423';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Set timezone setelah koneksi berhasil
    date_default_timezone_set('Asia/Jakarta');
    $pdo->exec("SET time_zone = '+07:00'");
    
} catch (\PDOException $e) {
    // Log error untuk production
    error_log("Database connection failed: " . $e->getMessage());
    
    // Tampilkan pesan error yang ramah pengguna
    if (getenv('APP_ENV') === 'production') {
        die("Terjadi gangguan sistem. Silakan coba lagi nanti.");
    } else {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}
?>