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
            $stmt = $this->db->prepare("SELECT * FROM dm_tinh WHERE COALESCE(is_active, true) = true ORDER BY CASE WHEN ten_tinh LIKE '%Phú Thọ%' THEN 0 ELSE 1 END, ten_tinh");
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
        return $this->getAll('dm_nganh', 'ma_nganh ASC');
    }

    public function getActiveMajors() {
        $stmt = $this->db->prepare("SELECT * FROM dm_nganh WHERE COALESCE(kich_hoat, true) = true ORDER BY ma_nganh ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getWards($provinceId) {
        return \App\Core\Cache::remember("master_wards_{$provinceId}", 1440, function() use ($provinceId) {
            $stmt = $this->db->prepare("SELECT * FROM dm_xa WHERE ma_tinh = ? AND COALESCE(is_active, true) = true ORDER BY ten_xa");
            $stmt->execute([$provinceId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }

    public function getSchools($provinceId) {
        return \App\Core\Cache::remember("master_schools_{$provinceId}", 1440, function() use ($provinceId) {
            $stmt = $this->db->prepare("SELECT * FROM dm_truong_thpt WHERE ma_tinh = ? AND COALESCE(is_active, true) = true ORDER BY ten_truong");
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
            $sql = "SELECT n.*, 
                           (SELECT string_agg(ma_to_hop, ', ') 
                            FROM dm_nganh_to_hop 
                            WHERE ma_nganh = n.ma_nganh) as combination_list 
                    FROM dm_nganh n 
                    ORDER BY n.ma_nganh ASC";
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                return $this->getMajors(); 
            }
        });
    }

    public function getActiveMajorsWithCombinations() {
        return \App\Core\Cache::remember('master_active_majors_combinations', 60, function() {
            $sql = "SELECT n.*, 
                           (SELECT string_agg(ma_to_hop, ', ') 
                            FROM dm_nganh_to_hop 
                            WHERE ma_nganh = n.ma_nganh) as combination_list 
                    FROM dm_nganh n 
                    WHERE COALESCE(n.kich_hoat, true) = true
                    ORDER BY n.ma_nganh ASC";
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                return $this->getActiveMajors(); 
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

    public function getPhuongThuc($activeOnly = false) {
        return \App\Core\Cache::remember('master_phuong_thuc_' . ($activeOnly ? 'active' : 'all'), 1440, function() use ($activeOnly) {
            $sql = "SELECT * FROM dm_phuong_thuc";
            if ($activeOnly) {
                $sql .= " WHERE is_active = TRUE";
            }
            $sql .= " ORDER BY thu_tu ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getSchoolsPaginated($search = '', $maTinh = '', $khuVuc = '', $sort = 'ten_truong', $dir = 'ASC', $limit = 10, $offset = 0) {
        $sql = "SELECT s.*, p.ten_tinh 
                FROM dm_truong_thpt s 
                LEFT JOIN dm_tinh p ON s.ma_tinh = p.ma_tinh 
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (s.ten_truong ILIKE ? OR s.ma_truong ILIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if (!empty($maTinh)) {
            $sql .= " AND s.ma_tinh = ?";
            $params[] = $maTinh;
        }

        if (!empty($khuVuc)) {
            $sql .= " AND s.khu_vuc = ?";
            $params[] = $khuVuc;
        }

        $allowedSort = ['ma_truong', 'ten_truong', 'khu_vuc', 'ma_tinh', 'ten_tinh'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'ten_truong';
        }
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        if ($sort === 'ma_tinh' || $sort === 'ten_tinh') {
            $sortColumn = $sort === 'ma_tinh' ? 's.ma_tinh' : 'p.ten_tinh';
            $sql .= " ORDER BY CASE WHEN s.ma_tinh = '25' THEN 0 ELSE 1 END, {$sortColumn} {$dir}, s.ma_truong ASC";
        } else {
            $sortColumn = ($sort === 'ma_truong') ? 's.ma_truong' : (($sort === 'khu_vuc') ? 's.khu_vuc' : 's.ten_truong');
            $sql .= " ORDER BY {$sortColumn} {$dir}, s.ma_truong ASC";
        }
        $sql .= " LIMIT ? OFFSET ?";
        
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->db->prepare($sql);
        
        $index = 1;
        foreach ($params as $param) {
            if (is_int($param)) {
                $stmt->bindValue($index++, $param, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($index++, $param, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countSchoolsFiltered($search = '', $maTinh = '', $khuVuc = '') {
        $sql = "SELECT COUNT(*) 
                FROM dm_truong_thpt s 
                LEFT JOIN dm_tinh p ON s.ma_tinh = p.ma_tinh 
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (s.ten_truong ILIKE ? OR s.ma_truong ILIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if (!empty($maTinh)) {
            $sql .= " AND s.ma_tinh = ?";
            $params[] = $maTinh;
        }

        if (!empty($khuVuc)) {
            $sql .= " AND s.khu_vuc = ?";
            $params[] = $khuVuc;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // --- Province Paginated Queries ---
    public function getProvincesPaginated($search = '', $sort = 'ma_tinh', $dir = 'ASC', $limit = 15, $offset = 0) {
        $sql = "SELECT * FROM dm_tinh WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (ten_tinh ILIKE ? OR ma_tinh ILIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $allowedSort = ['ma_tinh', 'ten_tinh'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'ma_tinh';
        }
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        $sql .= " ORDER BY {$sort} {$dir}";
        $sql .= " LIMIT ? OFFSET ?";
        
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->db->prepare($sql);
        
        $index = 1;
        foreach ($params as $param) {
            if (is_int($param)) {
                $stmt->bindValue($index++, $param, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($index++, $param, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countProvincesFiltered($search = '') {
        $sql = "SELECT COUNT(*) FROM dm_tinh WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (ten_tinh ILIKE ? OR ma_tinh ILIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function isProvinceInUse($maTinh) {
        // Check if used in dm_xa
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM dm_xa WHERE ma_tinh = ?");
        $stmt->execute([$maTinh]);
        if ((int)$stmt->fetchColumn() > 0) return true;

        // Check if used in dm_truong_thpt
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM dm_truong_thpt WHERE ma_tinh = ?");
        $stmt->execute([$maTinh]);
        if ((int)$stmt->fetchColumn() > 0) return true;

        // Check if used in ho_so_xet_tuyen (ma_tinh_ho_khau or ma_tinh_thuong_tru or ma_tinh_lop_12)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE ma_tinh_ho_khau = ? OR ma_tinh_thuong_tru = ? OR ma_tinh_lop_12 = ?");
        $stmt->execute([$maTinh, $maTinh, $maTinh]);
        if ((int)$stmt->fetchColumn() > 0) return true;

        return false;
    }

    // --- Ward Paginated Queries ---
    public function getWardsPaginated($search = '', $maTinh = '', $sort = 'ma_tinh', $dir = 'ASC', $limit = 15, $offset = 0) {
        $sql = "SELECT w.*, p.ten_tinh 
                FROM dm_xa w 
                LEFT JOIN dm_tinh p ON w.ma_tinh = p.ma_tinh 
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (w.ten_xa ILIKE ? OR w.ma_xa ILIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if (!empty($maTinh)) {
            $sql .= " AND w.ma_tinh = ?";
            $params[] = $maTinh;
        }

        $allowedSort = ['ma_xa', 'ten_xa', 'ma_tinh', 'ten_tinh'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'ma_tinh';
        }
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        if ($sort === 'ma_tinh' || $sort === 'ten_tinh') {
            $sortColumn = $sort === 'ma_tinh' ? 'w.ma_tinh' : 'p.ten_tinh';
            $sql .= " ORDER BY CASE WHEN w.ma_tinh = '25' THEN 0 ELSE 1 END, {$sortColumn} {$dir}, w.ma_xa ASC";
        } else {
            $sortColumn = $sort === 'ma_xa' ? 'w.ma_xa' : 'w.ten_xa';
            $sql .= " ORDER BY {$sortColumn} {$dir}, w.ma_xa ASC";
        }
        
        $sql .= " LIMIT ? OFFSET ?";
        
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->db->prepare($sql);
        
        $index = 1;
        foreach ($params as $param) {
            if (is_int($param)) {
                $stmt->bindValue($index++, $param, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($index++, $param, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countWardsFiltered($search = '', $maTinh = '') {
        $sql = "SELECT COUNT(*) 
                FROM dm_xa w 
                LEFT JOIN dm_tinh p ON w.ma_tinh = p.ma_tinh 
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (w.ten_xa ILIKE ? OR w.ma_xa ILIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if (!empty($maTinh)) {
            $sql .= " AND w.ma_tinh = ?";
            $params[] = $maTinh;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function isWardInUse($maXa) {
        // Check if used in ho_so_xet_tuyen (ma_xa_ho_khau or ma_xa_thuong_tru)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE ma_xa_ho_khau = ? OR ma_xa_thuong_tru = ?");
        $stmt->execute([$maXa, $maXa]);
        return ((int)$stmt->fetchColumn() > 0);
    }

    public function isSchoolInUse($maTruong) {
        // Check if used in thi_sinh (ma_truong_lop_12)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM thi_sinh WHERE ma_truong_lop_12 = ?");
        $stmt->execute([$maTruong]);
        return ((int)$stmt->fetchColumn() > 0);
    }
}
