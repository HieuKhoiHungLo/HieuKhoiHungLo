<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\AdmissionSession;
use App\Core\Database;
use PDO;

class AdmissionManagementController extends Controller {
    protected $db;
    protected $masterData;
    protected $sessionModel;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->db = Database::getInstance()->getConnection();
        $this->masterData = new MasterData();
        $this->sessionModel = new AdmissionSession();
    }

    public function index() {
        $years = $this->db->query("SELECT DISTINCT nam_tuyen_sinh FROM dot_tuyen_sinh ORDER BY nam_tuyen_sinh DESC")->fetchAll(PDO::FETCH_COLUMN);
        $activeSession = $this->sessionModel->getActiveSession();
        
        $this->view('admin/admission/management', [
            'title' => 'Thiết lập Điểm chuẩn',
            'years' => $years,
            'activeSession' => $activeSession
        ]);
    }

    public function apiGetSessions() {
        $year = $_GET['year'] ?? null;
        if (!$year) {
            $this->json(['status' => false, 'message' => 'Năm không hợp lệ']);
            return;
        }

        $stmt = $this->db->prepare("SELECT id, ten_dot, nam_tuyen_sinh, kich_hoat FROM dot_tuyen_sinh WHERE nam_tuyen_sinh = ? ORDER BY id DESC");
        $stmt->execute([$year]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->json(['status' => true, 'sessions' => $sessions]);
    }

    public function apiGetData() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) {
            $this->json(['status' => false, 'message' => 'Đợt tuyển sinh không hợp lệ']);
            return;
        }

        // Lấy tất cả ngành - chi_tieu và kich_hoat từ dm_nganh (Source of Truth)
        $allMajors = $this->db->query("SELECT ma_nganh, ten_nganh, nhom_nganh, chi_tieu, nguong_hoc_luc, nguong_diem_thpt, COALESCE(kich_hoat, true) as kich_hoat FROM dm_nganh ORDER BY ma_nganh ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Lấy cấu hình điểm chuẩn cho đợt này
        $stmt = $this->db->prepare("SELECT ma_nganh, diem_chuan, tieuchi_phu FROM admission_benchmarks WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $benchmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $benchmarkMap = [];
        foreach ($benchmarks as $b) {
            $benchmarkMap[$b['ma_nganh']] = $b;
        }

        foreach ($allMajors as &$m) {
            $mCode = $m['ma_nganh'];
            $m['has_benchmark'] = isset($benchmarkMap[$mCode]);
            // chi_tieu luôn từ dm_nganh (read-only trên UI)
            $m['chi_tieu'] = (int)($m['chi_tieu'] ?? 0);
            $m['diem_chuan'] = $m['has_benchmark'] ? $benchmarkMap[$mCode]['diem_chuan'] : 0;
            $m['tieuchi_phu'] = $m['has_benchmark'] ? $benchmarkMap[$mCode]['tieuchi_phu'] : '';
            $m['kich_hoat'] = ($m['kich_hoat'] === true || $m['kich_hoat'] === 't' || $m['kich_hoat'] === '1' || $m['kich_hoat'] === 1);
        }

        $this->json(['status' => true, 'data' => $allMajors]);
    }

    public function apiSave() {
        $sessionId = $_POST['session_id'] ?? null;
        $data = $_POST['data'] ?? [];

        if (!$sessionId) {
            $this->json(['status' => false, 'message' => 'Dữ liệu không hợp lệ']);
            return;
        }

        $this->db->beginTransaction();
        try {
            $stmtDel = $this->db->prepare("DELETE FROM admission_benchmarks WHERE session_id = ?");
            $stmtDel->execute([$sessionId]);

            // Lấy chi_tieu từ dm_nganh để đồng bộ
            $chiTieuMap = [];
            $ctRows = $this->db->query("SELECT ma_nganh, COALESCE(chi_tieu, 0) as chi_tieu FROM dm_nganh")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($ctRows as $r) {
                $chiTieuMap[$r['ma_nganh']] = (int)$r['chi_tieu'];
            }

            $stmtIns = $this->db->prepare("INSERT INTO admission_benchmarks (session_id, ma_nganh, diem_chuan, tieuchi_phu) VALUES (?, ?, ?, ?)");
            
            foreach ($data as $item) {
                if (isset($item['has_benchmark']) && ($item['has_benchmark'] == 'true' || $item['has_benchmark'] === true || $item['has_benchmark'] == 1)) {
                    $maNganh = $item['ma_nganh'];
                    $diemChuan = isset($item['diem_chuan']) ? (float)$item['diem_chuan'] : 0.0;
                    $tieuchiPhu = isset($item['tieuchi_phu']) && trim($item['tieuchi_phu']) !== '' ? $item['tieuchi_phu'] : null;

                    $stmtIns->execute([
                        $sessionId,
                        $maNganh,
                        round($diemChuan, 2),
                        $tieuchiPhu
                    ]);
                }
            }

            $this->db->commit();
            $this->json(['status' => true, 'message' => 'Lưu cấu hình thành công']);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['status' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }
}
