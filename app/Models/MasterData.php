<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class MasterData extends Model {
    
    public function getAll($table, $orderBy = 'id') {
        $stmt = $this->db->prepare("SELECT * FROM {$table} ORDER BY {$orderBy}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($table, $id, $idField = 'id') {
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE {$idField} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array_values($data));
    }

    public function update($table, $id, $data, $idField = 'id') {
        $fields = array_keys($data);
        $setClause = implode(', ', array_map(fn($f) => "{$f} = ?", $fields));
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$idField} = ?";
        $params = array_values($data);
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($table, $id, $idField = 'id') {
        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE {$idField} = ?");
        return $stmt->execute([$id]);
    }

    public function deleteMany($table, $ids, $idField = 'id') {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE {$idField} IN ($placeholders)");
        return $stmt->execute($ids);
    }

    public function getProvinces() {
        return \App\Core\Cache::remember('master_provinces', 1440, function() {
            $stmt = $this->db->prepare("SELECT * FROM dm_tinh ORDER BY CASE WHEN ten_tinh LIKE '%Phú Thọ%' THEN 0 ELSE 1 END, ten_tinh");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }

    public function isSubjectInUse($subjectId) {
        $stmt = $this->db->prepare("SELECT count(*) FROM dm_to_hop WHERE mon_1_id = ? OR mon_2_id = ? OR mon_3_id = ?");
        $stmt->execute([$subjectId, $subjectId, $subjectId]);
        return $stmt->fetchColumn() > 0;
    }

    public function getMajors() {
        return $this->getAll('dm_nganh', 'ten_nganh');
    }

    public function getWards($provinceId) {
        return \App\Core\Cache::remember("master_wards_{$provinceId}", 1440, function() use ($provinceId) {
            $stmt = $this->db->prepare("SELECT * FROM dm_xa WHERE ma_tinh = ? ORDER BY ten_xa");
            $stmt->execute([$provinceId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }

    public function getSchools($provinceId) {
        return \App\Core\Cache::remember("master_schools_{$provinceId}", 1440, function() use ($provinceId) {
            $stmt = $this->db->prepare("SELECT * FROM dm_truong_thpt WHERE ma_tinh = ? ORDER BY ten_truong");
            $stmt->execute([$provinceId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }

    public function findSchool($schoolId) {
        $stmt = $this->db->prepare("SELECT * FROM dm_truong_thpt WHERE ma_truong = ?");
        $stmt->execute([$schoolId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPriorityAreas() {
        return $this->getAll('dm_khu_vuc', 'ma_kv');
    }

    public function getPriorityObjects() {
        return $this->getAll('dm_doi_tuong', 'ma_dt');
    }

    public function getSetting($key) {
        $stmt = $this->db->prepare("SELECT value FROM settings WHERE \"key\" = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    }

    public function setSetting($key, $value) {
        $existing = $this->getSetting($key);
        if ($existing !== false) {
            $stmt = $this->db->prepare("UPDATE settings SET value = ? WHERE \"key\" = ?");
            return $stmt->execute([$value, $key]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO settings (\"key\", value) VALUES (?, ?)");
            return $stmt->execute([$key, $value]);
        }
    }

    public function getSubjects($type = null) {
        $cacheKey = "master_subjects_" . ($type ?? 'all');
        return \App\Core\Cache::remember($cacheKey, 1440, function() use ($type) {
            $sql = "SELECT * FROM dm_mon";
            $params = [];
            if ($type) {
                $sql .= " WHERE loai_mon = ?";
                $params[] = $type;
            }
            $sql .= " ORDER BY loai_mon ASC, ma_mon ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }

    public function getCombinations() {
        $sql = "SELECT c.*, 
                m1.ten_mon as mon1_ten, m1.ma_mon as mon1_ma,
                m2.ten_mon as mon2_ten, m2.ma_mon as mon2_ma,
                m3.ten_mon as mon3_ten, m3.ma_mon as mon3_ma
                FROM dm_to_hop c
                LEFT JOIN dm_mon m1 ON c.mon_1_id = m1.id
                LEFT JOIN dm_mon m2 ON c.mon_2_id = m2.id
                LEFT JOIN dm_mon m3 ON c.mon_3_id = m3.id
                ORDER BY c.ma_to_hop";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveMajorCombinations($ma_nganh, $combinations) {
        // Clear existing
        $this->db->prepare("DELETE FROM dm_nganh_to_hop WHERE ma_nganh = ?")->execute([$ma_nganh]);
        
        // Insert new
        $stmt = $this->db->prepare("INSERT INTO dm_nganh_to_hop (ma_nganh, ma_to_hop) VALUES (?, ?)");
        foreach ($combinations as $ma_to_hop) {
            $stmt->execute([$ma_nganh, $ma_to_hop]);
        }
    }

    public function getMajorCombinations($ma_nganh) {
        $stmt = $this->db->prepare("SELECT ma_to_hop FROM dm_nganh_to_hop WHERE ma_nganh = ?");
        $stmt->execute([$ma_nganh]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Optimization: Fetch Majors with Combinations in one query
    public function getMajorsWithCombinations() {
        return \App\Core\Cache::remember('master_majors_combinations', 1440, function() {
            // Postgres uses string_agg
            $sql = "SELECT n.*, string_agg(nth.ma_to_hop, ', ') as combination_list 
                    FROM dm_nganh n 
                    LEFT JOIN dm_nganh_to_hop nth ON n.ma_nganh = nth.ma_nganh 
                    GROUP BY n.ma_nganh, n.ten_nganh, n.chi_tieu, n.khoi_xet_tuyen, n.diem_nam_truoc, n.ghi_chu, n.khu_vuc_tuyen_sinh, n.id, n.nhom_nganh, n.nguong_hoc_luc, n.nguong_diem_thpt
                    ORDER BY n.ten_nganh";
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                return $this->getMajors(); 
            }
        });
    }

    // Optimization: Fetch Schools with Province Name
    public function getSchoolsWithProvince() {
        $sql = "SELECT s.*, p.ten_tinh 
                FROM dm_truong_thpt s 
                LEFT JOIN dm_tinh p ON s.ma_tinh = p.ma_tinh 
                ORDER BY s.ten_truong";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getSessions() {
        return $this->getAll('dot_tuyen_sinh', 'id DESC');
    }

    public function getEmailTemplates() {
        return $this->getAll('email_templates', 'code ASC');
    }

    public function getZoneConfigs() {
        $stmt = $this->db->prepare("
            SELECT c.*, t.ten_tinh 
            FROM config_vung_tuyen_sinh c
            LEFT JOIN dm_tinh t ON c.ma_tinh = t.ma_tinh
            ORDER BY c.ma_nganh_prefix, t.ten_tinh
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
