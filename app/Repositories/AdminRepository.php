<?php
namespace App\Repositories;

use App\Models\QuanTriVien;

class AdminRepository {
    protected $model;

    public function __construct() {
        $this->model = new QuanTriVien();
    }

    public function findByUsername(string $username) {
        return $this->model->timTheoTenDangNhap($username);
    }

    public function findById(int $id) {
        return $this->model->find($id);
    }

    // Add other methods as needed wrappers
}
