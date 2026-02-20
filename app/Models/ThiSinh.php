<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ThiSinh extends Model {
    protected $table = 'thi_sinh';

    public function findByCCCD($cccd) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findAll() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY ngay_tao DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFiltered($search = '', $status = '', $hocBaStatus = '', $limit = 20, $offset = 0, $sessionId = null, $onlyEditRequests = false) {
        // 1. Build Base Query to get Candidates first (Optimize OFFSET/LIMIT)
        $sql = "SELECT t.* FROM {$this->table} t WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (ho_va_ten LIKE ? OR so_cccd LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            $sql .= " AND EXISTS (SELECT 1 FROM nguyen_vong nv WHERE nv.so_cccd = t.so_cccd AND nv.trang_thai = ?)";
            $params[] = $status;
        }

        if ($hocBaStatus !== '') {
            $sql .= " AND t.da_du_6_ky = ?";
            $params[] = ($hocBaStatus == '1' ? true : false);
        }
        
        if ($sessionId) {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen WHERE so_cccd = t.so_cccd AND dot_tuyen_sinh_id = ?)";
            $params[] = $sessionId;
        }

        if ($onlyEditRequests) {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.yeu_cau_chinh_sua = TRUE)";
        }

        // Add Order By
        // Note: Ordering by 'has_edit_request' is tricky without subquery/join. 
        // We can keep it simple: Order by Created At, OR join specifically for ordering if strictly needed.
        // For performance, let's optimize core list. If sort by edit request is P0, we need a join.
        // Let's add a lightweight LEFT JOIN only if we need to sort by complex field, 
        // OR just keep it simple: sort by ngay_tao DESC.
        // The original query sorted by `has_edit_request DESC`. To keep this behavior without N+1 in SELECT:
        // We SHOULD Join with ho_so_xet_tuyen for sorting/filtering anyway.
        
        // Revised Strategy: LEFT JOIN for sorting and data fetching in one go IS better than dependent subquery SELECTs
        // PostGres optimizes JOINs well.
        
        $selectSql = "SELECT t.*, 
                       string_agg(DISTINCT nv.trang_thai, ', ') as statuses,
                       MAX(hs.yeu_cau_chinh_sua) as has_edit_request
                      FROM {$this->table} t
                      LEFT JOIN nguyen_vong nv ON t.so_cccd = nv.so_cccd
                      LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd
                      WHERE 1=1";
                      
        // BUT, if we JOIN, pagination limit applies to Result Rows (which might be multiplied by nguyen_vong).
        // GROUP BY is needed.
        
        // Let's stick to the "Fetch IDs first" strategy (Eager Load) which is safest for Pagination + One-to-Many.
        
        // 1. Fetch filtered Candidates
        $sql .= " ORDER BY ngay_tao DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($candidates)) {
            return [];
        }

        // 2. Extract CCCDs
        $cccds = array_column($candidates, 'so_cccd');
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));

        // 3. Eager Load Statuses (nguyen_vong)
        $statusSql = "SELECT so_cccd, string_agg(trang_thai, ', ') as statuses 
                      FROM nguyen_vong 
                      WHERE so_cccd IN ($placeholders) 
                      GROUP BY so_cccd";
        $stmtStatus = $this->db->prepare($statusSql);
        $stmtStatus->execute($cccds);
        $statusMap = [];
        while ($row = $stmtStatus->fetch(PDO::FETCH_ASSOC)) {
            $statusMap[$row['so_cccd']] = $row['statuses'];
        }

        // 4. Eager Load Edit Requests (ho_so_xet_tuyen)
        // Note: Filter by session? Maybe, but usually has_edit_request is global or we want to see any.
        // Original logic: "AND hs.yeu_cau_chinh_sua = TRUE" count > 0
        $editSql = "SELECT so_cccd, COUNT(*) > 0 as has_edit_request 
                    FROM ho_so_xet_tuyen 
                    WHERE so_cccd IN ($placeholders) AND yeu_cau_chinh_sua = TRUE 
                    GROUP BY so_cccd";
        $stmtEdit = $this->db->prepare($editSql);
        $stmtEdit->execute($cccds);
        $editMap = [];
        while ($row = $stmtEdit->fetch(PDO::FETCH_ASSOC)) {
            $editMap[$row['so_cccd']] = $row['has_edit_request'];
        }

        // 5. Merge Data
        foreach ($candidates as &$candidate) {
            $cccd = $candidate['so_cccd'];
            $candidate['statuses'] = $statusMap[$cccd] ?? '';
            $candidate['has_edit_request'] = $editMap[$cccd] ?? false;
        }

        // 6. Sort (Optional, since we lost 'ORDER BY has_edit_request' from SQL)
        // If sorting by `has_edit_request` is critical, we can do it in PHP code since page size is small (20).
        usort($candidates, function($a, $b) {
            if ($a['has_edit_request'] == $b['has_edit_request']) {
                return 0; // Keep original order (ngay_tao DESC)
            }
            return ($a['has_edit_request'] ? -1 : 1);
        });

        return $candidates;
    }

    public function countFiltered($search = '', $status = '', $hocBaStatus = '', $sessionId = null, $onlyEditRequests = false) {
        $sql = "SELECT COUNT(*) FROM {$this->table} t WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (ho_va_ten LIKE ? OR so_cccd LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            $sql .= " AND EXISTS (SELECT 1 FROM nguyen_vong nv WHERE nv.so_cccd = t.so_cccd AND nv.trang_thai = ?)";
            $params[] = $status;
        }

        if ($hocBaStatus !== '') {
            $sql .= " AND t.da_du_6_ky = ?";
            $params[] = ($hocBaStatus == '1' ? true : false);
        }
        
        if ($sessionId) {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen WHERE so_cccd = t.so_cccd AND dot_tuyen_sinh_id = ?)";
            $params[] = $sessionId;
        }

        if ($onlyEditRequests) {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.yeu_cau_chinh_sua = TRUE)";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getStats($sessionId = null, $year = null) {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'edit_requests' => 0
        ];

        // Base Query
        $sql = "SELECT 
                    COUNT(DISTINCT t.so_cccd) as total,
                    COUNT(DISTINCT CASE WHEN nv.trang_thai = 'Chờ duyệt' THEN t.so_cccd END) as pending,
                    COUNT(DISTINCT CASE WHEN nv.trang_thai = 'Đã duyệt' THEN t.so_cccd END) as approved,
                    COUNT(DISTINCT CASE WHEN nv.trang_thai = 'Từ chối' THEN t.so_cccd END) as rejected,
                    COUNT(DISTINCT CASE WHEN hs.yeu_cau_chinh_sua = TRUE THEN t.so_cccd END) as edit_requests
                FROM {$this->table} t
                LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd
                LEFT JOIN nguyen_vong nv ON t.so_cccd = nv.so_cccd
                WHERE 1=1";

        $params = [];

        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        } elseif ($year) {
            $sql .= " AND EXISTS (SELECT 1 FROM dot_tuyen_sinh dt WHERE dt.id = hs.dot_tuyen_sinh_id AND dt.dm_nam_tuyen_sinh_nam = ?)";
            $params[] = $year;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $stats['total'] = (int)$result['total'];
                $stats['pending'] = (int)$result['pending'];
                $stats['approved'] = (int)$result['approved'];
                $stats['rejected'] = (int)$result['rejected'];
                $stats['edit_requests'] = (int)$result['edit_requests'];
            }
        } catch (\PDOException $e) {
            error_log("Error in getStats: " . $e->getMessage());
        }

        return $stats;
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (so_cccd, ho_va_ten, mat_khau, dien_thoai, email) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['cccd'],
            $data['fullname'],
            $data['password'],
            $data['phone'],
            $data['email']
        ]);
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePasswordByEmail($email, $hashedPassword) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET mat_khau = ? WHERE email = ?");
        return $stmt->execute([$hashedPassword, $email]);
    }
    public function updateFullProfile($cccd, $data) {
        $allowed = [
            'ho_va_ten', 'ngay_sinh', 'gioi_tinh', 'dan_toc', 
            'khu_vuc_uu_tien', 'doi_tuong_uu_tien', 'dien_thoai', 'email',
            'ma_tinh_ho_khau', 'ma_tinh_lop_12', 'ma_truong_lop_12', 
            'nam_tot_nghiep', // Added field
            'ma_tinh_thuong_tru', 'ma_xa_thuong_tru', 'dia_chi_chi_tiet',
            'is_custom_kv', 'is_custom_dt',
            'anh_dai_dien', 'anh_cccd_truoc', 'anh_cccd_sau', 
            'file_minh_chung_kv', 'file_minh_chung_dt'
        ];

        $sets = [];
        $params = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                 $sets[] = "$field = ?";
                 // Handle boolean conversion specifically if passed as boolean
                 if (($field == 'is_custom_kv' || $field == 'is_custom_dt')) {
                     $params[] = $data[$field] ? 'true' : 'false';
                 } else {
                     $params[] = $data[$field];
                 }
            }
        }
        
        if (empty($sets)) return true;

        $params[] = $cccd;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE so_cccd = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // Keep legacy methods for potential backward compatibility or internal use during transition
    public function updateInfo($cccd, $data) {
        $sql = "UPDATE {$this->table} SET 
                ho_va_ten = ?, ngay_sinh = ?, gioi_tinh = ?, dan_toc = ?, 
                khu_vuc_uu_tien = ?, doi_tuong_uu_tien = ?, dien_thoai = ?, email = ?
                WHERE so_cccd = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$data['ho_va_ten'], $data['ngay_sinh'], $data['gioi_tinh'], $data['dan_toc'], $data['khu_vuc_uu_tien'], $data['doi_tuong_uu_tien'], $data['dien_thoai'], $data['email'], $cccd]);
    }

    public function updateContactInfo($cccd, $data) {
        $sql = "UPDATE {$this->table} SET ma_tinh_thuong_tru = ?, ma_xa_thuong_tru = ?, dia_chi_chi_tiet = ? WHERE so_cccd = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$data['province'], $data['ward'], $data['address'], $cccd]);
    }

    public function updateDocuments($cccd, $data) {
        $sql = "UPDATE {$this->table} SET anh_dai_dien = ?, anh_cccd_truoc = ?, anh_cccd_sau = ? WHERE so_cccd = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$data['anh_dai_dien'], $data['anh_cccd_truoc'], $data['anh_cccd_sau'], $cccd]);
    }

    public function updateHocBaStatus($cccd, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET da_du_6_ky = ? WHERE so_cccd = ?");
        return $stmt->execute([$status ? true : false, $cccd]);
    }

    public function getCertifications($cccd) {
        $stmt = $this->db->prepare("SELECT * FROM chung_chi_thi_sinh WHERE so_cccd = ? ORDER BY id ASC");
        $stmt->execute([$cccd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveCertifications($cccd, $certs) {
        try {
            $this->db->beginTransaction();
            
            // Delete old certs
            $stmt = $this->db->prepare("DELETE FROM chung_chi_thi_sinh WHERE so_cccd = ?");
            $stmt->execute([$cccd]);

            // Track if user has any cert for the flag in thi_sinh
            $hasCert = false;

            // Insert new certs
            if (!empty($certs)) {
                $stmt = $this->db->prepare("INSERT INTO chung_chi_thi_sinh (so_cccd, loai_chung_chi, diem_chung_chi, file_minh_chung_cc) VALUES (?, ?, ?, ?)");
                foreach ($certs as $cert) {
                    if (!empty($cert['loai_chung_chi'])) {
                        $stmt->execute([
                            $cccd, 
                            $cert['loai_chung_chi'], 
                            $cert['diem_chung_chi'] ?? null, 
                            $cert['file_minh_chung_cc'] ?? null
                        ]);
                        $hasCert = true;
                    }
                }
            }

            // Sync flag in thi_sinh for backward compatibility/quick filter
            $stmt = $this->db->prepare("UPDATE {$this->table} SET co_chung_chi_qt = ? WHERE so_cccd = ?");
            $stmt->execute([$hasCert ? true : false, $cccd]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }


    public function getSchoolStats($limit = 10, $startDate = null, $endDate = null, $sessionId = null) {
         $sql = "SELECT COALESCE(truong.ten_truong, 'Chưa cập nhật') as label, COUNT(DISTINCT ts.so_cccd) as count 
                FROM {$this->table} ts
                JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
                LEFT JOIN dm_truong_thpt truong ON ts.ma_truong_lop_12 = truong.ma_truong 
                WHERE 1=1";
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " AND hs.created_at >= ? AND hs.created_at <= ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }
        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        }
        $sql .= " GROUP BY truong.ten_truong ORDER BY count DESC LIMIT " . intval($limit);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProvinceStats($limit = 10, $startDate = null, $endDate = null, $sessionId = null) {
        $sql = "SELECT p.ten_tinh as label, COUNT(DISTINCT ts.so_cccd) as count 
                FROM {$this->table} ts
                JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
                JOIN dm_tinh p ON ts.ma_tinh_ho_khau = p.ma_tinh
                WHERE 1=1";
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " AND hs.created_at >= ? AND hs.created_at <= ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }
        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        }
        $sql .= " GROUP BY p.ten_tinh ORDER BY count DESC LIMIT " . intval($limit);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getGenderStats($startDate = null, $endDate = null, $sessionId = null) {
        $sql = "SELECT CASE WHEN ts.gioi_tinh = 'Nam' THEN 'Nam' WHEN ts.gioi_tinh = 'Nữ' THEN 'Nữ' ELSE 'Khác' END as label, COUNT(DISTINCT ts.so_cccd) as count 
                FROM {$this->table} ts
                JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
                WHERE 1=1";
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " AND hs.created_at >= ? AND hs.created_at <= ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }
        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        }
        $sql .= " GROUP BY ts.gioi_tinh";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAreaStats($startDate = null, $endDate = null, $sessionId = null) {
        $sql = "SELECT COALESCE(ts.khu_vuc_uu_tien, 'Không') as label, COUNT(DISTINCT ts.so_cccd) as count 
                FROM {$this->table} ts
                JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
                WHERE 1=1";
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " AND hs.created_at >= ? AND hs.created_at <= ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }
        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        }
        $sql .= " GROUP BY ts.khu_vuc_uu_tien";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getObjectStats($startDate = null, $endDate = null, $sessionId = null) {
        $sql = "SELECT COALESCE(ts.doi_tuong_uu_tien, 'Không') as label, COUNT(DISTINCT ts.so_cccd) as count 
                FROM {$this->table} ts
                JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
                WHERE 1=1";
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " AND hs.created_at >= ? AND hs.created_at <= ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }
        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        }
        $sql .= " GROUP BY ts.doi_tuong_uu_tien";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function delete($cccd) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE so_cccd = ?");
        return $stmt->execute([$cccd]);
    }
    public function updateWishesStatus($cccd, $status) {
        $sql = "UPDATE nguyen_vong SET trang_thai = ? WHERE so_cccd = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $cccd]);
    }
}
