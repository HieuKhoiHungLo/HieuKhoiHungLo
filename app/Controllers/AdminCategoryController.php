<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\PostCategory;
use App\Services\AuditService;

class AdminCategoryController extends Controller {
    protected $categoryModel;
    protected $auditService;

    public function __construct() {
        $this->requireAdmin();
        $this->categoryModel = new PostCategory();
        $this->auditService = new AuditService();
        
        if (!\can('posts.category')) {
            die('Bạn không có quyền truy cập chức năng này.');
        }
    }

    public function index() {
        $id = $_GET['id'] ?? '';
        $editCategory = null;
        if ($id) {
            $editCategory = $this->categoryModel->find($id);
        }
        $categories = $this->categoryModel->getAll();
        $this->view('admin/categories/index', [
            'categories' => $categories,
            'editCategory' => $editCategory
        ]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCsrf();
                $id = $_POST['id'] ?? '';
                $data = [
                    'name' => $_POST['name'] ?? '',
                    'slug' => $_POST['slug'] ?? $this->slugify($_POST['name'] ?? ''),
                    'is_active' => isset($_POST['is_active']),
                    'sort_order' => (int)($_POST['sort_order'] ?? 0)
                ];

                if ($id) {
                    $this->categoryModel->update($id, $data);
                    $this->auditService->log('UPDATE_CATEGORY', 'post_categories', $id, null, $data);
                } else {
                    $this->categoryModel->create($data);
                    $this->auditService->log('CREATE_CATEGORY', 'post_categories', null, null, $data);
                }
                $this->redirect('/admin/categories');
            } catch (\Exception $e) {
                die('Lỗi hệ thống: ' . $e->getMessage());
            }
        }
    }

    public function delete() {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $this->categoryModel->delete($id);
            $this->auditService->log('DELETE_CATEGORY', 'post_categories', $id);
        }
        $this->redirect('/admin/categories');
    }

    private function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }
}
