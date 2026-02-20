<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

class NewsController extends Controller {

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

        $this->view('news/detail', [
            'post' => $post,
            'recentPosts' => $postModel->getLatest(5)
        ]);
    }
}
