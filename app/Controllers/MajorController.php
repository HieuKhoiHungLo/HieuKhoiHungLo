<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\QuanTriVien;

class MajorController extends Controller {
    protected $masterData;
    protected $currentUser;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->masterData = new MasterData();

        $adminModel = new QuanTriVien();
        $this->currentUser = $adminModel->find($_SESSION['admin_id']);

        if (!$this->currentUser || !$this->currentUser['is_active']) {
             session_destroy();
             $this->redirect(url('/admin/login'));
        }

        if (!QuanTriVien::hasPermission($this->currentUser, 'master_data')) {
            echo "Bạn không có quyền truy cập chức năng này.";
            exit;
        }
    }

    public function index() {
        $majors = $this->masterData->getMajorsWithCombinations();
        
        foreach ($majors as &$m) {
            $m['combination_ids'] = !empty($m['combination_list']) ? explode(', ', $m['combination_list']) : [];
        }
        
        $combinations = $this->masterData->getCombinations(); 
        $provinces = $this->masterData->getProvinces();

        $this->view('admin/master_data/majors', [
            'majors' => $majors, 
            'combinations' => $combinations,
            'provinces' => $provinces,
            'user' => $this->currentUser
        ]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $this->masterData->create('dm_nganh', [
                    'ma_nganh' => $_POST['ma_nganh'],
                    'ten_nganh' => $_POST['ten_nganh'],
                    'chi_tieu' => $_POST['chi_tieu'] ?: null,
                    'nhom_nganh' => $_POST['nhom_nganh'] ?? 'Khac',
                    'nguong_hoc_luc' => $_POST['nguong_hoc_luc'] ?: null,
                    'nguong_diem_thpt' => $_POST['nguong_diem_thpt'] ?: null,
                    'nguong_diem_hocba' => $_POST['nguong_diem_hocba'] ?: null,
                    'khoi_xet_tuyen' => implode(', ', $_POST['combinations'] ?? []), 
                    'diem_nam_truoc' => $_POST['diem_nam_truoc'] ?: null,
                    'ghi_chu' => $_POST['ghi_chu'],
                    'khu_vuc_tuyen_sinh' => !empty($_POST['provinces']) ? implode(',', $_POST['provinces']) : null,
                    'co_xet_chung_chi' => isset($_POST['co_xet_chung_chi']) ? 'true' : 'false',
                    'co_diem_nangkhieu_thpt' => isset($_POST['co_diem_nangkhieu_thpt']) ? 'true' : 'false',
                    'co_diem_nangkhieu_hochba' => isset($_POST['co_diem_nangkhieu_hochba']) ? 'true' : 'false'
                ]);
                $this->masterData->saveMajorCombinations($_POST['ma_nganh'], $_POST['combinations'] ?? []);
                $this->clearMajorCaches();

            } elseif ($action === 'update') {
                $oldMa = $_POST['old_ma'];
                $newMa = $_POST['ma_nganh'];
                
                $this->masterData->update('dm_nganh', $oldMa, [
                    'ma_nganh' => $newMa,
                    'ten_nganh' => $_POST['ten_nganh'],
                    'chi_tieu' => $_POST['chi_tieu'] ?: null,
                    'nhom_nganh' => $_POST['nhom_nganh'] ?? 'Khac',
                    'nguong_hoc_luc' => $_POST['nguong_hoc_luc'] ?: null,
                    'nguong_diem_thpt' => $_POST['nguong_diem_thpt'] ?: null,
                    'nguong_diem_hocba' => $_POST['nguong_diem_hocba'] ?: null,
                    'khoi_xet_tuyen' => implode(', ', $_POST['combinations'] ?? []), 
                    'diem_nam_truoc' => $_POST['diem_nam_truoc'] ?: null,
                    'ghi_chu' => $_POST['ghi_chu'],
                    'khu_vuc_tuyen_sinh' => !empty($_POST['provinces']) ? implode(',', $_POST['provinces']) : null,
                    'co_xet_chung_chi' => isset($_POST['co_xet_chung_chi']) ? 'true' : 'false',
                    'co_diem_nangkhieu_thpt' => isset($_POST['co_diem_nangkhieu_thpt']) ? 'true' : 'false',
                    'co_diem_nangkhieu_hochba' => isset($_POST['co_diem_nangkhieu_hochba']) ? 'true' : 'false'
                ], 'ma_nganh');
                
                // If ID changed, clear old relationships to avoid orphans
                if ($oldMa !== $newMa) {
                    $this->masterData->delete('dm_nganh_to_hop', $oldMa, 'ma_nganh');
                }
                
                $this->masterData->saveMajorCombinations($newMa, $_POST['combinations'] ?? []);
                $this->clearMajorCaches();
            }
            $this->redirect(url('/admin/master-data/majors'));
        }
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $ma = $_POST['ma'] ?? '';
            if ($ma) {
                $this->masterData->delete('dm_nganh_to_hop', $ma, 'ma_nganh');
                $this->masterData->delete('dm_nganh', $ma, 'ma_nganh');
                $this->clearMajorCaches();
                $_SESSION['success'] = "Xóa ngành thành công";
            }
            $this->redirect(url('/admin/master-data/majors'));
        } else {
             $this->redirect(url('/admin/master-data/majors'));
        }
    }

    public function toggleActive() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $ma = $_POST['ma_nganh'] ?? '';
            if ($ma) {
                $db = \App\Core\Database::getInstance()->getConnection();
                // Đảo trạng thái kich_hoat
                $stmt = $db->prepare("UPDATE dm_nganh SET kich_hoat = NOT COALESCE(kich_hoat, true) WHERE ma_nganh = ?");
                $stmt->execute([$ma]);
                
                // Xóa cache
                $this->clearMajorCaches();
                
                // Lấy trạng thái mới
                $stmt2 = $db->prepare("SELECT COALESCE(kich_hoat, true) as kich_hoat FROM dm_nganh WHERE ma_nganh = ?");
                $stmt2->execute([$ma]);
                $result = $stmt2->fetch(\PDO::FETCH_ASSOC);
                
                $this->json([
                    'status' => true, 
                    'kich_hoat' => ($result['kich_hoat'] === true || $result['kich_hoat'] === 't' || $result['kich_hoat'] === '1'),
                    'message' => 'Cập nhật trạng thái thành công'
                ]);
                return;
            }
            $this->json(['status' => false, 'message' => 'Mã ngành không hợp lệ']);
        }
    }

    public function actions() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';
            try {
                if ($action === 'bulk_delete') {
                    $ids = $_POST['ids'] ?? []; 
                    if (!empty($ids)) {
                        // Delete relationships first
                        $this->masterData->deleteMany('dm_nganh_to_hop', $ids, 'ma_nganh');
                        $this->masterData->deleteMany('dm_nganh', $ids, 'ma_nganh');
                        $this->clearMajorCaches();
                        $_SESSION['success'] = "Đã xóa " . count($ids) . " ngành";
                    }
                } elseif ($action === 'import') {
                    $this->import();
                }
                $this->redirect(url('/admin/master-data/majors'));
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $this->redirect(url('/admin/master-data/majors'));
            }
        }
    }

    public function export() {
        $this->validateCsrf();
        $majors = $this->masterData->getMajorsWithCombinations();
        
        $data = [];
        foreach ($majors as $row) {
            $data[] = [
                'Mã Ngành' => $row['ma_nganh'], 
                'Tên Ngành' => $row['ten_nganh'], 
                'Chỉ Tiêu' => $row['chi_tieu'], 
                'Điểm 2025' => $row['diem_nam_truoc'], 
                'Khối Xét Tuyển (Cách nhau dấu phẩy)' => $row['combination_list'] ?? '', 
                'Ghi Chú' => $row['ghi_chu']
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'ds_nganh_' . date('Y-m-d') . '.xls');
    }

    public function template() {
        $data = [[
            'Mã Ngành' => '7480201', 
            'Tên Ngành' => 'Công nghệ thông tin', 
            'Chỉ Tiêu' => '200', 
            'Điểm 2025' => '21.5', 
            'Khối Xét Tuyển (Cách nhau dấu phẩy)' => 'A00, A01, D01', 
            'Ghi Chú' => 'Chương trình chuẩn'
        ]];

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'mau_nhap_nganh.xls');
    }

    private function import() {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Vui lòng chọn file hợp lệ.");
        }
        
        $filePath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];

        $rows = $this->parseUploadedFile($filePath, $fileName);

        if (empty($rows)) {
            throw new \Exception("File trống hoặc không thể đọc được dữ liệu.");
        }
        
        // Find header row and map column indices
        $headerRowIdx = -1;
        $colMap = [
            'ma' => 0,
            'ten' => 1,
            'chitieu' => 2,
            'diem' => 3,
            'khoi' => 4,
            'ghichu' => 5
        ];

        foreach ($rows as $rIdx => $row) {
            if (!is_array($row) || empty($row)) continue;
            
            $rowStr = mb_strtolower(implode(' ', array_map('strval', $row)), 'UTF-8');
            if (strpos($rowStr, 'mã') !== false || strpos($rowStr, 'ma_nganh') !== false || strpos($rowStr, 'tên ngành') !== false || strpos($rowStr, 'ten_nganh') !== false) {
                $headerRowIdx = $rIdx;
                // Dynamically map columns if matching text found
                foreach ($row as $cIdx => $cellVal) {
                    $cellStr = mb_strtolower(trim((string)$cellVal), 'UTF-8');
                    if (strpos($cellStr, 'mã') !== false) $colMap['ma'] = $cIdx;
                    elseif (strpos($cellStr, 'tên') !== false) $colMap['ten'] = $cIdx;
                    elseif (strpos($cellStr, 'tiêu') !== false || strpos($cellStr, 'chi_tieu') !== false) $colMap['chitieu'] = $cIdx;
                    elseif (strpos($cellStr, 'điểm') !== false || strpos($cellStr, 'diem') !== false) $colMap['diem'] = $cIdx;
                    elseif (strpos($cellStr, 'khối') !== false || strpos($cellStr, 'tổ hợp') !== false || strpos($cellStr, 'to_hop') !== false) $colMap['khoi'] = $cIdx;
                    elseif (strpos($cellStr, 'ghi chú') !== false || strpos($cellStr, 'ghi_chu') !== false) $colMap['ghichu'] = $cIdx;
                }
                break;
            }
        }

        $startIdx = ($headerRowIdx >= 0) ? $headerRowIdx + 1 : 0;
        $count = 0;
        $errors = [];
        
        for ($i = $startIdx; $i < count($rows); $i++) {
            $data = $rows[$i];
            if (!is_array($data) || count($data) < 2) continue;
            
            $ma = trim((string)($data[$colMap['ma']] ?? ''));
            $ten = trim((string)($data[$colMap['ten']] ?? ''));
            $chitieu = trim((string)($data[$colMap['chitieu']] ?? ''));
            $diem = trim((string)($data[$colMap['diem']] ?? ''));
            $khoi = trim((string)($data[$colMap['khoi']] ?? ''));
            $ghichu = trim((string)($data[$colMap['ghichu']] ?? ''));
            
            // Ignore header repeated or empty row
            if (!$ma || !$ten || mb_strtolower($ma) === 'mã ngành' || mb_strtolower($ma) === 'mã') continue;
            
            // Normalize numeric values
            $chiTieuVal = ($chitieu !== '' && is_numeric(str_replace(',', '', $chitieu))) ? (int)str_replace(',', '', $chitieu) : null;
            $diemVal = ($diem !== '' && is_numeric(str_replace(',', '.', $diem))) ? (float)str_replace(',', '.', $diem) : null;

            try {
                $exists = $this->masterData->find('dm_nganh', $ma, 'ma_nganh');
                $payload = [
                    'ma_nganh' => $ma,
                    'ten_nganh' => $ten,
                    'chi_tieu' => $chiTieuVal,
                    'diem_nam_truoc' => $diemVal,
                    'khoi_xet_tuyen' => $khoi ?: null,
                    'ghi_chu' => $ghichu
                ];

                if ($exists) {
                    $this->masterData->update('dm_nganh', $ma, $payload, 'ma_nganh');
                } else {
                    $this->masterData->create('dm_nganh', $payload);
                }

                // Handle combinations
                if ($khoi) {
                    $comboCodes = array_map('trim', preg_split('/[,;\s]+/', $khoi));
                    $comboCodes = array_filter($comboCodes);
                    $this->masterData->saveMajorCombinations($ma, $comboCodes);
                }
                
                $count++;
            } catch (\Exception $e) {
                $errors[] = "Dòng " . ($i + 1) . " ($ma): " . $e->getMessage();
            }
        }

        $this->clearMajorCaches();
        
        if ($count > 0) {
            $_SESSION['success'] = "Đã nhập thành công $count ngành từ file " . htmlspecialchars($fileName) . ".";
        } else {
            $_SESSION['error'] = "Không nhập được dữ liệu từ file. Vui lòng kiểm tra lại cấu trúc file (" . implode(', ', array_slice($errors, 0, 3)) . ")";
        }
    }

    private function parseUploadedFile($filePath, $fileName) {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $rows = [];

        if ($extension === 'xlsx') {
            $xlsxFile = __DIR__ . '/../Services/SimpleXLSX.php';
            if (file_exists($xlsxFile)) {
                require_once $xlsxFile;
                if ($xlsx = \Shuchkin\SimpleXLSX::parse($filePath)) {
                    $rows = $xlsx->rows();
                    unset($xlsx);
                    return $rows;
                }
            }
        }

        if ($extension === 'xls') {
            $xlsFile = __DIR__ . '/../Services/SimpleXLS.php';
            if (file_exists($xlsFile)) {
                require_once $xlsFile;
                if ($xls = \Shuchkin\SimpleXLS::parse($filePath)) {
                    $rows = $xls->rows();
                    unset($xls);
                    return $rows;
                }
            }
        }

        // PhpOffice\PhpSpreadsheet fallback
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                unset($spreadsheet);
                if (!empty($rows)) {
                    return $rows;
                }
            } catch (\Exception $e) {
                // Fallback below
            }
        }

        // CSV or HTML-table fallback
        $content = file_get_contents($filePath);
        if (strpos($content, '<table') !== false) {
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
            $trList = $dom->getElementsByTagName('tr');
            foreach ($trList as $tr) {
                $row = [];
                $tdList = $tr->getElementsByTagName('td');
                if ($tdList->length === 0) {
                    $tdList = $tr->getElementsByTagName('th');
                }
                foreach ($tdList as $td) {
                    $row[] = trim($td->textContent);
                }
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
            return $rows;
        }

        // CSV parsing with auto delimiter detection
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
        $delimiter = ",";
        if (isset($lines[0])) {
            if (substr_count($lines[0], "\t") > substr_count($lines[0], ",")) $delimiter = "\t";
            elseif (substr_count($lines[0], ";") > substr_count($lines[0], ",")) $delimiter = ";";
        }

        $handle = fopen($filePath, "r");
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) rewind($handle);

        while (($data = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
            $rows[] = $data;
        }
        fclose($handle);

        return $rows;
    }

    private function clearMajorCaches() {
        \App\Core\Cache::forget('master_majors');
        \App\Core\Cache::forget('master_majors_combinations');
        \App\Core\Cache::forget('master_active_majors_combinations');
        \App\Core\Cache::forget('active_majors_with_combinations_v2');
        \App\Core\Cache::forget('majors_with_combinations_v2');
    }
}
