<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\QuanTriVien;
use App\Models\ScoreConversion;

class SettingController extends Controller
{
    protected $masterData;
    protected $currentUser;

    public function __construct()
    {
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

    public function index()
    {
        $settingList = $this->masterData->getAll('settings', 'key');
        $this->view('admin/master_data/settings', ['settings' => $settingList, 'user' => $this->currentUser]);
    }

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            foreach ($_POST['settings'] as $key => $value) {
                $this->masterData->setSetting($key, $value);
            }

            // Sync certain settings to cau_hinh table for frontend logic compatibility
            if (isset($_POST['settings']['home_announcement'])) {
                $masterDataRepo = new \App\Repositories\MasterDataRepository();
                $currentHomeSettings = $masterDataRepo->getHomeSettings();
                $masterDataRepo->updateHomeSettings(
                    $currentHomeSettings['video_url'],
                    $currentHomeSettings['stats_majors'],
                    $currentHomeSettings['stats_quota'],
                    $currentHomeSettings['stats_employment'] ?? $currentHomeSettings['stats_employ'],
                    $_POST['settings']['home_announcement']
                );

                // Clear homepage session cache
                unset($_SESSION['cache_home_settings']);
            }

            $this->redirect(url('/admin/master-data/settings?updated=1'));
        }
    }

    public function languageRules()
    {
        $conversionModel = new ScoreConversion();
        $rules = $conversionModel->getAllRules();
        $this->view('admin/master_data/language_rules', ['rules' => $rules, 'user' => $this->currentUser]);
    }

    public function saveLanguageRule()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $conversionModel = new ScoreConversion();

            $conversionModel->saveRule($_POST);
            $this->redirect(url('/admin/master-data/language-rules'));
        }
    }

    public function deleteLanguageRule()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $id = $_POST['id'] ?? '';
            if ($id) {
                $conversionModel = new ScoreConversion();
                $conversionModel->deleteRule($id);
            }
            $this->redirect(url('/admin/master-data/language-rules'));
        } else {
            $this->redirect(url('/admin/master-data/language-rules'));
        }
    }
}
