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
    
    // Get first session
    $stmt = $db->query("SELECT id FROM dot_tuyen_sinh ORDER BY id DESC LIMIT 1");
    $sessionId = $stmt->fetchColumn();
    
    if (!$sessionId) {
        die("Chưa có đợt tuyển sinh nào!");
    }

    echo "<pre>Cấu hình hồ sơ cho đợt tuyển sinh ID: $sessionId\n";
    
    // Clear old config
    $db->exec("DELETE FROM nhap_hoc_ho_so WHERE session_id = " . intval($sessionId));
    
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
        ],
        [
            'ten' => 'Giấy báo trúng tuyển',
            'vals' => 'Đã nộp|Chưa nộp',
            'def' => 'Đã nộp',
            'req' => 1
        ],
        [
            'ten' => 'Bằng tốt nghiệp THPT / GCN TN tạm thời',
            'vals' => 'Bản sao|Chưa nộp',
            'def' => 'Bản sao',
            'req' => 1
        ],
        [
            'ten' => 'Giấy khai sinh (bản sao chứng thực)',
            'vals' => 'Bản sao|Chưa nộp',
            'def' => 'Bản sao',
            'req' => 1
        ],
        [
            'ten' => 'Lý lịch sinh viên (theo mẫu)',
            'vals' => 'Đã nộp|Chưa nộp',
            'def' => 'Đã nộp',
            'req' => 1
        ],
        [
            'ten' => 'Căn cước công dân (02 bản sao)',
            'vals' => 'Đã nộp|Thiếu|Chưa nộp',
            'def' => 'Đã nộp',
            'req' => 1
        ],
        [
            'ten' => 'Ảnh 3x4 (04 ảnh)',
            'vals' => 'Đã nộp|Thiếu|Chưa nộp',
            'def' => 'Đã nộp',
            'req' => 1
        ],
        [
            'ten' => 'Giấy tờ chứng nhận ưu tiên (nếu có)',
            'vals' => 'Đã nộp|Không có',
            'def' => 'Không có',
            'req' => 0
        ],
        [
            'ten' => 'Chứng chỉ ngoại ngữ quốc tế (nếu có)',
            'vals' => 'Đã nộp|Không có',
            'def' => 'Không có',
            'req' => 0
        ],
        [
            'ten' => 'Giấy ĐK nghĩa vụ quân sự (Đối với Nam)',
            'vals' => 'Đã nộp|Không có|Chưa nộp',
            'def' => 'Không có',
            'req' => 0
        ]
    ];

    $stmt = $db->prepare("INSERT INTO nhap_hoc_ho_so (session_id, ten_ho_so, cac_gia_tri, gia_tri_mac_dinh, bat_buoc, thu_tu) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($docs as $i => $doc) {
        $stmt->execute([
            $sessionId,
            $doc['ten'],
            $doc['vals'],
            $doc['def'],
            $doc['req'],
            $i + 1
        ]);
        echo "Đã thêm: {$doc['ten']}\n";
    }
    
    echo "\nHoàn tất!</pre>";
} catch (\Exception $e) {
    die("Lỗi: " . $e->getMessage());
}
