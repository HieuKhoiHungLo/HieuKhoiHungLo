<?php
/**
 * Migration: Tạo bảng mau_phieu để quản lý mẫu phiếu in Word
 * Chạy: D:\xampp\php\php.exe public/migrate_mau_phieu.php
 */
// Bypass for direct CLI execution
if (php_sapi_name() !== 'cli') {
    die('Chỉ chạy qua CLI!');
}

$dsn = 'pgsql:host=127.0.0.1;port=5433;dbname=tuyensinh_thv';
try {
    $pdo = new PDO($dsn, 'tuyensinh_app', 'Phutho2024@!', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die("Không kết nối được DB (cần mở SSH Tunnel trước): " . $e->getMessage() . "\n");
}

echo "=== MIGRATE: mau_phieu ===\n";

$pdo->exec("
    CREATE TABLE IF NOT EXISTS mau_phieu (
        id SERIAL PRIMARY KEY,
        ten_mau VARCHAR(200) NOT NULL,
        loai_mau VARCHAR(50) NOT NULL DEFAULT 'phieu_nhap_hoc',
        -- loai_mau: 'phieu_nhap_hoc' | 'giay_bao_trung_tuyen'
        ten_file VARCHAR(500),
        mo_ta TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        session_id INTEGER,
        created_by INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    COMMENT ON TABLE mau_phieu IS 'Bảng lưu trữ mẫu phiếu in Word (.docx)';
    COMMENT ON COLUMN mau_phieu.loai_mau IS 'phieu_nhap_hoc | giay_bao_trung_tuyen';
    COMMENT ON COLUMN mau_phieu.ten_file IS 'Đường dẫn tương đối trong storage/templates/';
    COMMENT ON COLUMN mau_phieu.session_id IS 'NULL = áp dụng cho mọi đợt';
");

echo "✅ Đã tạo bảng mau_phieu\n";
echo "✅ Migration hoàn thành!\n";
