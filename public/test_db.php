<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Đọc file .env bằng tay để lấy cấu hình kết nối thực tế
    $envFile = __DIR__ . '/../.env';
    $env = [];
    if (file_exists($envFile)) {
        foreach (file($envFile) as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#' || strpos($line, '=') === false) continue;
            list($k, $v) = explode('=', $line, 2);
            $env[trim($k)] = trim($v, " \t\n\r\"'");
        }
    }

    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '5432';
    $dbname = $env['DB_DATABASE'] ?? '';
    $user = $env['DB_USERNAME'] ?? '';
    $password = $env['DB_PASSWORD'] ?? '';

    echo "<h3>Đang kết nối tới: $host:$port (DB: $dbname, User: $user)</h3>";

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "<h2 style='color:green;'>SUCCESS: Kết nối từ Web đến CSDL thành công!</h2>";
    
    // Kiểm tra danh sách bảng trong schema public
    $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Danh sách bảng hiện có (" . count($tables) . " bảng):</h3>";
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "<h3 style='color:orange;'>CẢNH BÁO: Không tìm thấy bảng nào trong database! Bạn có thể chưa migrate hoặc import dữ liệu.</h3>";
    }
} catch (Exception $e) {
    echo "<h2 style='color:red;'>ERROR: Kết nối thất bại!</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    
    // In thêm thông tin các driver PDO đang được Web Server hỗ trợ
    echo "<h3>Các Driver PDO khả dụng trên Web Server:</h3>";
    echo "<pre>";
    print_r(PDO::getAvailableDrivers());
    echo "</pre>";
}
