<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;

class FooterLinksController extends Controller {
    protected $masterData;

    public function __construct() {
        $this->masterData = new MasterData();
    }

    public function index() {
        $linksJson = $this->masterData->getSetting('footer_support_links');
        $links = $linksJson ? json_decode($linksJson, true) : [];
        
        // Default links if none set
        if (empty($links)) {
            $links = [
                ['label' => 'Đăng ký xét tuyển', 'url' => '/register', 'icon' => 'fas fa-check-circle'],
                ['label' => 'Tra cứu hồ sơ', 'url' => '/login', 'icon' => 'fas fa-check-circle'],
                ['label' => 'Câu hỏi thường gặp', 'url' => '#', 'icon' => 'fas fa-check-circle'],
                ['label' => 'Hướng dẫn đăng ký', 'url' => '#', 'icon' => 'fas fa-check-circle'],
            ];
        }

        $content = null;
        ob_start();
        include __DIR__ . '/../../resources/views/admin/footer-links/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../resources/views/layouts/admin.php';
    }

    public function save() {
        $labels = $_POST['label'] ?? [];
        $urls = $_POST['url'] ?? [];
        $icons = $_POST['icon'] ?? [];

        $links = [];
        for ($i = 0; $i < count($labels); $i++) {
            $label = trim($labels[$i] ?? '');
            $url = trim($urls[$i] ?? '');
            if ($label && $url) {
                $links[] = [
                    'label' => $label,
                    'url' => $url,
                    'icon' => trim($icons[$i] ?? 'fas fa-check-circle')
                ];
            }
        }

        $this->masterData->setSetting('footer_support_links', json_encode($links, JSON_UNESCAPED_UNICODE));

        header('Location: ' . url('/admin/footer-links?msg=saved'));
        exit;
    }
}
