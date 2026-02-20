<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\RuleEngine;
use App\Services\AuditService;
use App\Services\PermissionService;
use App\Models\MasterData;

class RuleController extends Controller {
    protected $ruleEngine;
    protected $auditService;
    protected $masterData;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->ruleEngine = new RuleEngine();
        $this->auditService = new AuditService();
        $this->masterData = new MasterData();
    }

    public function index() {
        $rules = $this->ruleEngine->getAllRules();
        $majors = $this->masterData->getMajors();

        $this->view('admin/rules/index', [
            'rules' => $rules,
            'majors' => $majors
        ]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id' => $_POST['id'] ?? null,
                'name' => $_POST['name'],
                'ma_nganh' => $_POST['ma_nganh'] ?: null,
                'rule_type' => $_POST['rule_type'],
                'condition' => $_POST['condition'],
                'message' => $_POST['message'],
                'is_active' => isset($_POST['is_active'])
            ];

            $this->ruleEngine->saveRule($data);
            $this->auditService->log($_POST['id'] ? 'UPDATE' : 'CREATE', 'admission_rule', $_POST['id'] ?? 'new', null, $data);

            $this->redirect(url('/admin/rules?msg=saved'));
        }
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $this->ruleEngine->deleteRule($id);
            $this->auditService->log('DELETE', 'admission_rule', $id);
        }
        $this->redirect(url('/admin/rules'));
    }
}
