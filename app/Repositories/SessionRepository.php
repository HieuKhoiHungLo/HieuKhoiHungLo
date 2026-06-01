<?php
namespace App\Repositories;

use App\Models\AdmissionSession;

class SessionRepository {
    protected $model;

    public function __construct() {
        $this->model = new AdmissionSession();
    }

    public function getActiveSession() {
        return $this->model->getActiveSession();
    }

    public function getLatestActiveSession() {
        return $this->model->getLatestActiveSession();
    }

    public function getLatestSession() {
        return $this->model->getLatestSession();
    }

    public function find($id) {
        return $this->model->find($id);
    }

    public function getAll() {
        return $this->model->getAll();
    }
}
