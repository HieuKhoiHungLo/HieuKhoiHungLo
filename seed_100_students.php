<?php
/**
 * seed_100_students.php
 * Seed 100 random student records with correct schema.
 * Tables: thi_sinh, ho_so_xet_tuyen, nguyen_vong, ket_qua_hoc_tap, diem_thi_thpt
 */
require_once __DIR__ . '/vendor/autoload.php';
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});
require_once __DIR__ . '/app/Helpers/functions.php';
try { (new App\Core\DotEnv(__DIR__ . '/.env'))->load(); } catch (\Exception $e) {}

$db = App\Core\Database::getInstance()->getConnection();

// ==================== DATA POOLS ====================
$ho = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý', 'Phan', 'Mai', 'Lưu', 'Tạ', 'Đinh'];
$dem_nam = ['Văn', 'Đức', 'Minh', 'Quốc', 'Hữu', 'Công', 'Thành', 'Đình', 'Xuân', 'Tiến'];
$dem_nu = ['Thị', 'Ngọc', 'Phương', 'Thu', 'Thanh', 'Kim', 'Bích', 'Diệu', 'Minh', 'Thùy'];
$ten_nam = ['Hùng', 'Dũng', 'Tuấn', 'Minh', 'Đức', 'Thắng', 'Long', 'Quân', 'Phong', 'Bình', 'Tùng', 'Khoa', 'Sơn', 'Kiên', 'Trung', 'Hải', 'Khánh', 'Nghĩa', 'Huy', 'Phúc'];
$ten_nu = ['Hương', 'Linh', 'Trang', 'Hà', 'Lan', 'Mai', 'Ngọc', 'Yến', 'Thảo', 'Hạnh', 'Nhi', 'Vy', 'Chi', 'Trâm', 'Anh', 'Dung', 'Giang', 'Hiền', 'Ly', 'Quỳnh'];
$dan_toc = ['Kinh', 'Kinh', 'Kinh', 'Kinh', 'Tày', 'Thái', 'Mường', 'Nùng', 'Dao'];
$ma_tinh = ['25', '12', '01', '10', '11', '14', '15', '19', '17', '22', '24', '26'];
$statuses = ['Chờ duyệt', 'Chờ duyệt', 'Chờ duyệt', 'Đã nộp']; // Mostly Chờ duyệt for testing review
$domains = ['gmail.com', 'yahoo.com', 'outlook.com'];
$hoc_luc = ['Giỏi', 'Khá', 'Trung bình', 'Yếu'];
$hanh_kiem = ['Tốt', 'Khá', 'Trung bình'];

// Nganh from DB
$nganh_list = [];
try {
    $stmt = $db->query("SELECT ma_nganh, ten_nganh FROM dm_nganh LIMIT 20");
    $nganh_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {}
if (empty($nganh_list)) {
    $nganh_list = [
        ['ma_nganh' => '7140247', 'ten_nganh' => 'Sư phạm Khoa học tự nhiên'],
        ['ma_nganh' => '7140202', 'ten_nganh' => 'Giáo dục Tiểu học'],
        ['ma_nganh' => '7340101', 'ten_nganh' => 'Quản trị kinh doanh'],
        ['ma_nganh' => '7480201', 'ten_nganh' => 'Công nghệ thông tin'],
        ['ma_nganh' => '7140231', 'ten_nganh' => 'Sư phạm Tiếng Anh'],
        ['ma_nganh' => '7620110', 'ten_nganh' => 'Khoa học cây trồng'],
    ];
}

$to_hop_list = ['A00', 'A01', 'B00', 'C00', 'D01', 'D07', 'C01', 'C02'];
$phuong_thuc = [
    ['ma' => '100', 'ten' => 'Xét kết quả thi tốt nghiệp THPT'],
    ['ma' => '200', 'ten' => 'Xét học bạ THPT'],
    ['ma' => '300', 'ten' => 'Xét tuyển thẳng'],
];

// ==================== HELPERS ====================
function pick($arr) { return $arr[array_rand($arr)]; }
function rf($min, $max) { return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 2); }
function noAccent($s) {
    $s = mb_strtolower($s);
    $tr = ['à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a','â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','đ'=>'d','è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e','ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i','ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o','ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o','ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u','ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y'];
    return strtr($s, $tr);
}

// ==================== GENERATE ====================
$count = 100;
$matKhau = password_hash('Test@123', PASSWORD_BCRYPT);
$now = date('Y-m-d H:i:s');

echo "Generating $count students...\n";
$db->beginTransaction();

