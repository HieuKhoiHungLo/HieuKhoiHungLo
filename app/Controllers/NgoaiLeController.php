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
}
