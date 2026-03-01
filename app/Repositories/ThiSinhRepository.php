<?php
namespace App\Repositories;

use App\Models\ThiSinh;
use App\Core\Database;
use PDO;

class ThiSinhRepository {
    protected $db;
    protected $model;
    protected $table = 'thi_sinh';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->model = new ThiSinh();
    }

    public function findByCCCD($cccd) {
        return $this->model->findByCCCD($cccd);
    }

    public function findManyByCCCD(array $cccds) {
        if (empty($cccds)) return [];
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd IN ($placeholders)");
        $stmt->execute($cccds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmailsByIds(array $ids) {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT so_cccd, ho_va_ten, email FROM {$this->table} WHERE so_cccd IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetail($cccd) {
        $sql = "SELECT t.*, 
                p1.ten_tinh as ten_tinh_hk,
                p2.ten_tinh as ten_tinh_tt,
                x.ten_xa as ten_xa_tt,
                s.ten_truong as ten_truong_lop_12,
                dt.ten_dt as ten_doi_tuong_ut,
                kv.ten_kv as ten_khu_vuc_ut,
                hs.trang_thai, hs.ghi_chu, hs.yeu_cau_chinh_sua
                FROM {$this->table} t
                LEFT JOIN dm_tinh p1 ON t.ma_tinh_ho_khau = p1.ma_tinh
                LEFT JOIN dm_tinh p2 ON t.ma_tinh_thuong_tru = p2.ma_tinh
                LEFT JOIN dm_xa x ON t.ma_xa_thuong_tru = x.ma_xa
                LEFT JOIN dm_truong_thpt s ON t.ma_truong_lop_12 = s.ma_truong
                LEFT JOIN dm_doi_tuong dt ON t.doi_tuong_uu_tien = dt.ma_dt
                LEFT JOIN dm_khu_vuc kv ON t.khu_vuc_uu_tien = kv.ma_kv
                LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd
                WHERE t.so_cccd = ?
                ORDER BY hs.created_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cccd]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Load ALL review data in a single DB round-trip using PostgreSQL JSON aggregation.
     * Returns: ['user' => [...], 'academic' => [...], 'choices' => [...], 'certificates' => [...], 'diemThi' => [...]]
     */
    public function getReviewBundle($cccd) {
        $sql = "SELECT 
            -- Main candidate data
            t.*, 
            p1.ten_tinh as ten_tinh_hk,
            p2.ten_tinh as ten_tinh_tt,
            x.ten_xa as ten_xa_tt,
            s.ten_truong as ten_truong_lop_12,
            dt.ten_dt as ten_doi_tuong_ut,
            kv.ten_kv as ten_khu_vuc_ut,
            hs.trang_thai, hs.ghi_chu, hs.yeu_cau_chinh_sua,
            -- Academic records as JSON array
            COALESCE((SELECT json_agg(row_to_json(hb.*) ORDER BY hb.id) FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd), '[]'::json) as _academic_json,
            -- Nguyen vong as JSON array  
            COALESCE((SELECT json_agg(row_to_json(nv.*) ORDER BY nv.thu_tu_nguyen_vong) FROM nguyen_vong nv WHERE nv.so_cccd = t.so_cccd), '[]'::json) as _choices_json,
            -- Certificates as JSON array
            COALESCE((SELECT json_agg(row_to_json(cc.*)) FROM chung_chi_thi_sinh cc WHERE cc.so_cccd = t.so_cccd), '[]'::json) as _certs_json,
            -- THPT scores as JSON object
            (SELECT row_to_json(dth.*) FROM diem_thi_thpt dth WHERE dth.so_cccd = t.so_cccd LIMIT 1) as _diemthi_json
            FROM {$this->table} t
            LEFT JOIN dm_tinh p1 ON t.ma_tinh_ho_khau = p1.ma_tinh
            LEFT JOIN dm_tinh p2 ON t.ma_tinh_thuong_tru = p2.ma_tinh
            LEFT JOIN dm_xa x ON t.ma_xa_thuong_tru = x.ma_xa
            LEFT JOIN dm_truong_thpt s ON t.ma_truong_lop_12 = s.ma_truong
            LEFT JOIN dm_doi_tuong dt ON t.doi_tuong_uu_tien = dt.ma_dt
            LEFT JOIN dm_khu_vuc kv ON t.khu_vuc_uu_tien = kv.ma_kv
            LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd
            WHERE t.so_cccd = ?
            ORDER BY hs.created_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cccd]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) return null;

        // Extract embedded JSON and remove from main user array
        $academicJson = $row['_academic_json'] ?? '[]';
        $choicesJson = $row['_choices_json'] ?? '[]';
        $certsJson = $row['_certs_json'] ?? '[]';
        $diemThiJson = $row['_diemthi_json'] ?? null;
        
        unset($row['_academic_json'], $row['_choices_json'], $row['_certs_json'], $row['_diemthi_json']);

        return [
            'user' => $row,
            'academic' => json_decode($academicJson, true) ?: [],
            'choices' => json_decode($choicesJson, true) ?: [],
            'certificates' => json_decode($certsJson, true) ?: [],
            'diemThi' => $diemThiJson ? json_decode($diemThiJson, true) : null
        ];
    }

    public function findByEmail($email) {
        return $this->model->findByEmail($email);
    }

    public function create(array $data) {
        return $this->model->create($data);
    }

    public function updateFullProfile($cccd, array $data) {
        return $this->model->updateFullProfile($cccd, $data);
    }

    public function delete($cccd) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = NOW() WHERE so_cccd = ?");
        return $stmt->execute([$cccd]);
    }

    public function restore($cccd) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = NULL WHERE so_cccd = ?");
        return $stmt->execute([$cccd]);
    }

    public function forceDelete($cccd) {
        return $this->model->delete($cccd);
    }

    public function getFiltered($search = '', $status = '', $hocBaStatus = '', $limit = 20, $offset = 0, $sessionId = null, $onlyEditRequests = false, $year = null, $sort = 'created_at', $dir = 'desc', $trashed = false) {
        // Single query with all JOINs + COUNT OVER() — eliminates 7 separate queries
        $sql = "SELECT DISTINCT t.*,
                p.ten_tinh as province_name,
                sc.ten_truong as school_name,
                (SELECT string_agg(nv2.trang_thai, ', ') FROM nguyen_vong nv2 WHERE nv2.so_cccd = t.so_cccd) as statuses,
                (SELECT CONCAT(nv3.ma_nganh, ' - ', nv3.ten_nganh) FROM nguyen_vong nv3 WHERE nv3.so_cccd = t.so_cccd AND nv3.thu_tu_nguyen_vong = 1 LIMIT 1) as nv1,
                COALESCE(hs.yeu_cau_chinh_sua, false) as has_edit_request,
                hs.dot_tuyen_sinh_id,
                COUNT(*) OVER() as _total_count
                FROM {$this->table} t";
        
        $sql .= " LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd";
        $sql .= " LEFT JOIN dm_tinh p ON t.ma_tinh_ho_khau = p.ma_tinh";
        $sql .= " LEFT JOIN dm_truong_thpt sc ON t.ma_truong_lop_12 = sc.ma_truong";
        
        if ($year) {
            $sql .= " LEFT JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id";
        }

        if (!empty($status)) {
            $sql .= " LEFT JOIN nguyen_vong nv ON t.so_cccd = nv.so_cccd";
        }

        $sql .= " WHERE 1=1";
        if ($trashed) {
             $sql .= " AND t.deleted_at IS NOT NULL";
        } else {
             $sql .= " AND t.deleted_at IS NULL";
        }
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (t.ho_va_ten LIKE ? OR t.so_cccd LIKE ? OR t.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            $sql .= " AND nv.trang_thai = ?";
            $params[] = $status;
        }

        if ($hocBaStatus !== '') {
            $sql .= " AND t.da_du_6_ky = ?";
            $params[] = ($hocBaStatus == '1' ? 1 : 0);
        }
        
        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        } elseif ($year) {
             $sql .= " AND (dt.dm_nam_tuyen_sinh_nam = ? OR dt.nam_tuyen_sinh = ?)"; 
             $params[] = $year;
             $params[] = $year;
        }

        if ($onlyEditRequests) {
            $sql .= " AND hs.yeu_cau_chinh_sua = TRUE";
        }

        // Sorting
        $allowedSorts = [
            'created_at' => 't.ngay_tao', 
            'name' => 't.ho_va_ten', 
            'cccd' => 't.so_cccd',
            'province' => 'p.ten_tinh',
            'school' => 'sc.ten_truong',
            'nv1' => 'nv1'
        ];
        $sortField = $allowedSorts[$sort] ?? 't.ngay_tao';
        $sortDir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql .= " ORDER BY $sortField $sortDir LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countFiltered($search = '', $status = '', $hocBaStatus = '', $sessionId = null, $onlyEditRequests = false, $year = null, $trashed = false) {
        $sql = "SELECT COUNT(DISTINCT t.so_cccd) FROM {$this->table} t";
        
        // Always join ho_so_xet_tuyen
        $sql .= " LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd";
        
        if ($year) {
            $sql .= " LEFT JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id";
        }
        
        if (!empty($status)) {
            $sql .= " LEFT JOIN nguyen_vong nv ON t.so_cccd = nv.so_cccd";
        }

        $sql .= " WHERE 1=1";
        if ($trashed) {
             $sql .= " AND t.deleted_at IS NOT NULL";
        } else {
             $sql .= " AND t.deleted_at IS NULL";
        }
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (t.ho_va_ten LIKE ? OR t.so_cccd LIKE ? OR t.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            $sql .= " AND nv.trang_thai = ?";
            $params[] = $status;
        }

        if ($hocBaStatus !== '') {
            $sql .= " AND t.da_du_6_ky = ?";
            $params[] = ($hocBaStatus == '1' ? 'true' : 'false');
        }

        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        } elseif ($year) {
             $sql .= " AND (dt.dm_nam_tuyen_sinh_nam = ? OR dt.nam_tuyen_sinh = ?)";
             $params[] = $year;
             $params[] = $year;
        }

        if ($onlyEditRequests) {
            $sql .= " AND hs.yeu_cau_chinh_sua = TRUE";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    // Bulk Actions
    public function bulkUpdateStatus($cccds, $status) {
        if (empty($cccds)) return false;
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));
        
        // Update nguyen_vong status
        $sql = "UPDATE nguyen_vong SET trang_thai = ? WHERE so_cccd IN ($placeholders)";
        $params = array_merge([$status], $cccds);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // Update ho_so_xet_tuyen status as well for consistency
        $sql2 = "UPDATE ho_so_xet_tuyen SET trang_thai = ? WHERE so_cccd IN ($placeholders)";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->execute($params);
        
        return true;
    }

    public function bulkTransferSession($cccds, $sessionId) {
        if (empty($cccds)) return false;
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));
        
        try {
            $this->db->beginTransaction();
            
            // 1. Xóa hồ sơ trùng ở đợt đích (nếu thí sinh đã có hồ sơ ở đó)
            $sqlDelete = "DELETE FROM ho_so_xet_tuyen WHERE so_cccd IN ($placeholders) AND dot_tuyen_sinh_id = ?";
            $paramsDelete = array_merge($cccds, [$sessionId]);
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute($paramsDelete);
            
            // 2. Chuyển hồ sơ hiện tại sang đợt mới
            $sqlUpdate = "UPDATE ho_so_xet_tuyen SET dot_tuyen_sinh_id = ? WHERE so_cccd IN ($placeholders)";
            $paramsUpdate = array_merge([$sessionId], $cccds);
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute($paramsUpdate);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Transfer session failed: " . $e->getMessage());
            return false;
        }
    }

    public function bulkDelete($cccds) {
        if (empty($cccds)) return false;
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));
        
        $sql = "UPDATE thi_sinh SET deleted_at = NOW() WHERE so_cccd IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($cccds);
    }

    public function bulkRestore($cccds) {
        if (empty($cccds)) return false;
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));
        
        $sql = "UPDATE thi_sinh SET deleted_at = NULL WHERE so_cccd IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($cccds);
    }

    public function bulkForceDelete($cccds) {
        if (empty($cccds)) return false;
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));
        
        try {
            $this->db->beginTransaction();
            
            // Delete related records first if no cascade (or just in case)
            $tables = ['chung_chi_thi_sinh', 'nguyen_vong', 'ho_so_xet_tuyen', 'diem_thi_thpt', 'ket_qua_hoc_tap'];
            foreach ($tables as $tb) {
                 // Check if table exists/relation exists. Assuming standard schema.
                 // Actually basic tables are safe.
                 $this->db->exec("DELETE FROM $tb WHERE so_cccd IN ($placeholders)");
            }

            $stmt4 = $this->db->prepare("DELETE FROM thi_sinh WHERE so_cccd IN ($placeholders)");
            $stmt4->execute($cccds);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function hasEditRequest($cccd) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE so_cccd = ? AND yeu_cau_chinh_sua = TRUE");
        $stmt->execute([$cccd]);
        return $stmt->fetchColumn() > 0;
    }

    public function approveEditRequest($cccd) {
        $stmt = $this->db->prepare("UPDATE ho_so_xet_tuyen SET yeu_cau_chinh_sua = FALSE, trang_thai = 'Chờ duyệt' WHERE so_cccd = ? AND yeu_cau_chinh_sua = TRUE");
        return $stmt->execute([$cccd]);
    }

    public function requestEditPermission($applicationId) {
        $stmt = $this->db->prepare("UPDATE ho_so_xet_tuyen SET yeu_cau_chinh_sua = TRUE, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$applicationId]);
    }

    public function updateApplicationStatus($cccd, $status, $note = null, $reviewerId = null) {
        // Also update ho_so_xet_tuyen
        $sql = "UPDATE ho_so_xet_tuyen SET trang_thai = ?, yeu_cau_chinh_sua = FALSE";
        $params = [$status];
        
        if ($note !== null) {
            $sql .= ", ghi_chu = ?";
            $params[] = $note;
        }

        if ($reviewerId !== null) {
            $sql .= ", nguoi_duyet_id = ?";
            $params[] = $reviewerId;
        }
        
        $sql .= ", updated_at = NOW() WHERE so_cccd = ?";
        $params[] = $cccd;
        
        $stmt2 = $this->db->prepare($sql);
        $stmt2->execute($params);

        $stmt = $this->db->prepare("UPDATE nguyen_vong SET trang_thai = ? WHERE so_cccd = ?");
        return $stmt->execute([$status, $cccd]);
    }

    public function getNextPendingCandidate($currentCCCD, $sessionId = null, $year = null) {
        // Get current candidate metadata for sequence
        $stmt = $this->db->prepare("SELECT dot_tuyen_sinh_id, created_at FROM ho_so_xet_tuyen WHERE so_cccd = ?");
        $stmt->execute([$currentCCCD]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            // Fallback to simpler search if current record not found
            $sql = "SELECT hs.so_cccd FROM ho_so_xet_tuyen hs WHERE hs.trang_thai = 'Chờ duyệt'";
            $params = [];
            if ($sessionId) {
                $sql .= " AND hs.dot_tuyen_sinh_id = ?";
                $params[] = $sessionId;
            }
            $sql .= " ORDER BY hs.created_at ASC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        }

        $sid = $sessionId ?: $current['dot_tuyen_sinh_id'];

        // Try to find the next pending candidate in the SAME session after current timestamp
        $sql = "SELECT hs.so_cccd 
                FROM ho_so_xet_tuyen hs
                WHERE hs.dot_tuyen_sinh_id = ? 
                AND hs.trang_thai = 'Chờ duyệt'
                AND hs.created_at > ?
                ORDER BY hs.created_at ASC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sid, $current['created_at']]);
        $next = $stmt->fetchColumn();

        if ($next) return $next;

        // If nothing after, find the FIRST pending in the same session (looping or fallback)
        $sqlFallback = "SELECT hs.so_cccd FROM ho_so_xet_tuyen hs WHERE hs.dot_tuyen_sinh_id = ? AND hs.trang_thai = 'Chờ duyệt' AND hs.so_cccd != ? ORDER BY hs.created_at ASC LIMIT 1";
        $stmt = $this->db->prepare($sqlFallback);
        $stmt->execute([$sid, $currentCCCD]);
        return $stmt->fetchColumn();
    }

    public function getAdjacentCandidates($currentCCCD) {
        // Get the current candidate's session
        $stmt = $this->db->prepare("SELECT dot_tuyen_sinh_id FROM ho_so_xet_tuyen WHERE so_cccd = ?");
        $stmt->execute([$currentCCCD]);
        $sessionId = $stmt->fetchColumn();

        // Get all candidates in the SAME session for navigation, 
        // regardless of whether they are pending or already reviewed, 
        // to maintain a consistent sequence.
        $sql = "SELECT hs.so_cccd 
                FROM ho_so_xet_tuyen hs
                WHERE hs.dot_tuyen_sinh_id = ?
                ORDER BY hs.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        $allCCCDs = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $currentIndex = array_search($currentCCCD, $allCCCDs);
        
        if ($currentIndex === false) {
            return [
                'prev' => null,
                'next' => !empty($allCCCDs) ? $allCCCDs[0] : null,
                'position' => 0,
                'total' => count($allCCCDs)
            ];
        }

        return [
            'prev' => $currentIndex > 0 ? $allCCCDs[$currentIndex - 1] : null,
            'next' => $currentIndex < count($allCCCDs) - 1 ? $allCCCDs[$currentIndex + 1] : null,
            'position' => $currentIndex + 1,
            'total' => count($allCCCDs)
        ];
    }

    /**
     * Get statistics on how many applications each admin has reviewed.
     */
    public function getReviewerStats($sessionId = null, $year = null) {
        $sql = "SELECT qtv.ho_ten, qtv.ten_dang_nhap, COUNT(hs.id) as review_count
                FROM quan_tri_vien qtv
                JOIN ho_so_xet_tuyen hs ON qtv.id = hs.nguoi_duyet_id
                LEFT JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id
                WHERE 1=1";
        $params = [];

        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        } elseif ($year) {
            $sql .= " AND (dt.nam_tuyen_sinh = ? OR dt.dm_nam_tuyen_sinh_nam = ?)";
            $params[] = $year;
            $params[] = $year;
        }

        $sql .= " GROUP BY qtv.id, qtv.ho_ten, qtv.ten_dang_nhap ORDER BY review_count DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getStats($sessionId = null, $year = null) {
        return $this->model->getStats($sessionId, $year);
    }


    public function getCertifications($cccd) {
        return $this->model->getCertifications($cccd);
    }

    public function saveCertifications($cccd, $certs) {
        return $this->model->saveCertifications($cccd, $certs);
    }

    public function updateDocuments($cccd, $documents) {
        return $this->model->updateDocuments($cccd, $documents);
    }

    public function updateStatusAndNotes($cccd, $status, $notes = null) {
        $data = ['trang_thai' => $status];
        if ($notes !== null) {
            $data['ghi_chu'] = $notes; // Assuming 'ghi_chu' column exists
        }
        
        // Use normalized update if possible, or direct SQL
        $sql = "UPDATE ho_so_xet_tuyen SET trang_thai = :status";
        $params = ['status' => $status, 'cccd' => $cccd];
        
        if ($notes !== null) {
            $sql .= ", ghi_chu = :notes";
            $params['notes'] = $notes;
        }
        
        $sql .= " WHERE so_cccd = :cccd";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            // Sync status to nguyen_vong
            $stmt2 = $this->db->prepare("UPDATE nguyen_vong SET trang_thai = :status WHERE so_cccd = :cccd");
            $stmt2->execute(['status' => $status, 'cccd' => $cccd]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Update status/notes failed: " . $e->getMessage());
            return false;
        }
    }

    public function updatePasswordByEmail($email, $hashedPassword) {
        return $this->model->updatePasswordByEmail($email, $hashedPassword);
    }

    public function verifyEmailAndCCCD($email, $cccd) {
        return $this->model->verifyEmailAndCCCD($email, $cccd);
    }

    public function updatePasswordByCCCD($cccd, $hashedPassword) {
        return $this->model->updatePasswordByCCCD($cccd, $hashedPassword);
    }
    public function getProvinceStats($limit = 10, $startDate, $endDate, $sessionId = null) {
        return $this->model->getProvinceStats($limit, $startDate, $endDate, $sessionId);
    }

    public function getSchoolStats($limit = 10, $startDate, $endDate, $sessionId = null) {
        return $this->model->getSchoolStats($limit, $startDate, $endDate, $sessionId);
    }

    public function getGenderStats($startDate, $endDate, $sessionId = null) {
        return $this->model->getGenderStats($startDate, $endDate, $sessionId);
    }

    public function getAreaStats($startDate, $endDate, $sessionId = null) {
        return $this->model->getAreaStats($startDate, $endDate, $sessionId);
    }

    public function getObjectStats($startDate, $endDate, $sessionId = null) {
        return $this->model->getObjectStats($startDate, $endDate, $sessionId);
    }

    /**
     * Consolidated query: gender + area + object in ONE round-trip
     */
    public function getCombinedDemographicStats($startDate, $endDate, $sessionId = null): array {
        return $this->model->getCombinedDemographicStats($startDate, $endDate, $sessionId);
    }

    public function saveImportedCandidate($data) {
        // 1. Check if exists
        $existing = $this->findByCCCD($data['so_cccd']);
        
        if ($existing) {
            // Update
            // We only update fields provided in $data
            return $this->model->updateFullProfile($data['so_cccd'], $data);
        } else {
            // Create
            return $this->model->create($data);
        }
    }

    /**
     * Tìm thí sinh qua remember_token
     */
    public function findByRememberToken($token) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE remember_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Cập nhật remember_token cho thí sinh
     */
    public function updateRememberToken($id, $token) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET remember_token = ? WHERE id = ?");
        return $stmt->execute([$token, $id]);
    }

    public function getRecentRegistrationStats($sessionId = null) {
        return $this->model->getRecentRegistrationStats($sessionId);
    }

    public function getLatestCandidates($limit = 5, $sessionId = null) {
        return $this->model->getLatestCandidates($limit, $sessionId);
    }
}
