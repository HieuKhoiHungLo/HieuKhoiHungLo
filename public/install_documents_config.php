<?php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    
    // Get all sessions
    $stmt = $db->query("SELECT id FROM dot_tuyen_sinh");
    $sessions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $docs = [
        [
            'ten' => 'Giấy chứng nhận kết quả thi tốt nghiệp THPT',
            'vals' => 'Bản gốc|Chưa nộp',
            'def' => 'Bản gốc',
            'req' => 1
        ],
        [
            'ten' => 'Học bạ THPT (bản gốc và bản sao)',
            'vals' => 'Đã nộp|Chưa nộp|Thiếu',
            'def' => 'Đã nộp',
            'req' => 1
        ]
    ];

    $insStmt = $db->prepare("INSERT INTO nhap_hoc_ho_so (session_id, ten_ho_so, cac_gia_tri, gia_tri_mac_dinh, bat_buoc, thu_tu) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($sessions as $sessionId) {
        // Clear old config
        $db->exec("DELETE FROM nhap_hoc_ho_so WHERE session_id = " . intval($sessionId));
        
        foreach ($docs as $i => $doc) {
            $insStmt->execute([
                $sessionId,
                $doc['ten'],
                $doc['vals'],
                $doc['def'],
                $doc['req'],
                $i + 1
            ]);
        }
        echo "Đã cấu hình cho đợt tuyển sinh ID: $sessionId\n";
    }
    
    echo "\nHoàn tất!\n";
} catch (\Exception $e) {
    die("Lỗi: " . $e->getMessage());
}
