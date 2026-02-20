<?php
namespace App\Repositories;

use App\Models\DiemThiTHPT;

class ThptRepository {
    protected $model;

    public function __construct() {
        $this->model = new DiemThiTHPT();
    }

    public function getByCCCD($cccd) {
        return $this->model->getByCCCD($cccd);
    }

    public function save($data) {
        return $this->model->save($data);
    }
}
