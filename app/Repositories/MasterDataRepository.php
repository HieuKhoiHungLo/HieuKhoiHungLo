<?php

namespace App\Repositories;

use App\Models\MasterData;
use App\Core\Database;
use App\Core\Cache;
use PDO;

class MasterDataRepository
{
    protected $db;
    protected $model;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->model = new MasterData();
    }

    public function getProvinces()
    {
        return Cache::remember('master_provinces', 60 * 24, function () {
            return $this->model->getProvinces();
        });
    }

    public function getSubjects($type = null)
    {
        $key = 'master_subjects_' . ($type ?? 'all');
        return Cache::remember($key, 60 * 24, function () use ($type) {
            return $this->model->getSubjects($type);
        });
    }

    public function getMajors()
    {
        return Cache::remember('master_majors', 60 * 24, function () {
            return $this->model->getMajors();
        });
    }

    public function getMajorsWithCombinations()
    {
        return Cache::remember('majors_with_combinations_v2', 60, function () {
            $majors = [];
            try {
                // Need DB connection inside closure or via $this
                $db = Database::getInstance()->getConnection();

                $stmt = $db->query("SELECT * FROM dm_nganh ORDER BY ma_nganh ASC");
                $rawMajors = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $comboMap = [];
                try {
                    $stmtCombos = $db->query("
                        SELECT nth.ma_nganh, th.ma_to_hop 
                        FROM dm_nganh_to_hop nth 
                        JOIN dm_to_hop th ON nth.to_hop_id = th.id
                    ");
                    while ($row = $stmtCombos->fetch(PDO::FETCH_ASSOC)) {
                        $comboMap[$row['ma_nganh']][] = $row['ma_to_hop'];
                    }
                } catch (\Exception $e) {
                }

                foreach ($rawMajors as $m) {
                    $combos = $comboMap[$m['ma_nganh']] ?? [];
                    $m['to_hop_xet_tuyen'] = !empty($combos) ? implode(', ', $combos) : ($m['khoi_xet_tuyen'] ?? '');
                    $majors[] = $m;
                }
            } catch (\Exception $e) {
                return [];
            }
            return $majors;
        });
    }

    public function getActiveMajorsWithCombinations()
    {
        return Cache::remember('active_majors_with_combinations_v2', 60, function () {
            $majors = [];
            try {
                $db = Database::getInstance()->getConnection();

                $stmt = $db->query("SELECT * FROM dm_nganh WHERE COALESCE(kich_hoat, true) = true ORDER BY ma_nganh ASC");
                $rawMajors = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $comboMap = [];
                try {
                    $stmtCombos = $db->query("
                        SELECT nth.ma_nganh, th.ma_to_hop 
                        FROM dm_nganh_to_hop nth 
                        JOIN dm_to_hop th ON nth.to_hop_id = th.id
                    ");
                    while ($row = $stmtCombos->fetch(PDO::FETCH_ASSOC)) {
                        $comboMap[$row['ma_nganh']][] = $row['ma_to_hop'];
                    }
                } catch (\Exception $e) {
                }

                foreach ($rawMajors as $m) {
                    $combos = $comboMap[$m['ma_nganh']] ?? [];
                    $m['to_hop_xet_tuyen'] = !empty($combos) ? implode(', ', $combos) : ($m['khoi_xet_tuyen'] ?? '');
                    $majors[] = $m;
                }
            } catch (\Exception $e) {
                return [];
            }
            return $majors;
        });
    }

    public function getMajorCombinations($majorCode)
    {
        return Cache::remember('major_combinations_' . $majorCode, 60, function () use ($majorCode) {
            $Combinations = [];
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("
                    SELECT ma_to_hop 
                    FROM dm_nganh_to_hop 
                    WHERE ma_nganh = ?
                ");
                $stmt->execute([$majorCode]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    public function getPriorityAreas()
    {
        return Cache::remember('priority_areas', 600, function () {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT ma_kv, diem_uu_tien FROM dm_khu_vuc");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $map = [];
            foreach ($rows as $r) {
                $map[trim($r['ma_kv'])] = (float)$r['diem_uu_tien'];
            }
            return $map;
        });
    }

    public function getPriorityObjects()
    {
        return Cache::remember('priority_objects', 600, function () {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT ma_dt, diem_uu_tien FROM dm_doi_tuong");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $map = [];
            foreach ($rows as $r) {
                $map[trim($r['ma_dt'])] = (float)$r['diem_uu_tien'];
            }
            return $map;
        });
    }

    public function getSetting($key)
    {
        return $this->model->getSetting($key);
    }

    public function getEmailTemplates()
    {
        return $this->model->getEmailTemplates();
    }

    public function isPedagogyProvinceAllowed($majorCode, $provinceCode)
    {
        // 1. Check Specific Major Config First
        $major = $this->model->find('dm_nganh', $majorCode, 'ma_nganh');
        if ($major && !empty($major['khu_vuc_tuyen_sinh'])) {
            $allowedProvinces = explode(',', $major['khu_vuc_tuyen_sinh']);
            // Trim whitespace just in case
            $allowedProvinces = array_map('trim', $allowedProvinces);
            return in_array((string)$provinceCode, $allowedProvinces);
        }

        // 2. Fallback to Group Rule (Prefix 7140)
        if (strpos($majorCode, '7140') !== 0) {
            return true;
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM config_vung_tuyen_sinh WHERE ma_nganh_prefix = '7140' AND ma_tinh = ?");
        $stmt->execute([$provinceCode]);
        return $stmt->fetchColumn() > 0;
    }

    public function getHomeSettings()
    {
        $settings = [
            'video_url' => 'czCebfco6_g',
            'stats_majors' => '27',
            'stats_quota' => '3070',
            'stats_employ' => '98%',
            'announcement' => ''
        ];

        try {
            $stmt = $this->db->query("SELECT key, value FROM cau_hinh WHERE key IN ('home_video_url', 'home_stats_majors', 'home_stats_quota', 'home_stats_employment', 'home_announcement')");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['key'] === 'home_video_url') $settings['video_url'] = $row['value'];
                if ($row['key'] === 'home_stats_majors') $settings['stats_majors'] = $row['value'];
                if ($row['key'] === 'home_stats_quota') $settings['stats_quota'] = $row['value'];
                if ($row['key'] === 'home_stats_employment') $settings['stats_employ'] = $row['value'];
                if ($row['key'] === 'home_announcement') $settings['announcement'] = $row['value'];
            }
        } catch (\Exception $e) {
        }
        return $settings;
    }

    public function updateHomeSettings($videoId, $statsMajors, $statsQuota, $statsEmploy, $announcement)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO cau_hinh (key, value) VALUES (?,?) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value");
            $stmt->execute(['home_video_url', $videoId]);
            $stmt->execute(['home_stats_majors', $statsMajors]);
            $stmt->execute(['home_stats_quota', $statsQuota]);
            $stmt->execute(['home_stats_employment', $statsEmploy]);
            $stmt->execute(['home_announcement', $announcement]);

            // Sync back to settings table
            $this->model->setSetting('home_announcement', $announcement);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getZoneConfigs()
    {
        return $this->model->getZoneConfigs();
    }

    public function getWard($id)
    {
        if (!$id) return null;
        return Cache::remember('ward_' . $id, 60 * 24, function () use ($id) {
            return $this->model->find('dm_xa', $id, 'ma_xa');
        });
    }

    public function getSchool($id)
    {
        if (!$id) return null;
        return Cache::remember('school_' . $id, 60 * 24, function () use ($id) {
            return $this->model->findSchool($id);
        });
    }
}
