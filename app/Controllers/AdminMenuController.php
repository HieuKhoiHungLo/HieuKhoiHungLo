<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Menu;

class AdminMenuController extends Controller {
    protected $menuModel;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $permissionService = new \App\Services\PermissionService();
        if (!$permissionService->can('settings.edit')) {
            die('Bạn không có quyền truy cập chức năng này.');
        }
        $this->menuModel = new Menu();
    }

    public function index() {
        $position = $_GET['position'] ?? 'header_public';
        $menus = $this->menuModel->getAllMenus($position);
        
        $this->view('admin/menus/index', [
            'menus' => $menus, 
            'position' => $position
        ]);
    }

    public function create() {
        $position = $_GET['position'] ?? 'header_public';
        $parents = $this->menuModel->getAllMenus($position);
        
        $this->view('admin/menus/form', [
            'position' => $position,
            'parents' => $parents,
            'permissions' => $this->getAvailablePermissions()
        ]);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $data = [
                'title' => $_POST['title'] ?? '',
                'url' => $_POST['url'] ?? '',
                'position' => $_POST['position'] ?? 'header_public',
                'parent_id' => !empty($_POST['parent_id']) ? $_POST['parent_id'] : null,
                'visibility' => $_POST['visibility'] ?? 'public',
                'permission_required' => !empty($_POST['permission_required']) ? $_POST['permission_required'] : null,
                'icon' => $_POST['icon'] ?? null,
                'css_class' => $_POST['css_class'] ?? null,
                'order_index' => (int)($_POST['order_index'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $this->menuModel->create($data);
            $this->redirect(url("/admin/menus?position={$data['position']}&msg=saved"));
        }
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) $this->redirect(url('/admin/menus'));
        
        $menu = $this->menuModel->find($id);
        if (!$menu) $this->redirect(url('/admin/menus'));
        
        $parents = $this->menuModel->getAllMenus($menu['position']);
        
        $this->view('admin/menus/form', [
            'menu' => $menu,
            'position' => $menu['position'],
            'parents' => $parents,
            'permissions' => $this->getAvailablePermissions()
        ]);
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $id = $_POST['id'];
            $data = [
                'title' => $_POST['title'] ?? '',
                'url' => $_POST['url'] ?? '',
                'position' => $_POST['position'] ?? 'header_public',
                'parent_id' => !empty($_POST['parent_id']) ? $_POST['parent_id'] : null,
                'visibility' => $_POST['visibility'] ?? 'public',
                'permission_required' => !empty($_POST['permission_required']) ? $_POST['permission_required'] : null,
                'icon' => $_POST['icon'] ?? null,
                'css_class' => $_POST['css_class'] ?? null,
                'order_index' => (int)($_POST['order_index'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $this->menuModel->update($id, $data);
            $this->redirect(url("/admin/menus?position={$data['position']}&msg=saved"));
        }
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $menu = $this->menuModel->find($id);
            if ($menu) {
                // Xoá luôn menu con nếu có
                $db = \App\Core\Database::getInstance()->getConnection();
                $db->prepare("DELETE FROM menus WHERE parent_id = ?")->execute([$id]);
                
                $this->menuModel->delete($id);
                $this->redirect(url("/admin/menus?position={$menu['position']}&msg=deleted"));
                return;
            }
        }
        $this->redirect(url('/admin/menus'));
    }

    protected function getAvailablePermissions() {
        return [
            'dashboard' => 'Xem Dashboard chung',
            'candidate.view' => 'Xem danh sách Hồ sơ',
            'candidate.edit' => 'Sửa Hồ sơ',
            'candidate.delete' => 'Xóa Hồ sơ',
            'report.export' => 'Xuất báo cáo',
            'settings.edit' => 'Chỉnh sửa cài đặt/quy tắc',
            'major.view' => 'Xem danh mục',
            'role.view' => 'Quản lý tài khoản & phân quyền',
            'posts.view' => 'Quản lý tin tức',
            'audit.view' => 'Xem lịch sử hệ thống'
        ];
    }
}
