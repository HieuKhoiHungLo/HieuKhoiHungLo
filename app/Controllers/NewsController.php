<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

class NewsController extends Controller {

    public function index() {
        $category = $_GET['category'] ?? null;
        $postModel = new Post();
        
        if ($category) {
            $posts = $postModel->getLatest(20, $category);
        } else {
            $posts = $postModel->getLatest(20);
        }

        $categoryModel = new \App\Models\PostCategory();
        $categories = $categoryModel->getAllActive();

        $this->view('news/index', [
            'posts' => $posts,
            'category' => $category,
            'categories' => $categories,
            'recentPosts' => $postModel->getLatest(5)
        ]);
    }

    public function detail() {
        $slug = $_GET['slug'] ?? '';
        if (empty($slug)) {
            $this->redirect(url('/'));
        }

        $postModel = new Post();
        $post = $postModel->findBySlug($slug);

        if (!$post) {
            $this->redirect(url('/'));
        }

        // Increment view count
        $postModel->incrementView($post['id']);

        $categoryModel = new \App\Models\PostCategory();
        $categories = $categoryModel->getAllActive();

        $this->view('news/detail', [
            'post' => $post,
            'categories' => $categories,
            'recentPosts' => $postModel->getLatest(5)
        ]);
    }
}
