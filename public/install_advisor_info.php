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
    $pdo = \App\Core\Database::getInstance()->getConnection();
    
    // 1. Add column if not exists
    $pdo->exec("ALTER TABLE dm_nganh ADD COLUMN IF NOT EXISTS thong_tin_gv_ho_tro VARCHAR(255) DEFAULT NULL;");

    
    // 2. Map of majors to advisor info
    $map = [
        'Điều dưỡng' => 'Thầy Liêm: 0979.590.577',
        'Sư phạm Toán học' => 'Thầy Tứ: 0982.513.664',
        'Sư phạm Khoa học Tự nhiên' => 'Cô Yến: 0941.914.394',
        'Sư phạm KHTN' => 'Cô Yến: 0941.914.394',
        'Kế toán' => 'Cô Chi: 038.505.7216',
        'Quản trị kinh doanh' => 'Cô Hương: 0984.591.258',
        'Tài chính - Ngân hàng' => 'Cô Thảo: 0389.980.931',
        'Kinh tế' => 'Cô My: 0975.139.821',
        'Quản trị Dịch vụ Du lịch và Lữ hành' => 'Cô Giang: 0969.056.858',
        'Quản trị DV DL & LH' => 'Cô Giang: 0969.056.858',
        'Sư phạm Ngữ văn' => 'Cô Nhàn: 0968.098.220',
        'Du lịch' => 'Cô Loan: 0975.122.182',
        'Sư phạm Lịch sử - Địa lý' => 'Cô Liên: 0969.658.488',
        'Giáo dục Tiểu học' => 'Cô Điệp: 0986.605.189',
        'Giáo dục Mầm non' => 'Cô Yến: 0971.399.219',
        'Công tác xã hội' => 'Cô Dương: 0979.962.009',
        'Tâm lý học' => 'Cô Nga: 0388.804.792',
        'Sư phạm Tiếng Anh' => 'Cô Nhung: 0329.257.280',
        'Ngôn ngữ Anh' => 'Cô Mai: 0336.621.542',
        'Ngôn ngữ Trung Quốc' => 'Thầy Kiên: 0988.249.993, Cô Thuý: 0913.838.878',
        'Chăn nuôi' => 'Cô Yến: 0983.028.016',
        'Thú y' => 'Cô Yến: 0983.028.016',
        'Khoa học Cây trồng' => 'Cô Mỹ: 0984.977.084',
        'Công nghệ thông tin' => 'Cô Tài: 0976.945.061',
        'Công nghệ kỹ thuật điện, điện tử' => 'Thầy Hoà: 0987.155.353',
        'Công nghệ KT Cơ khí' => 'Thầy Luân: 0975.366.128',
        'Công nghệ kỹ thuật cơ khí' => 'Thầy Luân: 0975.366.128',
        'Sư phạm Âm nhạc' => 'Cô Hiền: 0985.525.024',
        'Giáo dục Thể chất' => 'Thầy Điệp: 0915.504.660',
        'Sư phạm Mỹ thuật' => 'Thầy Tuyển: 0358.165.468'
    ];

    $stmt = $pdo->prepare("UPDATE dm_nganh SET thong_tin_gv_ho_tro = :gv WHERE ten_nganh = :ten OR ten_nganh LIKE :likeTen");
    
    $updated = 0;
    foreach ($map as $nganh => $gv) {
        $stmt->execute([
            ':gv' => $gv,
            ':ten' => $nganh,
            ':likeTen' => '%' . $nganh . '%'
        ]);
        $updated += $stmt->rowCount();
    }
    
    echo "Updated successfully. Rows affected: " . $updated . "\n";
    
} catch (\Exception $e) {
    die("Error: " . $e->getMessage());
}
