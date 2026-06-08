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
     * Import thí sinh từ file Excel
     */
    public function importFromExcel($filePath, $batchId) {
        set_time_limit(0); // Cho phép chạy không giới hạn thời gian đối với file lớn
        
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        
        $data = $sheet->rangeToArray(
            'A1:' . $highestColumn . $highestRow,
            null,
            true,
            true,
            true
        );

        // Map column headers to finding indexes
        $headers = array_map('trim', array_map('strval', $data[1]));
        $colMap = [];
        foreach ($headers as $col => $header) {
            $h = strtolower($header);
            if ($h == 'cccd') $colMap['cccd'] = $col;
            if ($h == 'hoten') $colMap['hoten'] = $col;
            if ($h == 'ngaysinh') $colMap['ngaysinh'] = $col;
            if ($h == 'sbd') $colMap['sbd'] = $col;
            if ($h == 'kv') $colMap['kv'] = $col;
            if ($h == 'doituong') $colMap['doituong'] = $col;
            if ($h == 'tohop') $colMap['tohop'] = $col;
            if ($h == 'dm1') $colMap['dm1'] = $col;
            if ($h == 'dm2') $colMap['dm2'] = $col;
            if ($h == 'dm3') $colMap['dm3'] = $col;
            if ($h == 'diemtohop') $colMap['diemtohop'] = $col;
            if ($h == 'diemut') $colMap['diemut'] = $col;
            if ($h == 'utq') $colMap['utq'] = $col;
            if ($h == 'diemxt') $colMap['diemxt'] = $col;
            if ($h == 'manganh') $colMap['manganh'] = $col;
            if ($h == 'nganh' || $h == 'ten_nganh') $colMap['nganh'] = $col;
            if ($h == 'sotk') $colMap['sotk'] = $col;
            if ($h == 'nganhang') $colMap['nganhang'] = $col;
            if ($h == 'sotien') $colMap['sotien'] = $col;
            if ($h == 'noidung' || $h == 'noidungck') $colMap['noidung'] = $col;
            if ($h == 'email') $colMap['email'] = $col;
            if ($h == 'sdt') $colMap['sdt'] = $col;
            if ($h == 'ghichu') $colMap['ghichu'] = $col;
            if ($h == 'phuongthuc') $colMap['phuongthuc'] = $col;
        }

        if (!isset($colMap['email']) || !isset($colMap['cccd'])) {
            throw new \Exception("File Excel thiếu cột 'Email' hoặc 'CCCD' bắt buộc.");
        }

        $imported = 0;
        $ignored = 0;

        $stmt = $this->db->prepare("
            INSERT INTO thu_trung_tuyen (
                batch_id, so_cccd, ho_ten, ngay_sinh, sbd, khu_vuc, doi_tuong, to_hop,
                diem_mon_1, diem_mon_2, diem_mon_3, diem_to_hop, diem_ut, ut_quy_doi,
                diem_xt, ma_nganh, ten_nganh, phuong_thuc,
                so_tai_khoan, ngan_hang, so_tien, noi_dung_ck,
                email, sdt, ghi_chu, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, 'Chờ duyệt'
            )
        ");

        $this->db->beginTransaction();

        // Prepare duplicate check statement
        $dupStmt = $this->db->prepare("SELECT COUNT(*) FROM thu_trung_tuyen WHERE so_cccd = ? AND batch_id = ?");

        try {
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = $data[$row];
                
                $email = trim($rowData[$colMap['email']] ?? '');
                $cccd = trim($rowData[$colMap['cccd']] ?? '');
                
                // Bỏ qua nếu email/cccd rỗng
                if (empty($email) || empty($cccd)) {
                    $ignored++;
                    continue;
                }

                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $ignored++;
                    continue;
                }

                // Kiểm tra trùng lặp CCCD trong cùng đợt
                $dupStmt->execute([$cccd, $batchId]);
                if ((int)$dupStmt->fetchColumn() > 0) {
                    $ignored++;
                    continue;
                }

                // Parse số tiền (xóa dấu chấm phẩy, lấy số nguyên)
                $soTienStr = strval($rowData[$colMap['sotien']] ?? '0');
                $soTienStr = preg_replace('/[^0-9]/', '', $soTienStr);
                $soTien = $soTienStr ? (int)$soTienStr : 0;

                // Hàm hỗ trợ ép kiểu số thực
                $parseFloat = function($val) {
                    $val = str_replace(',', '.', trim($val ?? '0'));
                    return is_numeric($val) ? (float)$val : 0;
                };

                $stmt->execute([
                    $batchId,
                    $cccd,
                    trim($rowData[$colMap['hoten']] ?? ''),
                    trim($rowData[$colMap['ngaysinh']] ?? ''),
                    trim($rowData[$colMap['sbd']] ?? ''),
                    trim($rowData[$colMap['kv']] ?? ''),
                    trim($rowData[$colMap['doituong']] ?? ''),
                    trim($rowData[$colMap['tohop']] ?? ''),
                    $parseFloat($rowData[$colMap['dm1']] ?? null),
                    $parseFloat($rowData[$colMap['dm2']] ?? null),
                    $parseFloat($rowData[$colMap['dm3']] ?? null),
                    $parseFloat($rowData[$colMap['diemtohop']] ?? null),
                    $parseFloat($rowData[$colMap['diemut']] ?? null),
                    $parseFloat($rowData[$colMap['utq']] ?? null),
                    $parseFloat($rowData[$colMap['diemxt']] ?? null),
                    trim($rowData[$colMap['manganh']] ?? ''),
                    trim($rowData[$colMap['nganh']] ?? ''),
                    trim($rowData[$colMap['phuongthuc']] ?? ''),
                    str_replace(' ', '', trim($rowData[$colMap['sotk']] ?? '')), // clear spaces
                    trim($rowData[$colMap['nganhang']] ?? ''),
                    $soTien,
                    trim($rowData[$colMap['noidung']] ?? ''),
                    $email,
                    trim($rowData[$colMap['sdt']] ?? ''),
                    trim($rowData[$colMap['ghichu']] ?? '')
                ]);
                
                $imported++;
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
        $dataSql = "SELECT * " . $sql . " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
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
        $stmt = $this->db->prepare("SELECT * FROM thu_trung_tuyen WHERE id IN ($placeholders)");
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
        $stmt = $this->db->prepare("SELECT * FROM thu_trung_tuyen WHERE batch_id = ? AND status IN ('pending', 'failed')");
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
            '{{DiemXT}}' => $data['diem_xt'] ?? '',
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
        $stmt = $this->db->prepare("DELETE FROM thu_trung_tuyen WHERE batch_id = ?");
        return $stmt->execute([$batchId]);
    }
}
