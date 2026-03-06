<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

class AdminPostController extends Controller
{
    protected $postModel;

    public function __construct()
    {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->postModel = new Post();
    }

    public function index()
    {
        $posts = $this->postModel->getAllAdmin();
        $this->view('admin/posts/index', ['posts' => $posts]);
    }

    public function create()
    {
        $this->view('admin/posts/form', ['post' => null]);
    }

    public function edit()
    {
        $id = $_GET['id'] ?? '';
        $post = $this->postModel->find($id);

        if (!$post) {
            $this->redirect(url('/admin/posts'));
        }

        $this->view('admin/posts/form', ['post' => $post]);
    }

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // CSRF Check
                if (!$this->verifyCsrf($_POST['csrf_token'] ?? '')) {
                    die('CSRF token validation failed');
                }

                // Handle Thumbnail Upload
                $thumbnailPath = $_POST['thumbnail'] ?? '';

                if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    $filename = $_FILES['thumbnail_file']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed) && $_FILES['thumbnail_file']['size'] <= 5 * 1024 * 1024) { // 5MB
                        $publicPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
                        // Nếu có base path (VD: /TS), thêm vào document root
                        $basePath = parse_url(url('/'), PHP_URL_PATH);
                        $basePath = rtrim($basePath, '/');
                        $uploadRelative = 'uploads/posts/';
                        $uploadDir = $publicPath . $basePath . '/' . $uploadRelative;

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
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'thumbnail' => $thumbnailPath,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($id) {
                    $this->postModel->update($id, $data);
                } else {
                    $data['created_at'] = date('Y-m-d H:i:s');
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
