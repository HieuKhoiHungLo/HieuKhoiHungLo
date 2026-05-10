<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ThiSinh;
use App\Models\AcademicRecord;
use App\Models\Application;
use App\Models\NguyenVong;

class HomeController extends Controller {
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * 10;
        // Redirect logged-in admins to the admin dashboard
        /*
        if (isset($_SESSION['admin_id'])) {
            header('Location: ' . url('/admin/dashboard'));
            exit;
        }

        // Redirect logged-in students to the full application dashboard
        if (isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
            header('Location: ' . url('/application/index'));
            exit;
        }
        */

        $postModel = new \App\Models\Post();
        $sessionModel = new \App\Models\AdmissionSession();
        $db = \App\Core\Database::getInstance()->getConnection();

        // 1. Get Latest Posts by Category for Slideshow
        $categoryModel = new \App\Models\PostCategory();
        $categories = array_slice($categoryModel->getAllActive(), 0, 3);
        
        $newsByCategory = [];
        foreach ($categories as $cat) {
            $newsByCategory[$cat['name']] = $postModel->getLatest(5, $cat['name']);
        }
        
        // For fallback/other sections if needed
        $posts = $postModel->getPaginated(10, $offset);
        $totalPosts = $postModel->countPublished();
        $totalPages = ceil($totalPosts / 10);

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

            $step1Done = !empty($user['ho_va_ten']) && !empty($user['anh_dai_dien']);
            
            $step4Done = false;
            // Only consider Step 4 if Step 1 is done
            if ($step1Done && !empty($thptScores) && !empty($thptScores['nam_thi'])) {
                 $step4Done = true;
            }

            $stepStatus = [
                1 => $step1Done,
                2 => !empty($records),
                3 => !empty($certs) || (isset($user['co_chung_chi_qt']) && $user['co_chung_chi_qt'] == 0),
                4 => $step4Done,
                5 => !empty($choices)
            ];
        }

        // 4. Get Major Info - Chỉ lấy ngành đang kích hoạt
        $masterData = new \App\Models\MasterData();
        $majors = $masterData->getActiveMajorsWithCombinations();

        // 5. Get Admission Conditions & Homepage Settings (Optimized with Session Cache)
        if (!isset($_SESSION['cache_home_settings'])) {
            $conditions = '';
            $homeSettings = [
                'video_url' => 'czCebfco6_g',
                'stats_majors' => '27',
                'stats_quota' => '3070',
                'stats_employ' => '98%',
                'announcement' => '',
                'countdown_enabled' => '0',
                'countdown_title' => '',
                'countdown_deadline' => ''
            ];

            try {
                $stmt = $db->prepare("SELECT key, value FROM cau_hinh WHERE key IN ('admission_conditions', 'home_video_url', 'home_stats_majors', 'home_stats_quota', 'home_stats_employment', 'home_announcement', 'countdown_enabled', 'countdown_title', 'countdown_deadline')");
                $stmt->execute();
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    if ($row['key'] === 'admission_conditions') $conditions = $row['value'];
                    if ($row['key'] === 'home_video_url') $homeSettings['video_url'] = $row['value'];
                    if ($row['key'] === 'home_stats_majors') $homeSettings['stats_majors'] = $row['value'];
                    if ($row['key'] === 'home_stats_quota') $homeSettings['stats_quota'] = $row['value'];
                    if ($row['key'] === 'home_stats_employment') $homeSettings['stats_employ'] = $row['value'];
                    if ($row['key'] === 'home_announcement') $homeSettings['announcement'] = $row['value'];
                    if ($row['key'] === 'countdown_enabled') $homeSettings['countdown_enabled'] = $row['value'];
                    if ($row['key'] === 'countdown_title') $homeSettings['countdown_title'] = $row['value'];
                    if ($row['key'] === 'countdown_deadline') $homeSettings['countdown_deadline'] = $row['value'];
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
            'categories' => $categories,
            'newsByCategory' => $newsByCategory,
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
