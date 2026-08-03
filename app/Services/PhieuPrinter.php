<?php
namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Settings;
use PDO;

/**
 * PhieuPrinter - Service tạo phiếu in từ mẫu Word (.docx)
 * Hỗ trợ:
 *  - Phiếu Nhập Học (nguồn: nhap_hoc + ket_qua_trung_tuyen)
 *  - Giấy Báo Trúng Tuyển (nguồn: ket_qua_trung_tuyen)
 * Tích hợp QR Code VietQR
 */
class PhieuPrinter {

    private PDO $db;
    private string $templateDir;
    private string $tempDir;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->templateDir = dirname(__DIR__, 2) . '/storage/templates/';
        $this->tempDir = dirname(__DIR__, 2) . '/storage/temp/';

        if (!is_dir($this->templateDir)) mkdir($this->templateDir, 0755, true);
        if (!is_dir($this->tempDir))     mkdir($this->tempDir, 0755, true);

        // Disable ZipArchive issues on some setups
        Settings::setZipClass(Settings::PCLZIP);
    }

    /**
     * Tạo phiếu nhập học từ nhap_hoc_id (1 thí sinh)
     * @param int $nhapHocId
     * @param string $templateFile  Tên file .docx trong storage/templates/
     * @return string  Đường dẫn file tạm .docx
     */
    public function generatePhieuNhapHoc(int $nhapHocId, string $templateFile): string {
        $data = $this->getEnrollmentData($nhapHocId);
        if (!$data) throw new \Exception("Không tìm thấy hồ sơ nhập học ID: $nhapHocId");

        $templatePath = $this->templateDir . $templateFile;
        if (!file_exists($templatePath)) throw new \Exception("Không tìm thấy mẫu phiếu: $templateFile");

        $proc = new TemplateProcessor($templatePath);
        $this->fillPhieuNhapHoc($proc, $data);

        $outFile = $this->tempDir . 'phieu_nhap_hoc_' . $nhapHocId . '_' . time() . '.docx';
        $proc->saveAs($outFile);
        return $outFile;
    }

    /**
     * Tạo Giấy Báo Trúng Tuyển từ ket_qua_id (1 thí sinh)
     */
    public function generateGiayBao(int $ketQuaId, string $templateFile): string {
        $data = $this->getAdmissionResultData($ketQuaId);
        if (!$data) throw new \Exception("Không tìm thấy kết quả trúng tuyển ID: $ketQuaId");

        $templatePath = $this->templateDir . $templateFile;
        if (!file_exists($templatePath)) throw new \Exception("Không tìm thấy mẫu phiếu: $templateFile");

        $proc = new TemplateProcessor($templatePath);
        $this->fillGiayBao($proc, $data);

        $outFile = $this->tempDir . 'giay_bao_' . $ketQuaId . '_' . time() . '.docx';
        $proc->saveAs($outFile);
        return $outFile;
    }

    /**
     * Tạo file ZIP chứa nhiều phiếu nhập học
     * @param array $nhapHocIds
     * @param string $templateFile
     * @return string  Đường dẫn file .zip
     */
    public function batchPhieuNhapHoc(array $nhapHocIds, string $templateFile): string {
        $files = [];
        foreach ($nhapHocIds as $id) {
            try {
                $files[] = $this->generatePhieuNhapHoc((int)$id, $templateFile);
            } catch (\Exception $e) {
                // Skip invalid IDs
            }
        }
        if (empty($files)) throw new \Exception("Không có phiếu nào được tạo");
        return $this->mergeDocxToZip($files, 'phieu_nhap_hoc_batch');
    }

    /**
     * Tạo file ZIP chứa nhiều Giấy Báo Trúng Tuyển
     * @param array $ketQuaIds
     * @param string $templateFile
     * @return string  Đường dẫn file .zip
     */
    public function batchGiayBao(array $ketQuaIds, string $templateFile): string {
        $files = [];
        foreach ($ketQuaIds as $id) {
            try {
                $files[] = $this->generateGiayBao((int)$id, $templateFile);
            } catch (\Exception $e) {
                // Skip
            }
        }
        if (empty($files)) throw new \Exception("Không có giấy báo nào được tạo");
        return $this->mergeDocxToZip($files, 'giay_bao_batch');
    }

    // =========================================================================
    //  DATA FETCH
    // =========================================================================

    private function getEnrollmentData(int $nhapHocId): ?array {
        $stmt = $this->db->prepare("
            SELECT
                nh.id           AS nhap_hoc_id,
                nh.ma_phieu,
                nh.trang_thai,
                nh.ngay_nhap_hoc,
                nh.ghi_chu_can_bo,

                kq.ho_ten,
                kq.ngay_sinh,
                kq.so_cccd,
                kq.sbd,
                kq.khu_vuc,
                kq.doi_tuong,
                kq.phuong_thuc,
                kq.to_hop,
                kq.diem1, kq.diem2, kq.diem3,
                kq.diem_to_hop,
                kq.diem_ut,
                kq.diem_ut_quy_doi,
                kq.diem_xt,
                kq.ma_nganh,
                kq.ten_nganh,
                kq.so_thu_tu,
                kq.kinh_phi,
                kq.thoi_gian_nhap_hoc,
                kq.sdt,
                kq.email,
                kq.so_tai_khoan,
                kq.ten_ngan_hang,
                kq.noi_dung_chuyen_khoan,
                kq.id           AS ket_qua_id,

                ts.gioi_tinh,
                ts.dia_chi_chi_tiet,
                ts.nam_tot_nghiep,

                qv.ho_ten       AS ten_can_bo
            FROM nhap_hoc nh
            JOIN ket_qua_trung_tuyen kq ON nh.ket_qua_id = kq.id
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            LEFT JOIN quan_tri_vien qv ON nh.nguoi_nhap = qv.id
            WHERE nh.id = ?
        ");
        $stmt->execute([$nhapHocId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getAdmissionResultData(int $ketQuaId): ?array {
        $stmt = $this->db->prepare("
            SELECT
                kq.*,
                ts.gioi_tinh,
                ts.dia_chi_chi_tiet
            FROM ket_qua_trung_tuyen kq
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            WHERE kq.id = ?
        ");
        $stmt->execute([$ketQuaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    //  FILL PLACEHOLDERS
    // =========================================================================

    /** Điền dữ liệu vào mẫu Phiếu Nhập Học */
    private function fillPhieuNhapHoc(TemplateProcessor $proc, array $d): void {
        // Xử lý QR trước (ảnh)
        $qrPath = $this->fetchVietQR(
            $d['ten_ngan_hang'] ?? '',
            $d['so_tai_khoan'] ?? '',
            $d['kinh_phi'] ?? '0',
            $d['noi_dung_chuyen_khoan'] ?? ('NHAPHOC ' . ($d['so_cccd'] ?? '')),
            'TRUONG DAI HOC HUNG VUONG'
        );

        $values = $this->buildCommonValues($d);
        $values['MAPHIEU']       = $d['ma_phieu'] ?? '';
        $values['NGAYNHAPHOC']   = $d['ngay_nhap_hoc'] ? date('d/m/Y', strtotime($d['ngay_nhap_hoc'])) : '';
        $values['TENCANBO']      = $d['ten_can_bo'] ?? '';

        foreach ($values as $placeholder => $value) {
            $proc->setValue($placeholder, htmlspecialchars((string)$value, ENT_XML1, 'UTF-8'));
        }

        // Nhúng QR Code thanh toán
        if ($qrPath && file_exists($qrPath)) {
            try {
                $proc->setImageValue('QRT', [
                    'path'   => $qrPath,
                    'width'  => 130,
                    'height' => 130,
                    'ratio'  => false
                ]);
            } catch (\Throwable $e) {
                $proc->setValue('QRT', '[QR]');
            }
        } else {
            $proc->setValue('QRT', '');
        }
    }

    /** Điền dữ liệu vào mẫu Giấy Báo Trúng Tuyển */
    private function fillGiayBao(TemplateProcessor $proc, array $d): void {
        $qrPath = $this->fetchVietQR(
            $d['ten_ngan_hang'] ?? '',
            $d['so_tai_khoan'] ?? '',
            $d['kinh_phi'] ?? '0',
            $d['noi_dung_chuyen_khoan'] ?? ('NHAPHOC ' . ($d['so_cccd'] ?? '')),
            'TRUONG DAI HOC HUNG VUONG'
        );

        $values = $this->buildCommonValues($d);

        foreach ($values as $placeholder => $value) {
            $proc->setValue($placeholder, htmlspecialchars((string)$value, ENT_XML1, 'UTF-8'));
        }

        // QR thanh toán
        if ($qrPath && file_exists($qrPath)) {
            try {
                $proc->setImageValue('QRT', [
                    'path'   => $qrPath,
                    'width'  => 130,
                    'height' => 130,
                    'ratio'  => false
                ]);
            } catch (\Throwable $e) {
                $proc->setValue('QRT', '[QR]');
            }
        } else {
            $proc->setValue('QRT', '');
        }

        // QR mã CCCD/SBD (nếu mẫu có ${QRN})
        $proc->setValue('QRN', $d['so_cccd'] ?? '');
    }

    /**
     * Tạo mapping placeholder → giá trị dùng chung cho cả 2 loại phiếu
     * Các placeholder khớp với mẫu Word năm 2025
     */
    private function buildCommonValues(array $d): array {
        $ngaySinh = '';
        if (!empty($d['ngay_sinh'])) {
            $ngaySinh = date('d/m/Y', strtotime($d['ngay_sinh']));
        }

        return [
            // --- Thí sinh ---
            'HOTEN'         => mb_strtoupper($d['ho_ten'] ?? '', 'UTF-8'),
            'NGAYSINH'      => $ngaySinh,
            'CCCD'          => $d['so_cccd'] ?? '',
            'SBD'           => $d['sbd'] ?? '',
            'KV'            => $d['khu_vuc'] ?? '',
            'DOITUONG'      => $d['doi_tuong'] ?? '',
            'PT'            => $d['phuong_thuc'] ?? '',
            'TOHOP'         => $d['to_hop'] ?? '',
            // --- Điểm ---
            'DM1'           => $this->fmtDiem($d['diem1'] ?? null),
            'DM2'           => $this->fmtDiem($d['diem2'] ?? null),
            'DM3'           => $this->fmtDiem($d['diem3'] ?? null),
            'DIEMTOHOP'     => $this->fmtDiem($d['diem_to_hop'] ?? null),
            'DIEMUT'        => $this->fmtDiem($d['diem_ut'] ?? null),
            'UTQ'           => $this->fmtDiem($d['diem_ut_quy_doi'] ?? null),
            'DIEMXT'        => $this->fmtDiem($d['diem_xt'] ?? null),
            // --- Ngành ---
            'NGANH'         => $d['ten_nganh'] ?? '',
            'MANGANH'       => $d['ma_nganh'] ?? '',
            'SOTT'          => $d['so_thu_tu'] ?? '',
            // --- Tài chính ---
            'KINHPHI'       => $this->fmtTien($d['kinh_phi'] ?? null),
            'THOIGIANNHAP'  => $d['thoi_gian_nhap_hoc'] ?? '',
            // --- Liên lạc ---
            'SDT'           => $d['sdt'] ?? '',
            'EMAIL'         => $d['email'] ?? '',
            // --- Thanh toán ---
            'STK'           => $d['so_tai_khoan'] ?? '',
            'NGANHANG'      => $d['ten_ngan_hang'] ?? '',
            'NOIDUNGCK'     => $d['noi_dung_chuyen_khoan'] ?? '',
        ];
    }

    private function fmtDiem(?float $v): string {
        if ($v === null || $v === '') return '';
        return number_format((float)$v, 3, '.', '');
    }

    private function fmtTien($v): string {
        if (empty($v)) return '';
        return number_format((float)$v, 0, ',', '.') . ' đồng';
    }

    // =========================================================================
    //  VIETQR
    // =========================================================================

    /**
     * Tải QR VietQR từ img.vietqr.io và lưu tạm
     * @return string|null  Đường dẫn file ảnh PNG tạm, null nếu lỗi
     */
    private function fetchVietQR(
        string $bankName,
        string $accountNo,
        string $amount,
        string $addInfo,
        string $accountName
    ): ?string {
        if (empty($bankName) || empty($accountNo)) return null;

        // Map tên ngân hàng → BIN code
        $binMap = [
            'VIETINBANK'  => '970415',
            'VIETIN'      => '970415',
            'AGRIBANK'    => '970405',
            'AGRI'        => '970405',
            'BIDV'        => '970418',
            'VIETCOMBANK' => '970436',
            'VCB'         => '970436',
            'TECHCOMBANK' => '970407',
            'MB'          => '970422',
            'MBBANK'      => '970422',
        ];
        $binCode = $binMap[strtoupper(trim($bankName))] ?? '970415';

        $amount   = preg_replace('/[^0-9]/', '', (string)$amount);
        $addInfo  = urlencode($addInfo);
        $accName  = urlencode($accountName);

        $url = "https://img.vietqr.io/image/{$binCode}-{$accountNo}-compact2.png"
             . "?amount={$amount}&addInfo={$addInfo}&accountName={$accName}";

        $tempFile = $this->tempDir . 'qr_' . md5($url) . '.png';

        // Dùng cache 1 giờ
        if (file_exists($tempFile) && (time() - filemtime($tempFile)) < 3600) {
            return $tempFile;
        }

        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $data = @file_get_contents($url, false, $ctx);

        if ($data === false || strlen($data) < 1000) return null;

        file_put_contents($tempFile, $data);
        return $tempFile;
    }

    // =========================================================================
    //  ZIP HELPER
    // =========================================================================

    private function mergeDocxToZip(array $files, string $baseName): string {
        $zipPath = $this->tempDir . $baseName . '_' . date('Ymd_His') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            throw new \Exception("Không thể tạo file ZIP");
        }
        foreach ($files as $i => $file) {
            $zip->addFile($file, ($i + 1) . '_' . basename($file));
        }
        $zip->close();
        return $zipPath;
    }

    // =========================================================================
    //  PUBLIC HELPERS
    // =========================================================================

    public function getTemplateDir(): string { return $this->templateDir; }
    public function getTempDir():     string { return $this->tempDir; }

    /** Dọn file tạm cũ hơn 2 giờ */
    public function cleanTemp(): void {
        foreach (glob($this->tempDir . '*') as $f) {
            if (is_file($f) && (time() - filemtime($f)) > 7200) @unlink($f);
        }
    }
}
