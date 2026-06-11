<?php
namespace App\Services;

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdmissionResultService {
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
            "SELECT so_cccd FROM ket_qua_trung_tuyen WHERE batch_id = ?"
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

        $baseSql = "INSERT INTO ket_qua_trung_tuyen (
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
            FROM ket_qua_trung_tuyen
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

        $sql = "FROM ket_qua_trung_tuyen WHERE 1=1";
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
        $stmt = $this->db->prepare("SELECT * FROM ket_qua_trung_tuyen WHERE id IN ($placeholders) ORDER BY ma_nganh DESC, diem_xt ASC, id ASC");
        $stmt->execute($ids);
        $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $enqueuedCount = 0;
        foreach ($candidates as $candidate) {
            if (empty($candidate['email'])) continue;

            $subject = $template['subject'] ?? 'Thông báo';
            $body = $this->renderTemplate($template['body'], $candidate);
            
            $this->mailer->enqueue($candidate['email'], $subject, $body, true, 'admission_letter');
            
            $upd = $this->db->prepare("UPDATE ket_qua_trung_tuyen SET status = 'queued' WHERE id = ?");
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
        $stmt = $this->db->prepare("DELETE FROM ket_qua_trung_tuyen WHERE id IN ($placeholders)");
        return $stmt->execute($ids);
    }

    /**
     * Gắn toàn bộ email đợt này vào queue để gửi dần
     */
    public function enqueueBatch($batchId) {
        $stmt = $this->db->prepare("SELECT * FROM ket_qua_trung_tuyen WHERE batch_id = ? AND status IN ('pending', 'failed') ORDER BY ma_nganh DESC, diem_xt ASC, id ASC");
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
            $upd = $this->db->prepare("UPDATE ket_qua_trung_tuyen SET status = 'queued' WHERE id = ?");
            $upd->execute([$candidate['id']]);

            $enqueuedCount++;
        }

        return $enqueuedCount;
    }

    /**
     * Render nội dung thư
     */
    public function renderTemplate($templateHtml, $data) {
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

        $replacements = [
            '{{HoTen}}' => $data['ho_ten'] ?? '',
            '{{name}}' => $data['ho_ten'] ?? '',
            '{{SBD}}' => $data['sbd'] ?? '',
            '{{NgaySinh}}' => $data['ngay_sinh'] ?? '',
            '{{CCCD}}' => $data['so_cccd'] ?? '',
            '{{KhuVuc}}' => $data['khu_vuc'] ?? '',
            '{{DoiTuong}}' => $data['doi_tuong'] ?? '',
            '{{PhuongThuc}}' => $data['phuong_thuc'] ?? '',
            '{{ToHop}}' => $data['to_hop'] ?? '',
            '{{DM1}}' => $data['diem_mon_1'] ?? '',
            '{{DM2}}' => $data['diem_mon_2'] ?? '',
            '{{DM3}}' => $data['diem_mon_3'] ?? '',
            '{{DiemToHop}}' => $data['diem_to_hop'] ?? '',
            '{{DiemUT}}' => $data['diem_ut'] ?? '',
            '{{UTQ}}' => $data['ut_quy_doi'] ?? '',
            '{{DiemXT}}' => rtrim(rtrim(number_format((float)($data['diem_xt'] ?? 0), 3, '.', ''), '0'), '.'),
            '{{Nganh}}' => $data['ten_nganh'] ?? '',
            '{{major}}' => $data['ten_nganh'] ?? '',
            '{{MaNganh}}' => $data['ma_nganh'] ?? '',
            '{{ChiTieu}}' => $chiTieu,
            '{{DiemNamTruoc}}' => $diemNamTruoc,
            '{{XepHang}}' => $data['ghi_chu'] ?? '',
            '{{GhiChu}}' => $data['ghi_chu'] ?? '',
            '{{SoTien}}' => number_format((float)($data['so_tien'] ?? 0), 0, ',', '.'),
        ];

        // Tạo QR Code urls
        $bankName = strtolower(str_replace(' ', '', $data['ngan_hang'] ?? ''));
        $accountNum = $data['so_tai_khoan'] ?? '';
        $amount = $data['so_tien'] ?? 0;
        $content = urlencode(trim($data['noi_dung_ck'] ?? ''));

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
        $stmt = $this->db->prepare("DELETE FROM ket_qua_trung_tuyen WHERE batch_id = ?");
        return $stmt->execute([$batchId]);
    }

    /**
     * Xóa toàn bộ dữ liệu
     */
    public function deleteAll() {
        return $this->db->exec("DELETE FROM ket_qua_trung_tuyen");
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
            $stmt = $this->db->prepare("SELECT * FROM ket_qua_trung_tuyen WHERE id IN ($placeholders)");
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
        $countStmt = $db->prepare("SELECT COUNT(*) FROM ket_qua_trung_tuyen $baseWhere");
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
            $fetchStmt = $db->prepare("SELECT * FROM ket_qua_trung_tuyen $baseWhere ORDER BY ma_nganh DESC, diem_xt ASC, id ASC LIMIT ? OFFSET ?");
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
                $db->prepare("UPDATE ket_qua_trung_tuyen SET status = 'queued' WHERE id IN ($placeholders)")
                   ->execute($chunk);
            } catch (\Exception $e) {
                error_log("enqueueAll status update skip: " . $e->getMessage());
            }
        }

        return $totalEnqueued;
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
