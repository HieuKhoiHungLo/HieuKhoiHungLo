<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ThiSinh;
use App\Models\AcademicRecord;
use App\Models\Application;
use App\Models\NguyenVong;

class HomeController extends Controller {
    public function index() {
        $postModel = new \App\Models\Post();
        $sessionModel = new \App\Models\AdmissionSession();
        $db = \App\Core\Database::getInstance()->getConnection();

        // 1. Get Latest Posts/Announcements
        // 1. Get Latest Posts/Announcements with Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $limit = 3;
        $offset = ($page - 1) * $limit;
        
        $posts = $postModel->getPaginated($limit, $offset);
        $totalPosts = $postModel->countPublished();
        $totalPages = ceil($totalPosts / $limit);

        // 2. Get Active Session for deadlines
        $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestActiveSession();

        // 3. User Specific Data (If logged in)
        $user = null;
        $currentApp = null;
        $stepStatus = [];
        
        if (isset($_SESSION['user_id'])) {
            $cccd = $_SESSION['cccd'];
            $thiSinhModel = new ThiSinh();
            
            // Re-use session cached user if available
            if (isset($_SESSION['user_cached']) && $_SESSION['user_cached_cccd'] === $cccd) {
                $user = $_SESSION['user_cached'];
            } else {
                $user = $thiSinhModel->findByCCCD($cccd);
                $_SESSION['user_cached'] = $user;
                $_SESSION['user_cached_cccd'] = $cccd;
            }

            // Consolidate other checks to avoid too many DB calls
            $academicModel = new AcademicRecord();
            $appModel = new Application();
            $nvModel = new NguyenVong();
            $thptModel = new \App\Models\DiemThiTHPT();

            $records = $academicModel->getByCCCD($cccd);
            $certs = $thiSinhModel->getCertifications($cccd);
            $applications = $appModel->getByCCCD($cccd);
            $choices = $nvModel->getByCCCD($cccd);
            $thptScores = $thptModel->getByCCCD($cccd);

            if ($activeSession) {
                foreach ($applications as $app) {
                    // Check if app is array or object to be safe, but typically array now
                    $appSessionId = is_array($app) ? $app['dot_tuyen_sinh_id'] : $app->dot_tuyen_sinh_id;
                    $sessionId = is_array($activeSession) ? $activeSession['id'] : $activeSession->id;
                    
                    if ($appSessionId == $sessionId) {
                        $currentApp = $app;
                        break;
                    }
                }
            }

            $step4Done = false;
            // Only consider Step 4 if Step 1 is done
            if (!empty($stepStatus[1]) && !empty($thptScores) && !empty($thptScores['nam_thi'])) {
                 $step4Done = true;
                 
                 // Heuristic: Check for "Phantom" record created at registration
                 // If da_co_diem is empty (0/null) AND it seems like an auto-created record
                 if (empty($thptScores['da_co_diem'])) {
                     $uCreated = strtotime($user['ngay_tao'] ?? 'now');
                     $tUpdated = strtotime($thptScores['ngay_cap_nhat'] ?? '');
                     
                     // If record hasn't been updated since creation (within 2 minutes of user creation)
                     if ($uCreated && $tUpdated && abs($tUpdated - $uCreated) <= 120) {
                         $step4Done = false;
                     }
                 }
            }

            $stepStatus = [
                1 => !empty($user['ho_va_ten']) && !empty($user['anh_dai_dien']),
                2 => !empty($records),
                3 => !empty($certs) || (isset($user['co_chung_chi_qt']) && $user['co_chung_chi_qt'] == 0),
                4 => $step4Done,
                5 => !empty($choices)
            ];
        }

        // 4. Get Major Info (Optimized with Session Cache)
        if (!isset($_SESSION['cache_majors'])) {
            try {
                $stmt = $db->query("SELECT ma_nganh, ten_nganh, chi_tieu, khoi_xet_tuyen, diem_nam_truoc FROM dm_nganh ORDER BY ma_nganh ASC");
                $_SESSION['cache_majors'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Exception $e) { $_SESSION['cache_majors'] = []; }
        }
        $majors = $_SESSION['cache_majors'];

        // 5. Get Admission Conditions & Homepage Settings (Optimized with Session Cache)
        if (!isset($_SESSION['cache_home_settings'])) {
            $conditions = '';
            $homeSettings = [
                'video_url' => 'czCebfco6_g',
                'stats_majors' => '27',
                'stats_quota' => '3070',
                'stats_employ' => '98%',
                'announcement' => ''
            ];

            try {
                $stmt = $db->prepare("SELECT key, value FROM cau_hinh WHERE key IN ('admission_conditions', 'home_video_url', 'home_stats_majors', 'home_stats_quota', 'home_stats_employment', 'home_announcement')");
                $stmt->execute();
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    if ($row['key'] === 'admission_conditions') $conditions = $row['value'];
                    if ($row['key'] === 'home_video_url') $homeSettings['video_url'] = $row['value'];
                    if ($row['key'] === 'home_stats_majors') $homeSettings['stats_majors'] = $row['value'];
                    if ($row['key'] === 'home_stats_quota') $homeSettings['stats_quota'] = $row['value'];
                    if ($row['key'] === 'home_stats_employment') $homeSettings['stats_employ'] = $row['value'];
                    if ($row['key'] === 'home_announcement') $homeSettings['announcement'] = $row['value'];
                }
            } catch (\Exception $e) {}
            
            $_SESSION['cache_home_settings'] = $homeSettings;
            $_SESSION['cache_admission_conditions'] = $conditions;
        }
        
        $homeSettings = $_SESSION['cache_home_settings'];
        $conditions = $_SESSION['cache_admission_conditions'];
        
        // Get Settings using Repository (or Model if simple)
        // Since we are refactoring, let's use Repo.
        $masterDataRepo = new \App\Repositories\MasterDataRepository();
        $enableTHPT = $masterDataRepo->getSetting('enable_thpt_step') == '1';

        $this->view('home', [
            'posts' => $posts,
            'majors' => $majors,
            'activeSession' => $activeSession,
            'conditions' => $conditions,
            'homeSettings' => $homeSettings,
            'user' => $user,
            'currentApp' => $currentApp,
            'stepStatus' => $stepStatus,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'enableTHPT' => $enableTHPT
        ]);
    }
}
