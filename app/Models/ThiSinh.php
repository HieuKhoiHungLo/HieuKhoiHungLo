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

    public function getFiltered($search = '', $status = '', $hocBaStatus = '', $limit = 20, $offset = 0, $sessionId = null, $onlyEditRequests = false, $year = null, $sort = 'ngay_tao', $dir = 'DESC', $excludeTrash = true, $extraFilters = [], $applicationStatus = 'all') {
        $sql = "SELECT t.* FROM {$this->table} t WHERE 1=1";
        $params = [];

        if ($excludeTrash) {
            // Commenting out missing column logic. The new system handles trash at the 'ho_so_xet_tuyen' level instead of 'thi_sinh'.
            // $sql .= " AND (t.is_deleted = FALSE OR t.is_deleted IS NULL)";
        }

        if (!empty($search)) {
            $sql .= " AND (ho_va_ten LIKE ? OR so_cccd LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.trang_thai ILIKE ?)";
            $params[] = "%$status%";
        }

        if ($hocBaStatus !== '') {
            $sql .= " AND t.da_du_6_ky = ?";
            $params[] = ($hocBaStatus == '1' ? 'true' : 'false');
        }
        
        // Session and Year filtering: 
        // If applicationStatus is 'submitted', we strictly filter by session/year.
        // If applicationStatus is 'all' or 'ghost', we only filter by session/year for candidates WHO HAVE an application.
        // Candidates who have NO application (ghosts) should still show up in 'all' or 'ghost' mode.
        if ($applicationStatus === 'submitted') {
            if ($sessionId) {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.dot_tuyen_sinh_id = ?)";
                $params[] = $sessionId;
            } elseif ($year) {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id WHERE hs.so_cccd = t.so_cccd AND dt.dm_nam_tuyen_sinh_nam = ?)";
                $params[] = $year;
            }
        } elseif ($applicationStatus === 'all') {
            if ($sessionId) {
                $sql .= " AND (EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.dot_tuyen_sinh_id = ?) OR NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = t.so_cccd))";
                $params[] = $sessionId;
            } elseif ($year) {
                // If year is specified, we check if application matches year OR if ghost matches year via created_at
                $sql .= " AND (EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id WHERE hs.so_cccd = t.so_cccd AND dt.dm_nam_tuyen_sinh_nam = ?) OR NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = t.so_cccd))";
                $params[] = $year;
            }
        }

        if ($onlyEditRequests) {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.yeu_cau_chinh_sua = TRUE)";
        }

        if ($applicationStatus === 'submitted') {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd)";
        } elseif ($applicationStatus === 'ghost') {
            $sql .= " AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd)";
        }

        // Extra column-specific filters
        if (!empty($extraFilters)) {
            foreach ($extraFilters as $field => $val) {
                if ($val === '' || $val === null) continue;
                if ($field === 'phone') {
                    $sql .= " AND t.dien_thoai LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'dob') {
                    $sql .= " AND t.ngay_sinh::text LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'province') {
                    $sql .= " AND EXISTS (SELECT 1 FROM dm_tinh dt WHERE dt.ma_tinh = t.ma_tinh_ho_khau AND dt.ten_tinh LIKE ?)";
                    $params[] = "%$val%";
                } elseif ($field === 'school') {
                    $sql .= " AND EXISTS (SELECT 1 FROM dm_truong_thpt ds WHERE ds.ma_truong = t.ma_truong_lop_12 AND ds.ten_truong LIKE ?)";
                    $params[] = "%$val%";
                } elseif ($field === 'nv1') {
                    $sql .= " AND EXISTS (SELECT 1 FROM nguyen_vong nv WHERE nv.so_cccd = t.so_cccd AND nv.thu_tu_nguyen_vong = 1 AND nv.ten_nganh LIKE ?)";
                    $params[] = "%$val%";
                } elseif ($field === 'gender') {
                    $sql .= " AND t.gioi_tinh LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'ethnicity') {
                    $sql .= " AND t.dan_toc LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'area') {
                    $sql .= " AND t.khu_vuc_uu_tien LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'object') {
                    $sql .= " AND t.doi_tuong_uu_tien LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'grad_year') {
                    $sql .= " AND t.nam_tot_nghiep::text LIKE ?";
                    $params[] = "%$val%";
                }
            }
        }

        // Validate sort field
        $allowedSort = ['ho_va_ten', 'so_cccd', 'ngay_sinh', 'dien_thoai', 'ngay_tao'];
        if (!in_array($sort, $allowedSort)) $sort = 'ngay_tao';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY $sort $dir LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($candidates)) return [];

        $cccds = array_column($candidates, 'so_cccd');
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));

        $statusSql = "SELECT so_cccd, string_agg(trang_thai, ', ') as statuses 
                      FROM nguyen_vong 
                      WHERE so_cccd IN ($placeholders) 
                      GROUP BY so_cccd";
        $stmtStatus = $this->db->prepare($statusSql);
        $stmtStatus->execute($cccds);
        $statusMap = $stmtStatus->fetchAll(PDO::FETCH_KEY_PAIR);

        $editSql = "SELECT so_cccd, COUNT(*) > 0 as has_edit_request, string_agg(trang_thai, ', ') as master_status 
                    FROM ho_so_xet_tuyen 
                    WHERE so_cccd IN ($placeholders) 
                    GROUP BY so_cccd";
        $stmtEdit = $this->db->prepare($editSql);
        $stmtEdit->execute($cccds);
        $masterStatusMap = $stmtEdit->fetchAll(PDO::FETCH_ASSOC);
        $editMap = [];
        $statusMapHoso = [];
        foreach($masterStatusMap as $ms) {
            $editMap[$ms['so_cccd']] = $ms['has_edit_request'];
            $statusMapHoso[$ms['so_cccd']] = $ms['master_status'];
        }

        // Fetch display names for province and school
        $infoSql = "SELECT t.so_cccd, p.ten_tinh as province_name, s.ten_truong as school_name, nv.ten_nganh as nv1
                    FROM {$this->table} t
                    LEFT JOIN dm_tinh p ON t.ma_tinh_ho_khau = p.ma_tinh
                    LEFT JOIN dm_truong_thpt s ON t.ma_truong_lop_12 = s.ma_truong
                    LEFT JOIN nguyen_vong nv ON t.so_cccd = nv.so_cccd AND nv.thu_tu_nguyen_vong = 1
                    WHERE t.so_cccd IN ($placeholders)";
        $stmtInfo = $this->db->prepare($infoSql);
        $stmtInfo->execute($cccds);
        $infoMap = [];
        while($r = $stmtInfo->fetch(PDO::FETCH_ASSOC)) {
            $infoMap[$r['so_cccd']] = $r;
        }

        foreach ($candidates as &$candidate) {
            $cccd = $candidate['so_cccd'];
            $candidate['statuses'] = $statusMap[$cccd] ?? '';
            $candidate['master_status'] = $statusMapHoso[$cccd] ?? ''; // Use ho_so_xet_tuyen status
            $candidate['has_edit_request'] = !empty($editMap[$cccd]);
            $candidate['province_name'] = $infoMap[$cccd]['province_name'] ?? '';
            $candidate['school_name'] = $infoMap[$cccd]['school_name'] ?? '';
            $candidate['nv1'] = $infoMap[$cccd]['nv1'] ?? '';
        }

        return $candidates;
    }

    public function countFiltered($search = '', $status = '', $hocBaStatus = '', $sessionId = null, $onlyEditRequests = false, $year = null, $excludeTrash = true, $extraFilters = [], $applicationStatus = 'all') {
        $sql = "SELECT COUNT(*) FROM {$this->table} t WHERE 1=1";
        $params = [];

        if ($excludeTrash) {
            // Commenting out missing column logic. The new system handles trash at the 'ho_so_xet_tuyen' level instead of 'thi_sinh'.
            // $sql .= " AND (t.is_deleted = FALSE OR t.is_deleted IS NULL)";
        }

        if (!empty($search)) {
            $sql .= " AND (ho_va_ten LIKE ? OR so_cccd LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.trang_thai ILIKE ?)";
            $params[] = "%$status%";
        }

        if ($hocBaStatus !== '') {
            $sql .= " AND t.da_du_6_ky = ?";
            $params[] = ($hocBaStatus == '1' ? 'true' : 'false');
        }
        
        if ($applicationStatus === 'submitted') {
            if ($sessionId) {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.dot_tuyen_sinh_id = ?)";
                $params[] = $sessionId;
            } elseif ($year) {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id WHERE hs.so_cccd = t.so_cccd AND dt.dm_nam_tuyen_sinh_nam = ?)";
                $params[] = $year;
            }
        } elseif ($applicationStatus === 'all') {
            if ($sessionId) {
                $sql .= " AND (EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.dot_tuyen_sinh_id = ?) OR NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = t.so_cccd))";
                $params[] = $sessionId;
            } elseif ($year) {
                $sql .= " AND (EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id WHERE hs.so_cccd = t.so_cccd AND dt.dm_nam_tuyen_sinh_nam = ?) OR NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = t.so_cccd))";
                $params[] = $year;
            }
        }
        // If applicationStatus === 'ghost', we ignore session/year filters because ghosts have none.
        // Unless we want ghost matching year of registration? For now, keep it simple.

        if ($onlyEditRequests) {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.yeu_cau_chinh_sua = TRUE)";
        }

        if ($applicationStatus === 'submitted') {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd)";
        } elseif ($applicationStatus === 'ghost') {
            $sql .= " AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd)";
        }

        // Extra column-specific filters
        if (!empty($extraFilters)) {
            foreach ($extraFilters as $field => $val) {
                if ($val === '' || $val === null) continue;
                if ($field === 'phone') {
                    $sql .= " AND t.dien_thoai LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'dob') {
                    $sql .= " AND t.ngay_sinh::text LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'province') {
                    $sql .= " AND EXISTS (SELECT 1 FROM dm_tinh dt WHERE dt.ma_tinh = t.ma_tinh_ho_khau AND dt.ten_tinh LIKE ?)";
                    $params[] = "%$val%";
                } elseif ($field === 'school') {
                    $sql .= " AND EXISTS (SELECT 1 FROM dm_truong_thpt ds WHERE ds.ma_truong = t.ma_truong_lop_12 AND ds.ten_truong LIKE ?)";
                    $params[] = "%$val%";
                } elseif ($field === 'nv1') {
                    $sql .= " AND EXISTS (SELECT 1 FROM nguyen_vong nv WHERE nv.so_cccd = t.so_cccd AND nv.thu_tu_nguyen_vong = 1 AND nv.ten_nganh LIKE ?)";
                    $params[] = "%$val%";
                } elseif ($field === 'gender') {
                    $sql .= " AND t.gioi_tinh LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'ethnicity') {
                    $sql .= " AND t.dan_toc LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'area') {
                    $sql .= " AND t.khu_vuc_uu_tien LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'object') {
                    $sql .= " AND t.doi_tuong_uu_tien LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'grad_year') {
                    $sql .= " AND t.nam_tot_nghiep::text LIKE ?";
                    $params[] = "%$val%";
                }
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getStats($sessionId = null, $year = null, $startDate = null, $endDate = null) {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'require_edit' => 0,
            'edit_requests' => 0
        ];

        // Base Query
        $sessionFilter = "";
        if ($sessionId) {
            $sessionFilter = " AND hs.dot_tuyen_sinh_id = " . (int)$sessionId;
        } elseif ($year) {
            $sessionFilter = " AND EXISTS (SELECT 1 FROM dot_tuyen_sinh dt WHERE dt.id = hs.dot_tuyen_sinh_id AND dt.dm_nam_tuyen_sinh_nam = " . (int)$year . ")";
        }

        $sql = "SELECT 
            COUNT(DISTINCT CASE WHEN hs.so_cccd IS NOT NULL $sessionFilter THEN t.so_cccd END) as total,
            COUNT(DISTINCT CASE WHEN hs.trang_thai ILIKE '%Chờ duyệt%' $sessionFilter THEN t.so_cccd END) as pending,
            COUNT(DISTINCT CASE WHEN hs.trang_thai ILIKE '%Đã duyệt%' $sessionFilter THEN t.so_cccd END) as approved,
            COUNT(DISTINCT CASE WHEN hs.trang_thai ILIKE '%Yêu cầu sửa%' $sessionFilter THEN t.so_cccd END) as require_edit,
            COUNT(DISTINCT CASE WHEN hs.yeu_cau_chinh_sua = TRUE $sessionFilter THEN t.so_cccd END) as edit_requests
                FROM {$this->table} t
                LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd
                WHERE 1=1";
        
        $params = [];
        
        // Date filters still restrict the entire set if provided
        if ($startDate && $endDate) {
            $sql .= " AND hs.created_at >= ? AND hs.created_at <= ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $stats['total'] = (int)$result['total'];
                $stats['pending'] = (int)$result['pending'];
                $stats['approved'] = (int)$result['approved'];
                $stats['require_edit'] = (int)$result['require_edit'];
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

    public function verifyEmailAndCCCD($email, $cccd) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? AND so_cccd = ?");
        $stmt->execute([$email, $cccd]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePasswordByCCCD($cccd, $hashedPassword) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET mat_khau = ? WHERE so_cccd = ?");
        return $stmt->execute([$hashedPassword, $cccd]);
    }
    public function updateFullProfile($cccd, $data) {
        $allowed = [
            'ho_va_ten', 'ngay_sinh', 'gioi_tinh', 'dan_toc', 
            'khu_vuc_uu_tien', 'doi_tuong_uu_tien', 'dien_thoai', 'email',
            'ma_tinh_ho_khau', 'ma_tinh_lop_12', 'ma_truong_lop_12', 
            'nam_tot_nghiep', 'so_cccd', // Added so_cccd
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
        return $stmt->execute([$status ? 'true' : 'false', $cccd]);
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
            $stmt->execute([$hasCert ? 'true' : 'false', $cccd]);

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

    /**
     * ONE query → gender + area + object stats (replaces 3 separate queries)
     * Uses a single JOIN to ho_so_xet_tuyen with conditional counts.
     */
    public function getCombinedDemographicStats($startDate = null, $endDate = null, $sessionId = null): array {
        $where  = "WHERE 1=1";
        $params = [];
        if ($startDate && $endDate) {
            $where   .= " AND hs.created_at >= ? AND hs.created_at <= ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate   . ' 23:59:59';
        }
        if ($sessionId) {
            $where   .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        }

        $sqlGender = "SELECT 'gender' as type, COALESCE(ts.gioi_tinh, 'Khác') as label, COUNT(*) as count 
                      FROM {$this->table} ts JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd $where GROUP BY ts.gioi_tinh";
        
        $sqlArea = "SELECT 'area' as type, COALESCE(ts.khu_vuc_uu_tien, 'Không') as label, COUNT(*) as count 
                    FROM {$this->table} ts JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd $where GROUP BY ts.khu_vuc_uu_tien";
        
        $sqlObject = "SELECT 'object' as type, COALESCE(ts.doi_tuong_uu_tien, 'Không') as label, COUNT(*) as count 
                      FROM {$this->table} ts JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd $where GROUP BY ts.doi_tuong_uu_tien";

        $combinedSql = "($sqlGender) UNION ALL ($sqlArea) UNION ALL ($sqlObject)";
        $allParams = array_merge($params, $params, $params);
        
        $stmt = $this->db->prepare($combinedSql);
        $stmt->execute($allParams);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = ['gender' => [], 'area' => [], 'object' => []];
        foreach ($rows as $row) {
            $type = $row['type'];
            $result[$type][] = ['label' => $row['label'], 'count' => (int)$row['count']];
        }

        return $result;
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

    /**
     * Get recent registrations count (today and this week)
     */
    public function getRecentRegistrationStats($sessionId = null) {
        $todayStart = date('Y-m-d 00:00:00');
        $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
        
        $sql = "SELECT 
                  COUNT(DISTINCT CASE WHEN created_at >= ? THEN so_cccd END) as count_today,
                  COUNT(DISTINCT CASE WHEN created_at >= ? THEN so_cccd END) as count_week
                FROM ho_so_xet_tuyen";
        
        $params = [$todayStart, $weekStart];

        if ($sessionId) {
            $sql .= " WHERE dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'today' => (int)($result['count_today'] ?? 0),
            'this_week' => (int)($result['count_week'] ?? 0)
        ];
    }

    /**
     * Get top N latest candidates
     */
    public function getLatestCandidates($limit = 5, $sessionId = null) {
        $sql = "SELECT ts.ho_va_ten AS ho_ten, ts.so_cccd, hs.created_at, hs.trang_thai 
                FROM {$this->table} ts
                JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
                WHERE 1=1";
        $params = [];
        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        }
        $sql .= " ORDER BY hs.created_at DESC LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