try {
    for ($i = 1; $i <= $count; $i++) {
        $male = rand(0, 1);
        $hoTen = pick($ho) . ' ' . pick($male ? $dem_nam : $dem_nu) . ' ' . pick($male ? $ten_nam : $ten_nu);
        $cccd = '0' . str_pad(rand(10, 96), 2, '0', STR_PAD_LEFT) . '0' . ($male ? '0' : '1') . '4' . str_pad(200000 + $i, 6, '0', STR_PAD_LEFT);
        $ngaySinh = (2004 + rand(0, 4)) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $sdt = '0' . rand(3, 9) . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $firstName = noAccent(trim(substr($hoTen, strrpos($hoTen, ' ') + 1)));
        $lastName = noAccent(trim(substr($hoTen, 0, strpos($hoTen, ' '))));
        $email = $firstName . '.' . $lastName . rand(10, 99) . '@' . pick($domains);
        $maTinh = pick($ma_tinh);
        $status = pick($statuses);
        $namTN = rand(2024, 2026);

        // 1. INSERT thi_sinh
        $doiTuong = rand(0, 5) > 3 ? '0' . rand(1, 7) : null;
        $daDu6Ky = rand(0, 1) ? true : false;
        $coChungChi = rand(0, 4) === 0 ? true : false;
        $kvUuTien = pick(['KV1', 'KV2', 'KV2-NT', 'KV3']);
        $truongData = !empty($truong_list) ? pick($truong_list) : null;
        $maTruong = $truongData ? $truongData['ma_truong'] : null;
        $diaChi = 'Xã ' . rand(1, 30) . ', Huyện ' . rand(1, 15);
        $gioiTinh = $male ? 'Nam' : 'Nữ';
        $dtoc = pick($dan_toc);

        $stmtTS = $db->prepare("INSERT INTO thi_sinh (
            so_cccd, ho_va_ten, ngay_sinh, gioi_tinh, dan_toc,
            dien_thoai, email, mat_khau,
            khu_vuc_uu_tien, doi_tuong_uu_tien,
            ma_tinh_thuong_tru, dia_chi_chi_tiet,
            ma_tinh_lop_12, ma_truong_lop_12,
            ma_tinh_ho_khau, nam_tot_nghiep,
            da_du_6_ky, co_chung_chi_qt,
            is_custom_kv, is_custom_dt,
            nguon_du_lieu, ngay_tao
        ) VALUES (
            :cccd, :hoten, :ngaysinh, :gioitinh, :dantoc,
            :sdt, :email, :matkhau,
            :khuvuc, :doituong,
            :matinh1, :diachi,
            :matinh2, :truong,
            :matinhxk, :namtn,
            :dadu6ky, :cochungchi,
            :customkv, :customdt,
            'seeded', NOW()
        )");
        $stmtTS->bindValue(':cccd', $cccd);
        $stmtTS->bindValue(':hoten', $hoTen);
        $stmtTS->bindValue(':ngaysinh', $ngaySinh);
        $stmtTS->bindValue(':gioitinh', $gioiTinh);
        $stmtTS->bindValue(':dantoc', $dtoc);
        $stmtTS->bindValue(':sdt', $sdt);
        $stmtTS->bindValue(':email', $email);
        $stmtTS->bindValue(':matkhau', $matKhau);
        $stmtTS->bindValue(':khuvuc', $kvUuTien);
        $stmtTS->bindValue(':doituong', $doiTuong);
        $stmtTS->bindValue(':matinh1', $maTinh);
        $stmtTS->bindValue(':diachi', $diaChi);
        $stmtTS->bindValue(':matinh2', $maTinh);
        $stmtTS->bindValue(':truong', $maTruong);
        $stmtTS->bindValue(':matinhxk', $maTinh);
        $stmtTS->bindValue(':namtn', $namTN, PDO::PARAM_INT);
        $stmtTS->bindValue(':dadu6ky', $daDu6Ky, PDO::PARAM_BOOL);
        $stmtTS->bindValue(':cochungchi', $coChungChi, PDO::PARAM_BOOL);
        $stmtTS->bindValue(':customkv', false, PDO::PARAM_BOOL);
        $stmtTS->bindValue(':customdt', false, PDO::PARAM_BOOL);
        $stmtTS->execute();

        // 2. INSERT ho_so_xet_tuyen
        $db->prepare("INSERT INTO ho_so_xet_tuyen (
            so_cccd, dot_tuyen_sinh_id, trang_thai, created_at, updated_at
        ) VALUES (?, 3, ?, NOW(), NOW())")->execute([$cccd, $status]);

        // 3. INSERT nguyen_vong (1-3)
        $numNV = rand(1, 3);
        $usedNganh = [];
        for ($nv = 1; $nv <= $numNV; $nv++) {
            $nganh = pick($nganh_list);
            if (in_array($nganh['ma_nganh'], $usedNganh)) continue;
            $usedNganh[] = $nganh['ma_nganh'];
            $pt = pick($phuong_thuc);

            $db->prepare("INSERT INTO nguyen_vong (
                so_cccd, thu_tu_nguyen_vong, ma_truong, ma_nganh, ten_nganh,
                ma_phuong_thuc, ten_phuong_thuc, to_hop_mon,
                trang_thai, trung_tuyen, diem_xet_tuyen,
                created_at, updated_at, nguon_du_lieu
            ) VALUES (
                ?, ?, 'THV', ?, ?,
                ?, ?, ?,
                ?, 0, 0,
                NOW(), NOW(), 'seeded'
            )")->execute([
                $cccd, $nv, $nganh['ma_nganh'], $nganh['ten_nganh'],
                $pt['ma'], $pt['ten'], pick($to_hop_list),
                $status
            ]);
        }

        // 4. INSERT ket_qua_hoc_tap (lop 10, 11, 12)
        foreach ([10, 11, 12] as $lop) {
            $db->prepare("INSERT INTO ket_qua_hoc_tap (
                so_cccd, lop,
                diem_toan_hk1, diem_toan_hk2, diem_toan_cn,
                diem_van_hk1, diem_van_hk2, diem_van_cn,
                diem_ngoai_ngu_hk1, diem_ngoai_ngu_hk2, diem_ngoai_ngu_cn,
                diem_ly_hk1, diem_ly_hk2, diem_ly_cn,
                diem_hoa_hk1, diem_hoa_hk2, diem_hoa_cn,
                diem_sinh_hk1, diem_sinh_hk2, diem_sinh_cn,
                diem_su_hk1, diem_su_hk2, diem_su_cn,
                diem_dia_hk1, diem_dia_hk2, diem_dia_cn,
                diem_gdcd_hk1, diem_gdcd_hk2, diem_gdcd_cn,
                diem_tb_hk1, diem_tb_hk2, diem_tb_ca_nam,
                hoc_luc_hk1, hoc_luc_hk2, hoc_luc_ca_nam,
                hanh_kiem_hk1, hanh_kiem_hk2, hanh_kiem_ca_nam
            ) VALUES (
                ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?
            )")->execute([
                $cccd, $lop,
                rf(5,10), rf(5,10), rf(5,10), // toan
                rf(5,10), rf(5,10), rf(5,10), // van
                rf(4,10), rf(4,10), rf(4,10), // ngoai_ngu
                rf(4,10), rf(4,10), rf(4,10), // ly
                rf(4,10), rf(4,10), rf(4,10), // hoa
                rf(4,10), rf(4,10), rf(4,10), // sinh
                rf(5,10), rf(5,10), rf(5,10), // su
                rf(5,10), rf(5,10), rf(5,10), // dia
                rf(5,10), rf(5,10), rf(5,10), // gdcd
                rf(5,10), rf(5,10), rf(5,10), // tb
                pick($hoc_luc), pick($hoc_luc), pick($hoc_luc),
                pick($hanh_kiem), pick($hanh_kiem), pick($hanh_kiem)
            ]);
        }

        // 5. INSERT diem_thi_thpt (60% chance)
        if (rand(1, 100) <= 60) {
            $db->prepare("INSERT INTO diem_thi_thpt (
                so_cccd, nam_thi, da_co_diem,
                toan, van, ly, hoa, sinh, su, dia, gdcd, tieng_anh
            ) VALUES (?, ?, true, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
                $cccd, $namTN,
                rf(3, 10), rf(3, 10), rf(3, 10), rf(3, 10), rf(3, 10),
                rf(3, 10), rf(3, 10), rf(3, 10), rf(3, 10)
            ]);
        }

        if ($i % 20 === 0) echo "  $i / $count ...\n";
    }

    $db->commit();
    echo "\n✅ Done! Created $count students.\n";
    echo "  thi_sinh: " . $db->query("SELECT COUNT(*) FROM thi_sinh")->fetchColumn() . "\n";
    echo "  ho_so_xet_tuyen: " . $db->query("SELECT COUNT(*) FROM ho_so_xet_tuyen")->fetchColumn() . "\n";
    echo "  nguyen_vong: " . $db->query("SELECT COUNT(*) FROM nguyen_vong")->fetchColumn() . "\n";
    echo "  ket_qua_hoc_tap: " . $db->query("SELECT COUNT(*) FROM ket_qua_hoc_tap")->fetchColumn() . "\n";
    echo "  diem_thi_thpt: " . $db->query("SELECT COUNT(*) FROM diem_thi_thpt")->fetchColumn() . "\n";

} catch (\Exception $e) {
    $db->rollBack();
    echo "\n❌ Error at record $i: " . $e->getMessage() . "\n";
}
