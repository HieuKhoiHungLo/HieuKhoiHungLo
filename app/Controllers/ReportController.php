<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\ExportService;
use App\Services\PermissionService;
use App\Models\MasterData;
use ZipStream\ZipStream;
use App\Core\Database;

class ReportController extends Controller {
    // Configuration for parallel download
    private const BATCH_SIZE = 100;
    private const CURL_TIMEOUT = 30;
    
    protected $exportService;
    protected $permissionService;
    protected $masterData;
    protected $db;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->exportService = new ExportService();
        $this->permissionService = new PermissionService();
        $this->masterData = new MasterData();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index() {
        $majors   = $this->masterData->getMajors();
        $sessions = $this->masterData->getAll('dot_tuyen_sinh', 'nam_tuyen_sinh DESC, id DESC');
        
        $activeSession = null;
        foreach ($sessions as $s) {
            if (!empty($s['kich_hoat'])) { $activeSession = $s; break; }
        }

        $years = [];
        foreach ($sessions as $s) {
            $y = $s['nam_tuyen_sinh'] ?? null;
            if ($y && !in_array($y, $years)) $years[] = $y;
        }
        rsort($years);

        $selectedYear = $_GET['year'] ?? ($activeSession['nam_tuyen_sinh'] ?? ($years[0] ?? null));
        $yearSessions = array_values(array_filter($sessions, fn($s) => ($s['nam_tuyen_sinh'] ?? null) == $selectedYear));

        $selectedSessionId = $_GET['session_id'] ?? null;
        if (!$selectedSessionId) {
            if ($activeSession && ($activeSession['nam_tuyen_sinh'] ?? null) == $selectedYear) {
                $selectedSessionId = $activeSession['id'];
            } elseif (!empty($yearSessions)) {
                $selectedSessionId = $yearSessions[0]['id'];
            }
        }

        $stats = $this->exportService->getStatistics(['session_id' => $selectedSessionId]);

        $this->view('admin/reports/index', [
            'majors'            => $majors,
            'sessions'          => $sessions,
            'stats'             => $stats,
            'years'             => $years,
            'yearSessions'      => $yearSessions,
            'selectedYear'      => $selectedYear,
            'selectedSessionId' => $selectedSessionId,
            'allSessions'       => $sessions,
        ]);
    }

