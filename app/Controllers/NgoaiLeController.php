<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\NgoaiLe;
use App\Models\MasterData;

class NgoaiLeController extends Controller {
    protected $ngoaiLeModel;
    protected $masterData;

    public function __construct() {
        $this->ngoaiLeModel = new NgoaiLe();
        $this->masterData = new MasterData();
    }

    public function index() {
        $sessionId = $_GET['session_id'] ?? null;
        $sessions = $this->masterData->getSessions();
        
        if (!$sessionId && !empty($sessions)) {
            $sessionId = $sessions[0]['id'];
        }

        $exceptions = [];
        if ($sessionId) {
            $exceptions = $this->ngoaiLeModel->getAllForSession($sessionId);
        }

        // Lấy danh sách ngành để hiển thị dropdown
        $db = \App\Core\Database::getInstance()->getConnection();
        $majors = $db->query("SELECT ma_nganh, ten_nganh FROM dm_nganh WHERE kich_hoat = true")->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('admin/ngoai_le/index', [
            'title' => 'Ngoại Lệ Xét Tuyển',
            'sessions' => $sessions,
            'currentSessionId' => $sessionId,
            'exceptions' => $exceptions,
            'majors' => $majors
        ]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $sessionId = trim($_POST['session_id'] ?? '');
        $cccd = trim($_POST['so_cccd'] ?? '');
        $majorCode = trim($_POST['ma_nganh'] ?? '');
        $status = trim($_POST['trang_thai_ep_buoc'] ?? '');
        $note = trim($_POST['ghi_chu'] ?? '');

        if (!$sessionId || !$cccd || !$majorCode || !in_array($status, ['TrungTuyen', 'Truot'])) {
            $this->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.']);
            return;
        }

        try {
            $result = $this->ngoaiLeModel->saveRule($sessionId, $cccd, $majorCode, $status, $note);
            if ($result) {
                $this->json(['success' => true, 'message' => 'Lưu ngoại lệ thành công.']);
            } else {
                $this->json(['success' => false, 'message' => 'Không thể lưu dữ liệu.']);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $id = trim($_POST['id'] ?? '');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'Thiếu ID.']);
            return;
        }

        try {
            $result = $this->ngoaiLeModel->deleteRule($id);
            if ($result) {
                $this->json(['success' => true, 'message' => 'Đã xóa ngoại lệ.']);
            } else {
                $this->json(['success' => false, 'message' => 'Không thể xóa dữ liệu.']);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function importBoGD() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $sessionId = intval($_POST['session_id'] ?? 0);
        if (!$sessionId) {
            $this->json(['success' => false, 'message' => 'Vui lòng chọn đợt tuyển sinh.']);
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Lỗi tải file. Vui lòng thử lại.']);
            return;
        }

        $filePath = $_FILES['file']['tmp_name'];

        try {
            if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                $this->json(['success' => false, 'message' => 'Thư viện PhpSpreadsheet chưa được cài đặt.']);
                return;
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (empty($rows)) {
                $this->json(['success' => false, 'message' => 'File rỗng.']);
                return;
            }

            // Tìm hàng tiêu đề (Header row)
            $headerRowIndex = 1;
            $colCccd = null;
            $colMajor = null;
            $colReason = null;

            foreach ($rows as $rowIndex => $row) {
                foreach ($row as $colLetter => $cellValue) {
                    if (!$cellValue) continue;
                    $cleanVal = mb_strtolower(trim((string)$cellValue), 'UTF-8');
                    
                    if (!$colCccd && (strpos($cleanVal, 'đdcn') !== false || strpos($cleanVal, 'cccd') !== false || strpos($cleanVal, 'cmnd') !== false)) {
                        $colCccd = $colLetter;
                    }
                    if (!$colMajor && (strpos($cleanVal, 'mã xét tuyển') !== false || strpos($cleanVal, 'mã ngành') !== false || strpos($cleanVal, 'ma_nganh') !== false)) {
                        $colMajor = $colLetter;
                    }
                    if (!$colReason && (strpos($cleanVal, 'kết quả') !== false || strpos($cleanVal, 'nguồn tuyển') !== false || strpos($cleanVal, 'lý do') !== false)) {
                        $colReason = $colLetter;
                    }
                }

                if ($colCccd && $colMajor) {
                    $headerRowIndex = $rowIndex;
                    break;
                }
            }

            if (!$colCccd || !$colMajor) {
                $this->json([
                    'success' => false, 
                    'message' => 'Không tìm thấy cột "ĐDCN / Số CCCD" hoặc "Mã xét tuyển / Mã ngành" trong file.'
                ]);
                return;
            }

            $items = [];
            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex <= $headerRowIndex) continue;

                $rawCccd = trim((string)($row[$colCccd] ?? ''));
                $rawMajor = trim((string)($row[$colMajor] ?? ''));
                $rawReason = $colReason ? trim((string)($row[$colReason] ?? '')) : '';

                // Format CCCD: bỏ ký tự không phải số, pad cho đủ 12 số nếu thiếu
                $cccd = preg_replace('/[^0-9]/', '', $rawCccd);
                if (strlen($cccd) > 0 && strlen($cccd) < 12) {
                    $cccd = str_pad($cccd, 12, '0', STR_PAD_LEFT);
                }

                $majorCode = strtoupper(trim($rawMajor));

                if (!empty($cccd) && !empty($majorCode)) {
                    $items[] = [
                        'cccd' => $cccd,
                        'ma_nganh' => $majorCode,
                        'ghi_chu' => $rawReason ?: 'Không đạt điều kiện nguồn tuyển (Theo Bộ GD&ĐT)'
                    ];
                }
            }

            if (empty($items)) {
                $this->json(['success' => false, 'message' => 'Không tìm thấy dữ liệu hợp lệ trong file.']);
                return;
            }

            $count = $this->ngoaiLeModel->bulkSaveBoGDExceptions($sessionId, $items);
            $this->json([
                'success' => true, 
                'message' => "Đã import thành công {$count} nguyện vọng ngoại lệ từ Bộ GD&ĐT!"
            ]);

        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Lỗi xử lý file: ' . $e->getMessage()]);
        }
    }

    public function deleteBoGD() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $sessionId = intval($_POST['session_id'] ?? 0);
        if (!$sessionId) {
            $this->json(['success' => false, 'message' => 'Vui lòng chọn đợt tuyển sinh.']);
            return;
        }

        try {
            $result = $this->ngoaiLeModel->deleteBoGDExceptions($sessionId);
            if ($result !== false) {
                $this->json(['success' => true, 'message' => 'Đã xóa tất cả dữ liệu ngoại lệ từ Bộ GD&ĐT của đợt này.']);
            } else {
                $this->json(['success' => false, 'message' => 'Không thể xóa dữ liệu.']);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
