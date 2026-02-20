<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;

class ApiController extends Controller {

    protected $masterData;

    public function __construct() {
        $this->masterData = new MasterData();
    }

    public function getWards() {
        $provinceId = $_GET['province_id'] ?? '';
        if (!$provinceId) {
            echo json_encode([]);
            return;
        }
        $wards = $this->masterData->getWards($provinceId);
        header('Content-Type: application/json');
        echo json_encode($wards);
    }

    public function getSchools() {
        $provinceId = $_GET['province_id'] ?? '';
        if (!$provinceId) {
            echo json_encode([]);
            return;
        }
        $schools = $this->masterData->getSchools($provinceId);
        header('Content-Type: application/json');
        echo json_encode($schools);
    }

    public function getSchoolDetails() {
        $schoolId = $_GET['school_id'] ?? '';
        if (!$schoolId) {
            echo json_encode(null);
            return;
        }
        $school = $this->masterData->findSchool($schoolId);
        header('Content-Type: application/json');
        echo json_encode($school);
    }
}
