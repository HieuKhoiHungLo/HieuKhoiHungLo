<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CertificateRule;
use App\Models\MasterData;

class CertificateRuleController extends Controller {
    protected $model;
    protected $masterData;

    public function __construct() {
        $this->model = new CertificateRule();
        $this->masterData = new MasterData();
        
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
    }

    public function index() {
        $rules = $this->model->all();
        $subjects = $this->masterData->getSubjects(); 
        $this->view('admin/certificate_rules/index', [
            'title' => 'Cấu hình Quy đổi Chứng chỉ',
            'rules' => $rules,
            'subjects' => $subjects
        ]);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'loai_chung_chi' => $_POST['loai_chung_chi'] ?? 'IELTS',
                'muc_diem_tu' => (float)($_POST['muc_diem_tu'] ?? 0),
                'muc_diem_den' => !empty($_POST['muc_diem_den']) ? (float)$_POST['muc_diem_den'] : null,
                'diem_quy_doi' => (float)($_POST['diem_quy_doi'] ?? 0),
                'mon_id' => (int)($_POST['mon_id'] ?? 1)
            ];
            
            if ($this->model->create($data)) {
                $_SESSION['flash_success'] = "Thêm quy tắc thành công.";
            } else {
                $_SESSION['flash_error'] = "Không thể thêm quy tắc.";
            }
        }
        $this->redirect(url('/admin/certificate-rules'));
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if (!$id) {
                 $this->redirect(url('/admin/certificate-rules'));
            }

            $data = [
                'loai_chung_chi' => $_POST['loai_chung_chi'] ?? 'IELTS',
                'muc_diem_tu' => (float)($_POST['muc_diem_tu'] ?? 0),
                'muc_diem_den' => !empty($_POST['muc_diem_den']) ? (float)$_POST['muc_diem_den'] : null,
                'diem_quy_doi' => (float)($_POST['diem_quy_doi'] ?? 0),
                'mon_id' => (int)($_POST['mon_id'] ?? 1)
            ];
            
            if ($this->model->update($id, $data)) {
                $_SESSION['flash_success'] = "Cập nhật thành công.";
            } else {
                $_SESSION['flash_error'] = "Không thể cập nhật.";
            }
        }
        $this->redirect(url('/admin/certificate-rules'));
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if ($id && $this->model->delete($id)) {
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
    }
}