    public function exportCandidates() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'status'     => $_GET['status'] ?? null,
            'session_id' => $_GET['session_id'] ?? null,
        ];
        $data = $this->exportService->exportCandidatesToCsv($filters);
        $this->exportService->toExcel($data, 'danh_sach_thi_sinh_' . date('Ymd') . '.xls');
    }

    public function exportAdmitted() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $maNganh = $_GET['ma_nganh'] ?? null;
        if (!$maNganh) {
            die('Vui lòng chọn ngành.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];
        $data = $this->exportService->exportAdmittedByMajor($maNganh, $filters);
        $this->exportService->toExcel($data, 'trung_tuyen_' . $maNganh . '_' . date('Ymd') . '.xls');
    }

    public function exportCertificates() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];
        $data = $this->exportService->exportCertificatesFiltered($filters);
        $this->exportService->toExcel($data, 'danh_sach_chung_chi_nn_' . date('Ymd') . '.xls');
    }

    public function exportAptitudeList() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];
        $data = $this->exportService->exportAptitudeList($filters);
        foreach ($data as &$row) {
            $avatar = $row['Ảnh đại diện'] ?? '';
            if (!empty($avatar) && strpos($avatar, 'http') !== 0) {
                $row['Ảnh đại diện'] = url($avatar);
            }
        }
        unset($row);
        $this->exportService->toExcel($data, 'danh_sach_thi_nang_khieu_' . date('Ymd') . '.xls');
    }

    public function statsApi() {
        header('Content-Type: application/json');
        $filters = ['session_id' => $_GET['session_id'] ?? null];
        echo json_encode($this->exportService->getStatistics($filters));
        exit;
    }

    public function exportMoetInfo() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];
        $data = $this->exportService->exportMoetInfoCsv($filters);
        $this->exportService->toExcel($data, 'moet_thong_tin_diem_thpt_' . date('Ymd') . '.xls');
    }

    public function exportMoetWishes() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];
        $data = $this->exportService->exportMoetWishesCsv($filters);
        $this->exportService->toExcel($data, 'moet_nguyen_vong_' . date('Ymd') . '.xls');
    }

    public function exportMoetTranscripts() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];
        $data = $this->exportService->exportMoetTranscriptsCsv($filters);
        $this->exportService->toExcel($data, 'moet_diem_hoc_ba_' . date('Ymd') . '.xls');
    }

    public function exportAdmissionReport() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'major'      => $_GET['major'] ?? null,
        ];
        $data = $this->exportService->exportAdmissionData($filters);
        $this->exportService->toExcel($data, 'du_lieu_xet_tuyen_' . date('Ymd') . '.xls');
    }

    public function exportAllAdmittedReport() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = ['session_id' => $_GET['session_id'] ?? null];
        $data = $this->exportService->exportAdmittedByMajor(null, $filters);
        $this->exportService->toExcel($data, 'danh_sach_trung_tuyen_toan_bo_' . date('Ymd') . '.xls');
    }

    public function downloadAllPhotos() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }

        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];

        $list = $this->exportService->exportAllCandidatePhotos($filters);
        if (empty($list)) {
            die('Không có dữ liệu ảnh thẻ thí sinh để tải.');
        }

        $zipFileName = 'anh_the_tat_ca_thi_sinh_' . date('Ymd_His') . '.zip';
        $this->processAvatarZipDownload($list, $zipFileName, 'Không tìm thấy tệp ảnh nào để nén.');
    }

    public function downloadAptitudePhotos() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }

        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];

        $list = $this->exportService->exportAptitudeList($filters);
        if (empty($list)) {
            die('Không có dữ liệu thí sinh năng khiếu để tải ảnh.');
        }

        $zipFileName = 'anh_the_nang_khieu_' . date('Ymd_His') . '.zip';
        $this->processAvatarZipDownload($list, $zipFileName, 'Không tìm thấy tệp ảnh nào để nén.');
    }

    private function processAvatarZipDownload(array $list, string $zipFileName, string $emptyMsg) {
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '1024M');

        $zipFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName;

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            die('Không thể tạo file ZIP.');
        }

        $publicPath = __DIR__ . '/../../public';
        $addedFiles = 0;
        $remoteFiles = [];
        $localFiles = [];

        foreach ($list as $idx => $c) {
            $path = $c['anh_dai_dien'] ?? '';
            if (!$path) continue;
            if (strpos($path, 'http') === 0) {
                $remoteFiles[$idx] = $path;
            } else {
                $localFiles[$idx] = $path;
            }
        }

        foreach ($localFiles as $idx => $path) {
            $c = $list[$idx];
            $fullPath = $publicPath . $path;
            if (file_exists($fullPath) && is_file($fullPath)) {
                $ext = pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'jpg';
                $zipName = "{$c['Số CCCD']}_avatar.{$ext}";
                $zip->addFile($fullPath, $zipName);
                $addedFiles++;
            }
        }

        if (!empty($remoteFiles)) {
            $chunks = array_chunk($remoteFiles, self::BATCH_SIZE, true);
            foreach ($chunks as $chunk) {
                set_time_limit(300);
                $fastUrls = array_map([$this, 'getFastDownloadUrl'], $chunk);
                $contents = $this->fetchUrlsParallel($fastUrls);
                foreach ($contents as $idx => $content) {
                    if (!$content) continue;
                    $c = $list[$idx];
                    $url = $remoteFiles[$idx];
                    $ext = 'jpg';
                    if (strpos($url, 'drive.google.com/thumbnail') === false) {
                        $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    }
                    $zipName = "{$c['Số CCCD']}_avatar.{$ext}";
                    $zip->addFromString($zipName, $content);
                    $addedFiles++;
                }
            }
        }

        $zip->close();
        if ($addedFiles === 0) {
            @unlink($zipFilePath);
            die($emptyMsg);
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipFilePath));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($zipFilePath);
        @unlink($zipFilePath);
        exit;
    }

    public function downloadCertificatePhotos() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }

        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '1024M');

        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];

        $list = $this->exportService->exportCertificatesFiltered($filters);

        if (empty($list)) {
            die('Không có dữ liệu chứng chỉ để tải ảnh.');
        }

        $zipFileName = 'anh_minh_chung_cc_' . date('Ymd_His') . '.zip';
        $zipFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName;

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            die('Không thể tạo file ZIP.');
        }

        $publicPath = __DIR__ . '/../../public';
        $addedFiles = 0;
        $remoteQueue = [];

        foreach ($list as $c) {
            $paths = $c['file_minh_chung_cc'] ?? '';
            if (!$paths) continue;
            $files = explode(',', $paths);
            foreach ($files as $i => $path) {
                $path = trim($path);
                if (!$path) continue;
                if (strpos($path, 'http') === 0) {
                    $remoteQueue[] = [
                        'url'   => $path,
                        'cccd'  => $c['Số CCCD'],
                        'hoTen' => $c['Họ và Tên'],
                        'type'  => $c['Loại chứng chỉ'] ?? 'CC',
                        'index' => $i + 1,
                        'total' => count($files),
                    ];
                } else {
                    $fullPath = $publicPath . $path;
                    if (file_exists($fullPath) && is_file($fullPath)) {
                        $ext = pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'jpg';
                        $zipName = "{$c['Số CCCD']}_Cert_" . ($i + 1) . ".{$ext}";
                        $zip->addFile($fullPath, $zipName);
                        $addedFiles++;
                    }
                }
            }
        }
        
        if (!empty($remoteQueue)) {
            $chunks = array_chunk($remoteQueue, self::BATCH_SIZE);
            foreach ($chunks as $chunk) {
                set_time_limit(300);
                $urls = array_column($chunk, 'url');
                $fastUrls = array_map([$this, 'getFastDownloadUrl'], $urls);
                
                $contents = $this->fetchUrlsParallel($fastUrls);

                foreach ($contents as $idx => $content) {
                    if (!$content) {
                        error_log("Failed to download: " . $chunk[$idx]['url']);
                        continue;
                    }
                    $item = $chunk[$idx];
                    $ext = 'jpg';
                    if (strpos($item['url'], 'drive.google.com/thumbnail') === false) {
                        $ext = pathinfo(parse_url($item['url'], PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    }
                    $zipName = "{$item['cccd']}_Cert_{$item['index']}.{$ext}";
                    $zip->addFromString($zipName, $content);
                    $addedFiles++;
                }
            }
        }

        $zip->close();

        if ($addedFiles === 0) {
            @unlink($zipFilePath);
            die('Không tìm thấy tệp ảnh minh chứng nào để nén.');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipFilePath));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($zipFilePath);
        @unlink($zipFilePath);
        exit;
    }

    private function fetchUrlsParallel(array $urls): array {
        if (!function_exists('curl_multi_init')) {
            $results = [];
            foreach ($urls as $key => $url) {
                $results[$key] = @file_get_contents($url);
            }
            return $results;
        }

        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($urls as $key => $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_ENCODING, ''); // Hỗ trợ gzip/deflate để tải nhanh hơn
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AdmissionsPortal/1.0');
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        $active = null;
        $start_time = microtime(true);
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) === -1) {
                usleep(10000); // 10ms
            }
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($handles as $key => $ch) {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code === 200) {
                $results[$key] = curl_multi_getcontent($ch);
            } else {
                $results[$key] = null;
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $results;
    }
    public function downloadDataAudit() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }

        $type = $_GET['type'] ?? '';
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
        ];

        $typeNames = [
            'dob'           => 'kiem_tra_ngay_sinh',
            'wishes'        => 'chua_co_nguyen_vong',
            'contact'       => 'thieu_thong_tin_lien_he',
            'priority'      => 'thieu_thong_tin_uu_tien',
            'free'          => 'thi_sinh_tu_do',
            'scores'        => 'chua_co_diem_hoc_ba',
            'comprehensive' => 'tong_hop_ho_so_can_ra_soat',
        ];

        if (!isset($typeNames[$type])) {
            die('Loại kiểm tra không hợp lệ.');
        }

        $data = $this->exportService->exportDataAudit($type, $filters);
        if (empty($data)) {
            die('Không có dữ liệu cho tiêu chí này.');
        }

        $filename = 'danh_sach_' . $typeNames[$type] . '_' . date('Ymd_His') . '.xls';
        $this->exportService->toExcel($data, $filename);
    }

    private function getFastDownloadUrl($originalUrl): string {
        if (strpos($originalUrl, 'drive.google.com') !== false) {
            $id = '';
            if (preg_match('/d\/([a-zA-Z0-9_-]+)/', $originalUrl, $matches)) {
                $id = $matches[1];
            } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $originalUrl, $matches)) {
                $id = $matches[1];
            }
            if ($id) {
                return 'https://drive.google.com/thumbnail?id=' . $id . '&sz=w1000-h1000';
            }
        }
        return $originalUrl;
    }

    private function safeFileName($str): string {
        if (empty($str)) return 'file';
        $unicode = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'D' => 'Đ',
            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y' => 'Ý|Ì|Ỷ|Ỹ|Ị',
        ];
        foreach ($unicode as $nonUnicode => $uni) {
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        }
        $str = preg_replace('/[^A-Za-z0-9_\-]/', '_', $str);
        return trim($str, '_');
    }
}
