<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;

class ScoringSettingsController extends Controller {
    protected $masterData;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->masterData = new MasterData();
    }

    public function index() {
        $keys = [
            'score_priority_kv1', 'score_priority_kv2_nt', 'score_priority_kv2', 'score_priority_kv3',
            'score_priority_ut1', 'score_priority_ut2',
            'score_threshold_dampening', 'score_dampening_divisor'
        ];
        
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = $this->masterData->getSetting($key);
        }

        $this->view('admin/settings/scoring', ['settings' => $settings]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            foreach ($data as $key => $val) {
                if (strpos($key, 'score_') === 0) {
                     $this->masterData->setSetting($key, $val);
                }
            }
            $this->redirect(url('/admin/settings/scoring?msg=saved'));
        }
    }
}
