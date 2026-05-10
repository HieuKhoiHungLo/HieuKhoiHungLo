<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

class AdminPostController extends Controller
{
    protected $postModel;

    public function __construct()
    {
        $this->requireAdmin();
        $this->postModel = new Post();
        
        if (!\can('posts.view')) {
            die('Bạn không có quyền truy cập chức năng này.');
        }
    }

    public function index()
    {
        $search = trim($_GET['search'] ?? '');
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $total = $this->postModel->countAllAdmin($search);
        $totalPages = ceil($total / $limit);
        
        $posts = $this->postModel->getAllAdminPaginated($limit, $offset, $search);

        $this->view('admin/posts/index', [
            'posts' => $posts,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total
        ]);
    }

    public function create()
    {
        $categoryModel = new \App\Models\PostCategory();
        $categories = $categoryModel->getAllActive();
        $this->view('admin/posts/form', ['post' => null, 'categories' => $categories]);
    }

    public function edit()
    {
        $id = $_GET['id'] ?? '';
        $post = $this->postModel->find($id);

        if (!$post) {
            $this->redirect(url('/admin/posts'));
        }

        $categoryModel = new \App\Models\PostCategory();
        $categories = $categoryModel->getAllActive();
        $this->view('admin/posts/form', ['post' => $post, 'categories' => $categories]);
    }

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // CSRF Check
                if (!$this->verifyCsrf($_POST['csrf_token'] ?? '')) {
                    die('CSRF token validation failed');
                }

                if (!\can('posts.edit')) {
                    die('Bạn không có quyền thực hiện chức năng này.');
                }

                // Handle Thumbnail Upload
                $thumbnailPath = $_POST['thumbnail'] ?? '';

                if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    $filename = $_FILES['thumbnail_file']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed) && $_FILES['thumbnail_file']['size'] <= 5 * 1024 * 1024) { // 5MB
                        $rootDir = dirname(dirname(__DIR__));
                        $uploadRelative = 'uploads/posts/';
                        $uploadDir = $rootDir . '/public/' . $uploadRelative;
                        
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                        $newFilename = 'post_' . time() . '_' . uniqid() . '.' . $ext;
                        $destPath = $uploadDir . $newFilename;

                        if (move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $destPath)) {
                            $thumbnailPath = $uploadRelative . $newFilename;
                        }
                    }
                }

                $createdAt = trim($_POST['created_at'] ?? '');
                if (!$createdAt) {
                    $createdAt = date('Y-m-d H:i:s');
                } else {
                    $createdAt = date('Y-m-d H:i:s', strtotime($createdAt));
                }

                $id = $_POST['id'] ?? '';
                $title = $_POST['title'] ?? '';
                $slug = ($_POST['slug'] ?? '') ?: $this->slugify($title);
                $data = [
                    'title' => $title,
                    'slug' => $slug,
                    'summary' => $_POST['summary'] ?? '',
                    'content' => $_POST['content'] ?? '',
                    'category' => $_POST['category'] ?? 'Tin tức',
                    'status' => $_POST['status'] ?? 'Draft',
                    'is_featured' => isset($_POST['is_featured']), // PostgreSQL requires strict boolean
                    'thumbnail' => $thumbnailPath,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => $createdAt
                ];

                if ($id) {
                    $this->postModel->update($id, $data);
                } else {
                    $this->postModel->create($data);
                }

                $this->redirect(url('/admin/posts'));
            } catch (\Throwable $e) {
                $logDir = __DIR__ . '/../../storage/logs';
                if (!is_dir($logDir)) mkdir($logDir, 0777, true);
                $logMsg = '[' . date('Y-m-d H:i:s') . '] POST SAVE ERROR: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n---\n";
                file_put_contents($logDir . '/post_save_error.log', $logMsg, FILE_APPEND);
                die('Error: ' . $e->getMessage());
            }
        }
    }

    public function delete()
    {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $this->postModel->delete($id);
        }
        $this->redirect(url('/admin/posts'));
    }

    private function slugify($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) return 'n-a';
        return $text;
    }
}
