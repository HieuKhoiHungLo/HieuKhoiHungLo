<?php
namespace App\Services;

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdmissionLetterService {
    protected $db;
    protected $mailer;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->mailer = new MailerService();
    }

    /**
     * Import thí sinh từ file Excel — tối ưu cho file lớn (batch insert + in-memory dedup)
     */
    public function importFromExcel($filePath, $batchId) {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        // ── 1. ĐỌC EXCEL: chỉ lấy dữ liệu thô, bỏ qua formula/formatting ──────
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);        // bỏ style, formula → nhanh hơn ~3×
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow    = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();

        $data = $sheet->rangeToArray(
            'A1:' . $highestColumn . $highestRow,
            null, true, false, true   // false = không tính formula
        );

        // ── 2. MAP HEADER ────────────────────────────────────────────────────────
        $headers = array_map('trim', array_map('strval', $data[1]));
        $colMap  = [];
        foreach ($headers as $col => $header) {
            $h = $this->normalizeHeader($header);
            $fieldMap = [
                'cccd' => 'cccd',
                'hoten' => 'hoten',
                'hovaten' => 'hoten',
                'ngaysinh' => 'ngaysinh',
                'sbd' => 'sbd',
                'sobaodanh' => 'sbd',
                'kv' => 'kv',
                'khuvuc' => 'kv',
                'doituong' => 'doituong',
                'tohop' => 'tohop',
                'dm1' => 'dm1',
                'dm2' => 'dm2',
                'dm3' => 'dm3',
                'diemtohop' => 'diemtohop',
                'diemut' => 'diemut',
                'diemuutien' => 'diemut',
                'utq' => 'utq',
                'utquydoi' => 'utq',
                'diemxt' => 'diemxt',
                'diemxettuyen' => 'diemxt',
                'manganh' => 'manganh',
                'nganh' => 'nganh',
                'tennganh' => 'nganh',
                'sotk' => 'sotk',
                'sotaikhoan' => 'sotk',
                'nganhang' => 'nganhang',
                'sotien' => 'sotien',
                'noidung' => 'noidung',
                'noidungck' => 'noidung',
                'noidungchuyenkhoan' => 'noidung',
                'email' => 'email',
                'sdt' => 'sdt',
                'sodienthoai' => 'sdt',
                'ghichu' => 'ghichu',
                'phuongthuc' => 'phuongthuc',
            ];
            if (isset($fieldMap[$h])) $colMap[$fieldMap[$h]] = $col;
        }

        if (!isset($colMap['email']) || !isset($colMap['cccd'])) {
            throw new \Exception("File Excel thiếu cột 'Email' hoặc 'CCCD' bắt buộc.");
        }

        // ── 3. PRE-LOAD CCCD đã tồn tại vào bộ nhớ (1 query duy nhất) ──────────
        $existStmt = $this->db->prepare(
            "SELECT so_cccd FROM thu_trung_tuyen WHERE batch_id = ?"
        );
        $existStmt->execute([$batchId]);
        $existingCCCDs = array_flip($existStmt->fetchAll(\PDO::FETCH_COLUMN));

        // ── 4. CHUẨN BỊ DỮ LIỆU (không query DB) ───────────────────────────────
        $parseFloat = function($val) {
            $val = str_replace(',', '.', trim((string)($val ?? '0')));
            return is_numeric($val) ? (float)$val : 0;
        };

        $rows    = [];
        $ignored = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $data[$row] ?? [];

            $email = trim($rowData[$colMap['email']] ?? '');
            $cccd  = trim($rowData[$colMap['cccd']]  ?? '');

            if (empty($email) || empty($cccd)) { $ignored++; continue; }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $ignored++; continue; }
            if (isset($existingCCCDs[$cccd])) { $ignored++; continue; }

            // Đánh dấu in-memory để bắt duplicate ngay trong file
            $existingCCCDs[$cccd] = true;

            $soTienStr = isset($colMap['sotien']) ? preg_replace('/[^0-9]/', '', strval($rowData[$colMap['sotien']] ?? '')) : '';
            $soTien    = $soTienStr ? (int)$soTienStr : 0;

            $rows[] = [
                $batchId,
                $cccd,
                trim(isset($colMap['hoten']) ? ($rowData[$colMap['hoten']] ?? '') : ''),
                trim(isset($colMap['ngaysinh']) ? ($rowData[$colMap['ngaysinh']] ?? '') : ''),
                trim(isset($colMap['sbd']) ? ($rowData[$colMap['sbd']] ?? '') : ''),
                trim(isset($colMap['kv']) ? ($rowData[$colMap['kv']] ?? '') : ''),
                trim(isset($colMap['doituong']) ? ($rowData[$colMap['doituong']] ?? '') : ''),
                trim(isset($colMap['tohop']) ? ($rowData[$colMap['tohop']] ?? '') : ''),
                $parseFloat(isset($colMap['dm1']) ? ($rowData[$colMap['dm1']] ?? null) : null),
                $parseFloat(isset($colMap['dm2']) ? ($rowData[$colMap['dm2']] ?? null) : null),
                $parseFloat(isset($colMap['dm3']) ? ($rowData[$colMap['dm3']] ?? null) : null),
                $parseFloat(isset($colMap['diemtohop']) ? ($rowData[$colMap['diemtohop']] ?? null) : null),
                $parseFloat(isset($colMap['diemut']) ? ($rowData[$colMap['diemut']] ?? null) : null),
                $parseFloat(isset($colMap['utq']) ? ($rowData[$colMap['utq']] ?? null) : null),
                $parseFloat(isset($colMap['diemxt']) ? ($rowData[$colMap['diemxt']] ?? null) : null),
                trim(isset($colMap['manganh']) ? ($rowData[$colMap['manganh']] ?? '') : ''),
                trim(isset($colMap['nganh']) ? ($rowData[$colMap['nganh']] ?? '') : ''),
                trim(isset($colMap['phuongthuc']) ? ($rowData[$colMap['phuongthuc']] ?? '') : ''),
                str_replace(' ', '', trim(isset($colMap['sotk']) ? ($rowData[$colMap['sotk']] ?? '') : '')),
                trim(isset($colMap['nganhang']) ? ($rowData[$colMap['nganhang']] ?? '') : ''),
                $soTien,
                trim(isset($colMap['noidung']) ? ($rowData[$colMap['noidung']] ?? '') : ''),
                $email,
                trim(isset($colMap['sdt']) ? ($rowData[$colMap['sdt']] ?? '') : ''),
                trim(isset($colMap['ghichu']) ? ($rowData[$colMap['ghichu']] ?? '') : ''),
            ];
        }

        // ── 5. BATCH INSERT — mỗi lần 500 dòng ──────────────────────────────────
        $imported   = 0;
        $batchSize  = 500;
        $colCount   = 25;

        $baseSql = "INSERT INTO thu_trung_tuyen (
            batch_id, so_cccd, ho_ten, ngay_sinh, sbd, khu_vuc, doi_tuong, to_hop,
            diem_mon_1, diem_mon_2, diem_mon_3, diem_to_hop, diem_ut, ut_quy_doi,
            diem_xt, ma_nganh, ten_nganh, phuong_thuc,
            so_tai_khoan, ngan_hang, so_tien, noi_dung_ck,
            email, sdt, ghi_chu
        ) VALUES ";

        $chunks = array_chunk($rows, $batchSize);

        $this->db->beginTransaction();
        try {
            foreach ($chunks as $chunk) {
                $placeholderRow = '(' . implode(',', array_fill(0, $colCount, '?')) . ')';
                $sql = $baseSql . implode(',', array_fill(0, count($chunk), $placeholderRow));
                $flat = array_merge(...$chunk);

                $this->db->prepare($sql)->execute($flat);
                $imported += count($chunk);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return ['imported' => $imported, 'ignored' => $ignored];
    }

    /**
     * Lấy danh sách các đợt gửi
     */
    public function getBatches() {
        $stmt = $this->db->query("
            SELECT 
                batch_id,
                MIN(created_at) as created_at,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM thu_trung_tuyen
            GROUP BY batch_id
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chi tiết thí sinh của 1 đợt
     */
    public function getBatchDetails($batchId, $statusFilter = '') {
        return $this->getCandidates(['batch_id' => $batchId, 'status' => $statusFilter]);
    }

    /**
     * Lấy tất cả các mẫu email có sẵn
     */
    public function getTemplates() {
        $stmt = $this->db->query("SELECT id, subject, code FROM email_templates ORDER BY id ASC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách thí sinh với bộ lọc
     */
    public function getCandidates($filters = []) {
        $page = (int)($filters['page'] ?? 1);
        $limit = (int)($filters['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $sql = "FROM thu_trung_tuyen WHERE 1=1";
        $params = [];

        if (!empty($filters['batch_id'])) {
            $sql .= " AND batch_id = ?";
            $params[] = $filters['batch_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['q'])) {
            $sql .= " AND (ho_ten ILIKE ? OR so_cccd ILIKE ? OR email ILIKE ? OR sdt ILIKE ?)";
            $q = "%" . $filters['q'] . "%";
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        // Detailed filters from table header
        if (!empty($filters['f_name'])) {
            $sql .= " AND ho_ten ILIKE ?";
            $params[] = "%" . $filters['f_name'] . "%";
        }
        if (!empty($filters['f_cccd'])) {
            $sql .= " AND so_cccd ILIKE ?";
            $params[] = "%" . $filters['f_cccd'] . "%";
        }
        if (!empty($filters['f_phone'])) {
            $sql .= " AND sdt ILIKE ?";
            $params[] = "%" . $filters['f_phone'] . "%";
        }
        if (!empty($filters['f_email'])) {
            $sql .= " AND email ILIKE ?";
            $params[] = "%" . $filters['f_email'] . "%";
        }
        if (!empty($filters['f_dob'])) {
            $sql .= " AND ngay_sinh ILIKE ?";
            $params[] = "%" . $filters['f_dob'] . "%";
        }
        if (!empty($filters['f_major'])) {
            $sql .= " AND ten_nganh ILIKE ?";
            $params[] = "%" . $filters['f_major'] . "%";
        }

        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) " . $sql);
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalItems / $limit);

        // Get data
        $dataSql = "SELECT * " . $sql . " ORDER BY ma_nganh DESC, diem_xt ASC, id ASC LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($dataSql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'limit' => $limit
            ]
        ];
    }

    /**
     * Đưa các thí sinh được chọn vào queue với template cụ thể
     */
    public function enqueueSelected($ids, $templateId) {
        if (empty($ids)) return 0;

        // Fetch Template
        $tplStmt = $this->db->prepare("SELECT * FROM email_templates WHERE id = ?");
        $tplStmt->execute([$templateId]);
        $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$template) {
            throw new \Exception("Mẫu email không tồn tại.");
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM thu_trung_tuyen WHERE id IN ($placeholders) ORDER BY ma_nganh DESC, diem_xt ASC, id ASC");
        $stmt->execute($ids);
        $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $enqueuedCount = 0;
        foreach ($candidates as $candidate) {
            if (empty($candidate['email'])) continue;

            $subject = $template['subject'] ?? 'Thông báo';
            $body = $this->renderTemplate($template['body'], $candidate);
            
            $this->mailer->enqueue($candidate['email'], $subject, $body, true, 'admission_letter');
            
            $upd = $this->db->prepare("UPDATE thu_trung_tuyen SET status = 'queued' WHERE id = ?");
            $upd->execute([$candidate['id']]);

            $enqueuedCount++;
        }

        return $enqueuedCount;
    }

    /**
     * Xóa các thí sinh được chọn
     */
    public function deleteSelected($ids) {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM thu_trung_tuyen WHERE id IN ($placeholders)");
        return $stmt->execute($ids);
    }

    /**
     * Gắn toàn bộ email đợt này vào queue để gửi dần
     */
    public function enqueueBatch($batchId) {
        $stmt = $this->db->prepare("SELECT * FROM thu_trung_tuyen WHERE batch_id = ? AND status IN ('pending', 'failed') ORDER BY ma_nganh DESC, diem_xt ASC, id ASC");
        $stmt->execute([$batchId]);
        $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Fetch Template
        $tplStmt = $this->db->prepare("SELECT * FROM email_templates WHERE code = 'ADMISSION_LETTER'");
        $tplStmt->execute();
        $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$template) {
            throw new \Exception("Chưa cấu hình template email ADMISSION_LETTER");
        }

        $enqueuedCount = 0;
        foreach ($candidates as $candidate) {
            if (empty($candidate['email'])) continue;

            $subject = $template['subject'] ?? 'Thông báo trúng tuyển';
            
            // Render template
            $body = $this->renderTemplate($template['body'], $candidate);
            
            // Queue via MailerService
            $this->mailer->enqueue($candidate['email'], $subject, $body, true, 'admission_letter');
            
            // Cập nhật trạng thái thành queue
            $upd = $this->db->prepare("UPDATE thu_trung_tuyen SET status = 'queued' WHERE id = ?");
            $upd->execute([$candidate['id']]);

            $enqueuedCount++;
        }

        return $enqueuedCount;
    }

    /**
     * Render nội dung thư
     */
    public function renderTemplate($templateHtml, $data) {
        $cccd = $data['so_cccd'] ?? '';
        $maNganh = $data['ma_nganh'] ?? '';
        
        // Fetch extra fields from ket_qua_trung_tuyen
        $stmtKq = $this->db->prepare("SELECT * FROM ket_qua_trung_tuyen WHERE so_cccd = ? AND ma_nganh = ? LIMIT 1");
        $stmtKq->execute([$cccd, $maNganh]);
        $kqRow = $stmtKq->fetch(\PDO::FETCH_ASSOC) ?: [];
        
        // Fetch gender and details from thi_sinh
        $stmtTs = $this->db->prepare("SELECT gioi_tinh, dien_thoai, email FROM thi_sinh WHERE so_cccd = ? LIMIT 1");
        $stmtTs->execute([$cccd]);
        $tsRow = $stmtTs->fetch(\PDO::FETCH_ASSOC) ?: [];
        $gioiTinh = $tsRow['gioi_tinh'] ?? '';
        
        // Fetch from nhap_hoc
        $nhapHocRow = [];
        if (!empty($kqRow['id'])) {
            $stmtNh = $this->db->prepare("SELECT * FROM nhap_hoc WHERE ket_qua_id = ? LIMIT 1");
            $stmtNh->execute([$kqRow['id']]);
            $nhapHocRow = $stmtNh->fetch(\PDO::FETCH_ASSOC) ?: [];
        }

        $chiTieu = '';
        $diemNamTruoc = '';
        if (!empty($data['ma_nganh'])) {
            $stmt = $this->db->prepare("SELECT chi_tieu, diem_nam_truoc FROM dm_nganh WHERE ma_nganh = ? LIMIT 1");
            $stmt->execute([$data['ma_nganh']]);
            $nganhInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($nganhInfo) {
                $chiTieu = $nganhInfo['chi_tieu'] !== null ? $nganhInfo['chi_tieu'] : '';
                $diemNamTruoc = $nganhInfo['diem_nam_truoc'] !== null ? number_format((float)$nganhInfo['diem_nam_truoc'], 2, '.', '') : '';
                if ($diemNamTruoc !== '') {
                    $diemNamTruoc = rtrim(rtrim($diemNamTruoc, '0'), '.');
                }
            }
        }

        $prio = \App\Controllers\AdmissionController::calcPriorityPoints(
            $data['khu_vuc'] ?? $data['khu_vuc_uu_tien'] ?? '',
            $data['doi_tuong'] ?? $data['doi_tuong_uu_tien'] ?? '',
            $data['diem_mon_1'] ?? null,
            $data['diem_mon_2'] ?? null,
            $data['diem_mon_3'] ?? null,
            $data['diem_to_hop'] ?? null
        );

        $rawDiemUt = !empty($data['diem_ut']) && (float)$data['diem_ut'] > 0 ? $data['diem_ut'] : (!empty($kqRow['diem_ut']) && (float)$kqRow['diem_ut'] > 0 ? $kqRow['diem_ut'] : ($prio['diem_ut'] ?? 0));
        $rawUtQuyDoi = !empty($data['ut_quy_doi']) && (float)$data['ut_quy_doi'] > 0 ? $data['ut_quy_doi'] : (!empty($kqRow['ut_quy_doi']) && (float)$kqRow['ut_quy_doi'] > 0 ? $kqRow['ut_quy_doi'] : ($prio['ut_quy_doi'] ?? 0));

        $diemUtFormatted = number_format((float)$rawDiemUt, 3, '.', '');
        $utQuyDoiFormatted = number_format((float)$rawUtQuyDoi, 3, '.', '');

        $replacements = [
            // 1. Thông tin cá nhân thí sinh
            '{{HoTen}}'       => $data['ho_ten'] ?? '',
            '{{HOTEN}}'       => $data['ho_ten'] ?? '',
            '{{name}}'        => $data['ho_ten'] ?? '',
            '{{CCCD}}'        => $data['so_cccd'] ?? '',
            '{{SO_CCCD}}'     => $data['so_cccd'] ?? '',
            '{{NgaySinh}}'    => $data['ngay_sinh'] ?? '',
            '{{NGAYSINH}}'    => $data['ngay_sinh'] ?? '',
            '{{SBD}}'         => $data['sbd'] ?? '',
            '{{GioiTinh}}'    => $gioiTinh ?: ($data['gioi_tinh'] ?? ''),
            '{{Email}}'       => $data['email'] ?? $tsRow['email'] ?? '',
            '{{EMAIL}}'       => $data['email'] ?? $tsRow['email'] ?? '',
            '{{SDT}}'         => $data['sdt'] ?? $tsRow['dien_thoai'] ?? '',
            '{{KhuVuc}}'      => $data['khu_vuc'] ?? $data['khu_vuc_uu_tien'] ?? '',
            '{{KHUVUC}}'      => $data['khu_vuc'] ?? $data['khu_vuc_uu_tien'] ?? '',
            '{{DoiTuong}}'    => $data['doi_tuong'] ?? $data['doi_tuong_uu_tien'] ?? '',
            '{{DOITUONG}}'    => $data['doi_tuong'] ?? $data['doi_tuong_uu_tien'] ?? '',

            // 2. Thông tin xét tuyển & điểm
            '{{PhuongThuc}}'  => $data['phuong_thuc'] ?? '',
            '{{PHUONGTHUC}}'  => $data['phuong_thuc'] ?? '',
            '{{ToHop}}'       => !empty($data['to_hop']) ? $this->getToHopDetail($data['to_hop']) : '',
            '{{TOHOP}}'       => !empty($data['to_hop']) ? $this->getToHopDetail($data['to_hop']) : '',
            '{{DM1}}'         => $data['diem_mon_1'] ?? '',
            '{{DM2}}'         => $data['diem_mon_2'] ?? '',
            '{{DM3}}'         => $data['diem_mon_3'] ?? '',
            '{{DiemToHop}}'   => $data['diem_to_hop'] ?? '',
            '{{DIEMTOHOP}}'   => $data['diem_to_hop'] ?? '',
            '{{DiemUT}}'      => $diemUtFormatted,
            '{{DIEMUT}}'      => $diemUtFormatted,
            '{{UTQ}}'         => $utQuyDoiFormatted,
            '{{DiemXT}}'      => rtrim(rtrim(number_format((float)($data['diem_xt'] ?? 0), 3, '.', ''), '0'), '.'),
            '{{DIEMXT}}'      => rtrim(rtrim(number_format((float)($data['diem_xt'] ?? 0), 3, '.', ''), '0'), '.'),
            '{{Nganh}}'       => $data['ten_nganh'] ?? '',
            '{{NGANH}}'       => $data['ten_nganh'] ?? '',
            '{{major}}'       => $data['ten_nganh'] ?? '',
            '{{MaNganh}}'     => $data['ma_nganh'] ?? '',
            '{{MANGANH}}'     => $data['ma_nganh'] ?? '',
            '{{ChiTieu}}'     => $chiTieu,
            '{{CHITIEU}}'     => $chiTieu,
            '{{DiemNamTruoc}}'=> $diemNamTruoc,
            '{{XepHang}}'     => $data['ghi_chu'] ?? '',
            '{{GhiChu}}'      => $data['ghi_chu'] ?? '',

            // 3. Thông tin Nhập học & Giấy báo trúng tuyển
            '{{SoGB}}'        => $kqRow['so_giay_bao'] ?? '',
            '{{SOGIAYBAO}}'   => $kqRow['so_giay_bao'] ?? '',
            '{{SOGB}}'        => $kqRow['so_giay_bao'] ?? '',
            '{{ThoiGianNhap}}'=> $kqRow['thoi_gian_nhap'] ?? '',
            '{{THOIGIANNHAP}}'=> $kqRow['thoi_gian_nhap'] ?? '',
            '{{NganhTT}}'     => !empty($kqRow['nganh_tt']) ? $kqRow['nganh_tt'] : ($data['ten_nganh'] ?? ''),
            '{{NGANH_TT}}'    => !empty($kqRow['nganh_tt']) ? $kqRow['nganh_tt'] : ($data['ten_nganh'] ?? ''),
            '{{Khoa}}'        => $kqRow['ten_khoa'] ?? '',
            '{{KHOA}}'        => $kqRow['ten_khoa'] ?? '',
            '{{KinhPhi}}'     => $kqRow['kinh_phi'] ?? '',
            '{{KINHPHI}}'     => $kqRow['kinh_phi'] ?? '',
            '{{KhoiKinhPhi}}' => !empty($kqRow['kinh_phi']) ? '<div style="margin-top:12px; padding:10px 14px; background:#eff6ff; border-left:3px solid #3b82f6; border-radius:0 6px 6px 0; font-size:13px; color:#1e40af; font-family:Arial,Helvetica,sans-serif;"><i class="fas fa-info-circle" style="margin-right:4px;"></i> ' . $kqRow['kinh_phi'] . '</div>' : '',
            '{{FileGiayBao}}' => $kqRow['file_giay_bao'] ?? '',
            '{{LINKGIAYBAO}}' => $kqRow['file_giay_bao'] ?? '',

            // 4. Thông tin Học phí & Ngân hàng
            '{{SOTK}}'        => !empty($data['so_tai_khoan']) ? $data['so_tai_khoan'] : (!empty($kqRow['so_tai_khoan']) ? $kqRow['so_tai_khoan'] : ''),
            '{{SOTAIKHOAN}}'  => !empty($data['so_tai_khoan']) ? $data['so_tai_khoan'] : (!empty($kqRow['so_tai_khoan']) ? $kqRow['so_tai_khoan'] : ''),
            '{{NGANHANG}}'    => !empty($data['ngan_hang']) ? $data['ngan_hang'] : (!empty($kqRow['ngan_hang']) ? $kqRow['ngan_hang'] : ''),
            '{{SoTien}}'      => number_format((float)(!empty($data['so_tien']) ? $data['so_tien'] : (!empty($kqRow['so_tien']) ? $kqRow['so_tien'] : 0)), 0, ',', '.'),
            '{{SOTIEN}}'      => number_format((float)(!empty($data['so_tien']) ? $data['so_tien'] : (!empty($kqRow['so_tien']) ? $kqRow['so_tien'] : 0)), 0, ',', '.'),
            '{{NOIDUNG}}'     => !empty($data['noi_dung_ck']) ? $data['noi_dung_ck'] : (!empty($kqRow['noi_dung_ck']) ? $kqRow['noi_dung_ck'] : ''),
            '{{NOIDUNGCK}}'   => !empty($data['noi_dung_ck']) ? $data['noi_dung_ck'] : (!empty($kqRow['noi_dung_ck']) ? $kqRow['noi_dung_ck'] : ''),
        ];

        // === Nút Xem giấy báo PDF ===
        $replacements['{{NutXemGiayBao}}'] = '<div style="margin-top: 14px; text-align: center;"><a href="' . url('/tra-cuu-trung-tuyen', true) . '" target="_blank" style="display: inline-block; background-color: #1e40af; color: #ffffff; text-decoration: none; padding: 10px 24px; border-radius: 8px; font-weight: bold; font-size: 13px; font-family: Arial, Helvetica, sans-serif;"><i class="fas fa-file-pdf" style="margin-right: 6px;"></i> Xem Giấy báo trúng tuyển (PDF)</a></div>';

        // === Trạng thái xác nhận Nhà trường ===
        $replacements['{{TrangThaiXacNhan}}'] = '<div style="text-align: center;"><a href="' . url('/tra-cuu-trung-tuyen', true) . '" target="_blank" style="display: inline-block; background-color: #dc2626; color: #ffffff; text-decoration: none; padding: 10px 24px; border-radius: 8px; font-weight: bold; text-align: center; font-size: 13px; font-family: Arial, Helvetica, sans-serif;"><i class="fas fa-check-circle" style="margin-right: 4px;"></i> XÁC NHẬN NHẬP HỌC TRỰC TUYẾN</a></div>';

        // === Trạng thái xác nhận Bộ GD&ĐT ===
        $replacements['{{TrangThaiXacNhanBo}}'] = '<div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; text-align: left; font-family: Arial, Helvetica, sans-serif;">'
            . '<span style="font-size: 13px; color: #374151; flex: 1;">Vui lòng truy cập hệ thống Bộ GD&ĐT để xác nhận nhập học chính thức.</span>'
            . '<a href="https://thisinh.thitotnghiepthpt.edu.vn" target="_blank" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; font-size: 13px; white-space: nowrap; font-family: Arial, Helvetica, sans-serif;"><i class="fas fa-external-link-alt" style="margin-right: 4px;"></i> Đi tới hệ thống Bộ</a>'
            . '</div>';

        // === Trạng thái xác nhận kinh phí ===
        $replacements['{{XacNhanKinhPhi}}'] = '<div style="margin-top:14px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; text-align: left; font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #374151;"><i class="fas fa-info-circle" style="color: #3b82f6; margin-right: 6px;"></i> Sau khi hoàn tất nộp kinh phí nhập học, Anh (chị) vui lòng truy cập <a href="' . url('/tra-cuu-trung-tuyen', true) . '" target="_blank" style="color: #2563eb; font-weight: bold; text-decoration: underline;">Cổng tra cứu kết quả</a> để cập nhật trạng thái nộp kinh phí.</div>';

        // === Tiến trình 6 bước ===
        $mergedData = array_merge($data, $kqRow);
        $mergedData['gioi_tinh'] = $gioiTinh;
        $mergedData['nop_kinh_phi'] = !empty($kqRow['xac_nhan_kinh_phi']);
        $mergedData['so_tien_nop'] = $nhapHocRow['so_tien_da_nop'] ?? 0;
        $mergedData['nhap_hoc'] = !empty($nhapHocRow['id']) && ($nhapHocRow['trang_thai'] === 'da_nhap_hoc');
        $replacements['{{THANH_TIEN_DO_6_BUOC}}'] = $this->renderStepperHtml($mergedData);

        // Tạo QR Code urls
        $resolvedNganHang = !empty($data['ngan_hang']) ? $data['ngan_hang'] : (!empty($kqRow['ngan_hang']) ? $kqRow['ngan_hang'] : '');
        $resolvedSoTK = !empty($data['so_tai_khoan']) ? $data['so_tai_khoan'] : (!empty($kqRow['so_tai_khoan']) ? $kqRow['so_tai_khoan'] : '');
        $resolvedSoTien = !empty($data['so_tien']) ? $data['so_tien'] : (!empty($kqRow['so_tien']) ? $kqRow['so_tien'] : 0);
        $resolvedNoiDung = !empty($data['noi_dung_ck']) ? $data['noi_dung_ck'] : (!empty($kqRow['noi_dung_ck']) ? $kqRow['noi_dung_ck'] : '');

        $bankName = strtolower(str_replace(' ', '', $resolvedNganHang));
        $accountNum = $resolvedSoTK;
        $amount = $resolvedSoTien;
        $content = urlencode(trim($resolvedNoiDung));

        if ($bankName && $accountNum) {
            $qrVietQR = '<img src="https://img.vietqr.io/image/' . $bankName . '-' . $accountNum . '-compact2.jpg?amount=' . $amount . '&addInfo=' . $content . '" alt="QR Thanh Toan" style="max-width: 250px; height: auto; border: 1px solid #eee; border-radius: 5px; display: inline-block;" />';
        } else {
            $qrVietQR = '<p style="color:#666; font-style:italic;">(Chưa có thông tin chuyển khoản)</p>';
        }

        $cccd = urlencode($data['so_cccd'] ?? '');
        if ($cccd) {
            $qrCCCD = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . $cccd . '" alt="QR CCCD" style="max-width: 200px; height:auto; border:1px solid #ccc; border-radius:6px;" />';
        } else {
            $qrCCCD = '';
        }

        $replacements['{{QR_ThanhToan}}'] = $qrVietQR;
        $replacements['{{QR_CCCD}}'] = $qrCCCD;

        return strtr($templateHtml, $replacements);
    }

    private function calculateEnrollmentSteps($data) {
        $step1 = true;
        $step2 = !empty($data['xac_nhan_bo']);
        $step3 = !empty($data['xac_nhan_truong']);
        $step4 = !empty($data['nop_kinh_phi']) || (!empty($data['so_tien_nop']) && (float)$data['so_tien_nop'] > 0) || !empty($data['xac_nhan_kinh_phi']);
        $step5 = !empty($data['nhap_hoc']) && $step4;

        return [
            1 => ['title' => '1. Trúng tuyển', 'completed' => $step1, 'icon' => 'fa-graduation-cap', 'desc' => 'Đã có tên trong danh sách trúng tuyển'],
            2 => ['title' => '2. Xác nhận Bộ GD&ĐT', 'completed' => $step2, 'icon' => 'fa-building-columns', 'desc' => 'Xác nhận trên thisinh.thitotnghiepthpt.edu.vn'],
            3 => ['title' => '3. Xác nhận HVU', 'completed' => $step3, 'icon' => 'fa-university', 'desc' => 'Xác nhận nhập học Trường ĐH Hùng Vương'],
            4 => ['title' => '4. Nộp học phí', 'completed' => $step4, 'icon' => 'fa-credit-card', 'desc' => 'Chuyển khoản nộp kinh phí nhập học (VietQR)'],
            5 => ['title' => '5. Hướng dẫn hồ sơ nhập học', 'completed' => $step5, 'icon' => 'fa-folder-open', 'desc' => 'Nhà trường hướng dẫn hoàn thiện hồ sơ'],
        ];
    }

    private function renderStepperHtml($data) {
        $steps = $this->calculateEnrollmentSteps($data);
        $html = '<div class="hvu-stepper-container my-6 p-4 md:p-6 bg-slate-900 text-white rounded-2xl shadow-xl font-sans border border-slate-800" style="margin-top: 24px; margin-bottom: 24px; padding: 20px; background-color: #0f172a; color: #ffffff; border-radius: 16px; border: 1px solid #1e293b;">';
        $html .= '<div style="border-bottom: 1px solid #1e293b; padding-bottom: 12px; margin-bottom: 16px; text-align: left;">';
        $html .= '  <h4 style="font-size: 14px; font-weight: bold; color: #fbbf24; text-transform: uppercase; margin: 0;"><i class="fas fa-route"></i> TIẾN TRÌNH NHẬP HỌC</h4>';
        $html .= '</div>';
        
        $html .= '<div style="text-align: center; margin: 0 auto; width: 100%;">';
        foreach ($steps as $num => $step) {
            $isDone = $step['completed'];
            $statusStyle = $isDone 
                ? 'background-color: #14532d; border: 1px solid #22c55e; color: #bbf7d0; display: inline-block; width: 30%; min-width: 145px; margin: 6px; padding: 12px 8px; border-radius: 12px; text-align: center; vertical-align: top; box-sizing: border-box;' 
                : 'background-color: #1e293b; border: 1px solid #334155; color: #94a3b8; display: inline-block; width: 30%; min-width: 145px; margin: 6px; padding: 12px 8px; border-radius: 12px; text-align: center; vertical-align: top; box-sizing: border-box;';
            $badgeIcon   = $isDone ? '✓' : $num;
            $badgeBg     = $isDone ? 'background-color: #22c55e; color: #ffffff;' : 'background-color: #475569; color: #ffffff;';
            
            $html .= '<div style="' . $statusStyle . '">';
            $html .= '  <div style="width: 24px; height: 24px; line-height: 24px; border-radius: 50%; margin: 0 auto 8px; text-align: center; font-size: 12px; font-weight: bold; ' . $badgeBg . '">' . $badgeIcon . '</div>';
            $html .= '  <div style="font-size: 11px; font-weight: bold; line-height: 1.3; margin-bottom: 4px;">' . htmlspecialchars($step['title']) . '</div>';
            $html .= '  <div style="font-size: 10px; opacity: 0.75;">' . ($isDone ? 'Đã hoàn thành' : 'Chưa thực hiện') . '</div>';
            $html .= '</div>';
        }
        $html .= '</div></div>';
        return $html;
    }

    private function getToHopDetail($maToHop) {
        $stmt = $this->db->prepare("
            SELECT t.ma_to_hop, m1.ma_mon as mon1, m2.ma_mon as mon2, m3.ma_mon as mon3 
            FROM dm_to_hop t
            LEFT JOIN dm_mon m1 ON t.mon_1_id = m1.id
            LEFT JOIN dm_mon m2 ON t.mon_2_id = m2.id
            LEFT JOIN dm_mon m3 ON t.mon_3_id = m3.id
            WHERE t.ma_to_hop = ?
            LIMIT 1
        ");
        $stmt->execute([$maToHop]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $mon1 = !empty($row['mon1']) ? strtoupper($row['mon1']) : '';
            $mon2 = !empty($row['mon2']) ? strtoupper($row['mon2']) : '';
            $mon3 = !empty($row['mon3']) ? strtoupper($row['mon3']) : '';
            $subjects = array_filter([$mon1, $mon2, $mon3]);
            if (!empty($subjects)) {
                return $row['ma_to_hop'] . ' (' . implode('-', $subjects) . ')';
            }
        }
        return $maToHop;
    }
    
    /**
     * Lấy template báo trúng tuyển
     */
    public function getTemplate() {
        $stmt = $this->db->prepare("SELECT * FROM email_templates WHERE code = 'ADMISSION_LETTER'");
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Xóa đợt gửi
     */
    public function deleteBatch($batchId) {
        $stmt = $this->db->prepare("DELETE FROM thu_trung_tuyen WHERE batch_id = ?");
        return $stmt->execute([$batchId]);
    }

    /**
     * Xóa toàn bộ dữ liệu
     */
    public function deleteAll() {
        return $this->db->exec("DELETE FROM thu_trung_tuyen");
    }

    /**
     * Gửi test email
     */
    public function sendTestEmail($email, $templateId, $ids = []) {
        $tplStmt = $this->db->prepare("SELECT * FROM email_templates WHERE id = ?");
        $tplStmt->execute([$templateId]);
        $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$template) {
            throw new \Exception("Mẫu email không tồn tại.");
        }

        $subject = $template['subject'] ?? 'Thông báo trúng tuyển';

        if (!empty($ids)) {
            // Gửi email test cho các thí sinh được chọn tới email nhận test
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("SELECT * FROM thu_trung_tuyen WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($candidates as $candidate) {
                $body = $this->renderTemplate($template['body'], $candidate);
                $this->mailer->enqueue($email, $subject, $body, true, 'admission_letter');
            }
            return count($candidates);
        } else {
            // Tạo dữ liệu giả lập cho email test (logic cũ khi không chọn thí sinh nào)
            $fakeData = [
                'ho_ten' => 'Nguyễn Văn Test',
                'so_cccd' => '012345678912',
                'sbd' => 'T12345',
                'ngay_sinh' => '01/01/2005',
                'khu_vuc' => 'KV1',
                'doi_tuong' => 'UT1',
                'to_hop' => 'A00',
                'diem_mon_1' => 8.5,
                'diem_mon_2' => 8.0,
                'diem_mon_3' => 9.0,
                'diem_to_hop' => 25.5,
                'diem_ut' => 1.5,
                'ut_quy_doi' => 1.0,
                'diem_xt' => 28.0,
                'ten_nganh' => 'Công nghệ thông tin',
                'ma_nganh' => '7480201',
                'phuong_thuc' => 'Xét điểm thi THPT',
                'so_tk' => '1903123456789',
                'ngan_hang' => 'Techcombank',
                'so_tien' => 5000000,
                'noi_dung_ck' => 'Nguyễn Văn Test nop hoc phi',
                'email' => $email,
                'sdt' => '0987654321',
                'ghi_chu' => 'Test Ghi chú'
            ];

            $body = $this->renderTemplate($template['body'], $fakeData);
            return $this->mailer->enqueue($email, $subject, $body, true, 'admission_letter') ? 1 : 0;
        }
    }

    /**
     * Gắn toàn bộ email (theo đợt hoặc tất cả) vào queue để gửi dần
     */
    public function enqueueAll($templateId, $batchId = '') {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        $db = $this->db;

        // Fetch Template
        $tplStmt = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
        $tplStmt->execute([$templateId]);
        $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$template) {
            throw new \Exception("Mẫu email không tồn tại.");
        }

        $subject = $template['subject'] ?? 'Thông báo trúng tuyển';

        // Xây dựng điều kiện lọc theo scope
        if (!empty($batchId)) {
            $baseWhere  = "WHERE batch_id = ? AND status IN ('pending', 'failed')";
            $baseParams = [$batchId];
        } else {
            $baseWhere  = "WHERE status IN ('pending', 'failed')";
            $baseParams = [];
        }

        // Đếm tổng
        $countStmt = $db->prepare("SELECT COUNT(*) FROM thu_trung_tuyen $baseWhere");
        $countStmt->execute($baseParams);
        $total = (int)$countStmt->fetchColumn();

        if ($total === 0) {
            return 0;
        }

        // === BƯỚC 1: INSERT vào email_queue theo batch nhỏ 50 dòng ===
        // Không kèm UPDATE, không transaction lớn — tránh Supabase statement_timeout
        $batchSize     = 50;
        $offset        = 0;
        $totalEnqueued = 0;
        $insertedIds   = [];

        $insertStmt = $db->prepare(
            "INSERT INTO email_queue (recipient, subject, body, status, category, created_at)
             VALUES (?, ?, ?, 'pending', 'admission_letter', NOW())"
        );

        while ($offset < $total) {
            $params = array_merge($baseParams, [$batchSize, $offset]);
            $fetchStmt = $db->prepare("SELECT * FROM thu_trung_tuyen $baseWhere ORDER BY ma_nganh DESC, diem_xt ASC, id ASC LIMIT ? OFFSET ?");
            $fetchStmt->execute($params);
            $candidates = $fetchStmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($candidates)) break;

            foreach ($candidates as $candidate) {
                if (empty($candidate['email'])) continue;
                $body = $this->renderTemplate($template['body'], $candidate);
                try {
                    $insertStmt->execute([$candidate['email'], $subject, $body]);
                    $insertedIds[] = $candidate['id'];
                    $totalEnqueued++;
                } catch (\Exception $e) {
                    error_log("enqueueAll insert skip id={$candidate['id']}: " . $e->getMessage());
                }
            }

            $offset += $batchSize;
            unset($candidates);
            gc_collect_cycles();
        }

        // === BƯỚC 2: UPDATE status='queued' theo batch nhỏ 50 ID ===
        // Tách hoàn toàn với INSERT để tránh lock contention
        foreach (array_chunk($insertedIds, 50) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            try {
                $db->prepare("UPDATE thu_trung_tuyen SET status = 'queued' WHERE id IN ($placeholders)")
                   ->execute($chunk);
            } catch (\Exception $e) {
                error_log("enqueueAll status update skip: " . $e->getMessage());
            }
        }

        return $totalEnqueued;
    }

    /**
     * Đồng bộ thí sinh trúng tuyển từ kết quả tuyển sinh sang thư trúng tuyển
     */
    public function syncFromResults($sessionId) {
        $sessionId = (int)$sessionId;
        
        // 1. Lấy thông tin đợt tuyển sinh
        $sessionStmt = $this->db->prepare("SELECT ten_dot FROM dot_tuyen_sinh WHERE id = ?");
        $sessionStmt->execute([$sessionId]);
        $session = $sessionStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$session) {
            throw new \Exception("Đợt tuyển sinh không tồn tại.");
        }
        $batchId = "Đồng bộ " . $session['ten_dot'];

        // 2. Lấy danh sách CCCD + Ngành đã tồn tại trong đợt này ở bảng thu_trung_tuyen để tránh trùng lặp
        $existStmt = $this->db->prepare("SELECT so_cccd, ma_nganh FROM thu_trung_tuyen WHERE batch_id = ?");
        $existStmt->execute([$batchId]);
        $existing = [];
        while ($row = $existStmt->fetch(\PDO::FETCH_ASSOC)) {
            $key = $row['so_cccd'] . '|' . $row['ma_nganh'];
            $existing[$key] = true;
        }

        // 3. Lấy dữ liệu từ ket_qua_trung_tuyen JOIN thi_sinh
        $sql = "
            SELECT 
                k.so_cccd, k.ho_ten, k.ngay_sinh, k.sbd, k.khu_vuc, k.doi_tuong, k.to_hop,
                k.diem_mon_1, k.diem_mon_2, k.diem_mon_3, k.diem_to_hop, k.diem_ut, k.ut_quy_doi,
                k.diem_xt, k.ma_nganh, k.ten_nganh, k.phuong_thuc, k.ghi_chu,
                ts.email, ts.dien_thoai as sdt
            FROM ket_qua_trung_tuyen k
            LEFT JOIN thi_sinh ts ON k.so_cccd = ts.so_cccd
            WHERE k.session_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $rows = [];
        $ignored = 0;
        foreach ($candidates as $c) {
            $key = $c['so_cccd'] . '|' . $c['ma_nganh'];
            if (isset($existing[$key])) {
                $ignored++;
                continue;
            }

            // Đánh dấu in-memory để tránh trùng lặp trong chính danh sách đang lặp
            $existing[$key] = true;

            // Xử lý các giá trị mặc định nếu rỗng
            $email = trim($c['email'] ?? '');
            $sdt = trim($c['sdt'] ?? '');
            
            // Một số trường hợp cần chuẩn hóa
            $rows[] = [
                $batchId,
                $c['so_cccd'],
                trim($c['ho_ten'] ?? ''),
                trim($c['ngay_sinh'] ?? ''),
                trim($c['sbd'] ?? ''),
                trim($c['khu_vuc'] ?? ''),
                trim($c['doi_tuong'] ?? ''),
                trim($c['to_hop'] ?? ''),
                $c['diem_mon_1'] !== null ? (float)$c['diem_mon_1'] : 0.0,
                $c['diem_mon_2'] !== null ? (float)$c['diem_mon_2'] : 0.0,
                $c['diem_mon_3'] !== null ? (float)$c['diem_mon_3'] : 0.0,
                $c['diem_to_hop'] !== null ? (float)$c['diem_to_hop'] : 0.0,
                $c['diem_ut'] !== null ? (float)$c['diem_ut'] : 0.0,
                $c['ut_quy_doi'] !== null ? (float)$c['ut_quy_doi'] : 0.0,
                $c['diem_xt'] !== null ? (float)$c['diem_xt'] : 0.0,
                trim($c['ma_nganh'] ?? ''),
                trim($c['ten_nganh'] ?? ''),
                trim($c['phuong_thuc'] ?? ''),
                '', // so_tai_khoan
                '', // ngan_hang
                0,  // so_tien
                '', // noi_dung_ck
                $email,
                $sdt,
                trim($c['ghi_chu'] ?? '')
            ];
        }

        // 4. Batch Insert
        $imported = 0;
        if (!empty($rows)) {
            $batchSize  = 500;
            $colCount   = 25;
            $baseSql = "INSERT INTO thu_trung_tuyen (
                batch_id, so_cccd, ho_ten, ngay_sinh, sbd, khu_vuc, doi_tuong, to_hop,
                diem_mon_1, diem_mon_2, diem_mon_3, diem_to_hop, diem_ut, ut_quy_doi,
                diem_xt, ma_nganh, ten_nganh, phuong_thuc,
                so_tai_khoan, ngan_hang, so_tien, noi_dung_ck,
                email, sdt, ghi_chu
            ) VALUES ";

            $chunks = array_chunk($rows, $batchSize);
            $this->db->beginTransaction();
            try {
                foreach ($chunks as $chunk) {
                    $placeholderRow = '(' . implode(',', array_fill(0, $colCount, '?')) . ')';
                    $sql = $baseSql . implode(',', array_fill(0, count($chunk), $placeholderRow));
                    $flat = array_merge(...$chunk);

                    $this->db->prepare($sql)->execute($flat);
                    $imported += count($chunk);
                }
                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        return ['imported' => $imported, 'ignored' => $ignored];
    }

    /**
     * Chuẩn hóa tiêu đề: viết thường, xóa dấu Tiếng Việt, xóa tất cả các ký tự không phải chữ/số
     */
    private function normalizeHeader($str) {
        $str = mb_strtolower(trim((string)$str), 'UTF-8');
        $unicode = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        ];
        foreach ($unicode as $nonUnicode => $uni) {
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        }
        return preg_replace('/[^a-z0-9]/', '', $str);
    }
}
