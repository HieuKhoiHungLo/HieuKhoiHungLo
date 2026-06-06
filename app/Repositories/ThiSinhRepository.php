<?php

namespace App\Repositories;

use App\Models\ThiSinh;
use App\Core\Database;
use PDO;

class ThiSinhRepository
{
    protected $db;
    protected $model;
    protected $table = 'thi_sinh';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->model = new ThiSinh();
    }

    public function getDb()
    {
        return $this->db;
    }

    public function findByCCCD($cccd)
    {
        return $this->model->findByCCCD($cccd);
    }

    public function findManyByCCCD(array $cccds)
    {
        if (empty($cccds)) return [];
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd IN ($placeholders)");
        $stmt->execute($cccds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Chuẩn hóa toàn bộ họ tên thí sinh trong hệ thống
     */
    public function bulkNormalizeNames()
    {
        $stmt = $this->db->query("SELECT so_cccd, ho_va_ten FROM {$this->table}");
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = 0;
        foreach ($candidates as $c) {
            $normalized = normalize_name($c['ho_va_ten']);
            if ($normalized !== $c['ho_va_ten']) {
                $upd = $this->db->prepare("UPDATE {$this->table} SET ho_va_ten = ? WHERE so_cccd = ?");
                $upd->execute([$normalized, $c['so_cccd']]);
                $count++;
            }
        }
        return $count;
    }

    public function getEmailsByIds(array $ids)
    {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT so_cccd, ho_va_ten, email FROM {$this->table} WHERE so_cccd IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetail($cccd)
    {
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
                LEFT JOIN dm_truong_thpt s ON t.ma_truong_lop_12 = s.ma_truong AND s.is_active = TRUE
                LEFT JOIN dm_doi_tuong dt ON t.doi_tuong_uu_tien = dt.ma_dt
                LEFT JOIN dm_khu_vuc kv ON t.khu_vuc_uu_tien = kv.ma_kv
                LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd
                WHERE t.so_cccd = ?
                ORDER BY hs.created_at DESC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cccd]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getReviewBundle($cccd)
    {
        $sql = "SELECT 
            t.*, 
            p1.ten_tinh as ten_tinh_hk,
            p2.ten_tinh as ten_tinh_tt,
            x.ten_xa as ten_xa_tt,
            s.ten_truong as ten_truong_lop_12,
            dt.ten_dt as ten_doi_tuong_ut,
            kv.ten_kv as ten_khu_vuc_ut,
            hs.trang_thai, hs.ghi_chu, hs.yeu_cau_chinh_sua, hs.id as application_id,
            COALESCE((SELECT json_agg(row_to_json(hb.*) ORDER BY hb.id) FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd), '[]'::json) as _academic_json,
            COALESCE((SELECT json_agg(row_to_json(nv.*) ORDER BY nv.thu_tu_nguyen_vong) FROM nguyen_vong nv WHERE nv.so_cccd = t.so_cccd), '[]'::json) as _choices_json,
            COALESCE((SELECT json_agg(row_to_json(cc.*)) FROM chung_chi_thi_sinh cc WHERE cc.so_cccd = t.so_cccd), '[]'::json) as _certs_json,
            (SELECT row_to_json(dth.*) FROM diem_thi_thpt dth WHERE dth.so_cccd = t.so_cccd LIMIT 1) as _diemthi_json
            FROM {$this->table} t
            LEFT JOIN dm_tinh p1 ON t.ma_tinh_ho_khau = p1.ma_tinh
            LEFT JOIN dm_tinh p2 ON t.ma_tinh_thuong_tru = p2.ma_tinh
            LEFT JOIN dm_xa x ON t.ma_xa_thuong_tru = x.ma_xa
            LEFT JOIN dm_truong_thpt s ON t.ma_truong_lop_12 = s.ma_truong AND s.is_active = TRUE
            LEFT JOIN dm_doi_tuong dt ON t.doi_tuong_uu_tien = dt.ma_dt
            LEFT JOIN dm_khu_vuc kv ON t.khu_vuc_uu_tien = kv.ma_kv
            LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd
            WHERE t.so_cccd = ?
            ORDER BY hs.created_at DESC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cccd]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

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

    public function findByEmail($email)
    {
        return $this->model->findByEmail($email);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function updateFullProfile($cccd, array $data)
    {
        return $this->model->updateFullProfile($cccd, $data);
    }

    public function update($cccd, array $data)
    {
        return $this->updateFullProfile($cccd, $data);
    }

    public function saveDiemThiTHPT($cccd, array $data)
    {
        $model = new \App\Models\DiemThiTHPT();
        return $model->save($cccd, $data);
    }

    public function updateHocBaStatus($cccd, $status)
    {
        return $this->model->updateHocBaStatus($cccd, $status);
    }

    public function updateCertStatus($cccd, $hasCert)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET co_chung_chi_qt = ? WHERE so_cccd = ?");
        return $stmt->execute([$hasCert ? '1' : '0', $cccd]);
    }

    public function delete($cccd)
    {
        return $this->model->delete($cccd);
    }

    public function restore($cccd)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = NULL WHERE so_cccd = ?");
        return $stmt->execute([$cccd]);
    }

    public function forceDelete($cccd)
    {
        try {
            $this->db->beginTransaction();

            $dependentTables = [
                'nguyen_vong' => 'so_cccd',
                'ho_so_xet_tuyen' => 'so_cccd',
                'chung_chi_thi_sinh' => 'so_cccd',
                'ket_qua_hoc_tap' => 'so_cccd',
                'diem_chi_tiet' => 'so_cccd',
                'diem_thi_thpt' => 'so_cccd',
                'diem_nang_khieu' => 'so_cccd',
                'notification_reads' => 'user_cccd'
            ];

            foreach ($dependentTables as $table => $column) {
                $stmt = $this->db->prepare("DELETE FROM $table WHERE $column = ?");
                $stmt->execute([$cccd]);
            }

            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE so_cccd = ?");
            $result = $stmt->execute([$cccd]);

            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in forceDelete for CCCD $cccd: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Thay đổi số CCCD an toàn (Cập nhật đồng bộ tất cả các bảng liên quan)
     */
    public function changeCCCD($oldCccd, $newCccd)
    {
        if (empty($oldCccd) || empty($newCccd) || $oldCccd === $newCccd) return true;

        $hasActiveTransaction = $this->db->inTransaction();
        try {
            if (!$hasActiveTransaction) {
                $this->db->beginTransaction();
            }

            // 1. Kiểm tra CCCD mới đã tồn tại chưa
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE so_cccd = ?");
            $stmt->execute([$newCccd]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception("Số CCCD mới ($newCccd) đã tồn tại trên hệ thống. Không thể đổi.");
            }

            // 2. Danh sách các bảng phụ thuộc cần cập nhật
            $dependentTables = [
                'nguyen_vong' => 'so_cccd',
                'ho_so_xet_tuyen' => 'so_cccd',
                'chung_chi_thi_sinh' => 'so_cccd',
                'ket_qua_hoc_tap' => 'so_cccd',
                'diem_chi_tiet' => 'so_cccd',
                'diem_thi_thpt' => 'so_cccd',
                'diem_nang_khieu' => 'so_cccd',
                'notification_reads' => 'user_cccd'
            ];

            // Bước 3.1: Copy bản ghi chính sang CCCD mới (Shadow copy)
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (
                so_cccd, ho_va_ten, mat_khau, email, dien_thoai, ngay_sinh, gioi_tinh, dan_toc, 
                ma_tinh_ho_khau, ma_tinh_thuong_tru, ma_xa_thuong_tru, dia_chi_chi_tiet,
                ma_truong_lop_12, ma_tinh_lop_12, nam_tot_nghiep, khu_vuc_uu_tien, doi_tuong_uu_tien,
                is_custom_kv, is_custom_dt,
                anh_dai_dien, anh_cccd_truoc, anh_cccd_sau, file_minh_chung_kv, file_minh_chung_dt,
                loai_chung_chi, diem_chung_chi, file_minh_chung_cc,
                da_du_6_ky, co_chung_chi_qt, remember_token, nguon_du_lieu, ghi_chu, ngay_tao
            ) SELECT 
                ?, ho_va_ten, mat_khau, email, dien_thoai, ngay_sinh, gioi_tinh, dan_toc, 
                ma_tinh_ho_khau, ma_tinh_thuong_tru, ma_xa_thuong_tru, dia_chi_chi_tiet,
                ma_truong_lop_12, ma_tinh_lop_12, nam_tot_nghiep, khu_vuc_uu_tien, doi_tuong_uu_tien,
                is_custom_kv, is_custom_dt,
                anh_dai_dien, anh_cccd_truoc, anh_cccd_sau, file_minh_chung_kv, file_minh_chung_dt,
                loai_chung_chi, diem_chung_chi, file_minh_chung_cc,
                da_du_6_ky, co_chung_chi_qt, remember_token, nguon_du_lieu, ghi_chu, ngay_tao
            FROM {$this->table} WHERE so_cccd = ?");
            $stmt->execute([$newCccd, $oldCccd]);

            // Bước 3.2: Cập nhật các bảng phụ trỏ về CCCD mới
            foreach ($dependentTables as $table => $column) {
                $stmt = $this->db->prepare("UPDATE $table SET $column = ? WHERE $column = ?");
                $stmt->execute([$newCccd, $oldCccd]);
            }

            // Bước 3.3: Xóa bản ghi cũ
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE so_cccd = ?");
            $stmt->execute([$oldCccd]);

            if (!$hasActiveTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (\Exception $e) {
            if (!$hasActiveTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Lỗi khi đổi CCCD từ $oldCccd sang $newCccd: " . $e->getMessage());
            throw $e;
        }
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE deleted_at IS NULL");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFiltered($search = '', $status = '', $hocBaStatus = '', $limit = 20, $offset = 0, $sessionId = null, $onlyEditRequests = false, $year = null, $sort = 'ngay_tao', $dir = 'DESC', $excludeTrash = true, $extraFilters = [], $applicationStatus = 'all')
    {
        return $this->model->getFiltered($search, $status, $hocBaStatus, $limit, $offset, $sessionId, $onlyEditRequests, $year, $sort, $dir, $excludeTrash, $extraFilters, $applicationStatus);
    }

    public function countFiltered($search = '', $status = '', $hocBaStatus = '', $sessionId = null, $onlyEditRequests = false, $year = null, $excludeTrash = true, $extraFilters = [], $applicationStatus = 'all')
    {
        return $this->model->countFiltered($search, $status, $hocBaStatus, $sessionId, $onlyEditRequests, $year, $excludeTrash, $extraFilters, $applicationStatus);
    }

    public function bulkUpdateStatus($cccds, $status, $reviewerId = null)
    {
        if (empty($cccds)) return false;
        if (!is_array($cccds)) $cccds = [$cccds];

        // Safety check: Prevent URL or long invalid strings from being saved as status
        if (strpos($status, 'http') !== false || strpos($status, '/TS/') !== false || strlen($status) > 50) {
            $status = 'Chờ duyệt';
        }

        $placeholders = implode(',', array_fill(0, count($cccds), '?'));

        try {
            $sql = "UPDATE nguyen_vong SET trang_thai = ? WHERE so_cccd IN ($placeholders)";
            $params = array_merge([$status], $cccds);
            $stmt = $this->db->prepare($sql);
            $result1 = $stmt->execute($params);

            $ghiChuValue = ($status === 'Đã duyệt' ? 'Đã duyệt.' : null);
            
            $sql2 = "UPDATE ho_so_xet_tuyen SET trang_thai = ?, ghi_chu = ?, yeu_cau_chinh_sua = FALSE";
            $params2 = [$status, $ghiChuValue];
            if ($status === 'Chờ duyệt') {
                $sql2 .= ", nguoi_duyet_id = NULL";
            } elseif ($reviewerId !== null) {
                $sql2 .= ", nguoi_duyet_id = ?";
                $params2[] = $reviewerId;
            }
            $sql2 .= " WHERE so_cccd IN ($placeholders)";
            $params2 = array_merge($params2, $cccds);

            $stmt2 = $this->db->prepare($sql2);
            $result2 = $stmt2->execute($params2);

            return $result1 || $result2;
        } catch (\PDOException $e) {
            error_log("bulkUpdateStatus PDO Error: " . $e->getMessage());
            return false;
        }
    }

    public function bulkTransferSession($cccds, $sessionId)
    {
        if (empty($cccds)) return false;
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));

        try {
            $this->db->beginTransaction();

            $sqlDelete = "DELETE FROM ho_so_xet_tuyen WHERE so_cccd IN ($placeholders) AND dot_tuyen_sinh_id = ?";
            $paramsDelete = array_merge($cccds, [$sessionId]);
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute($paramsDelete);

            $sqlUpdate = "UPDATE ho_so_xet_tuyen SET dot_tuyen_sinh_id = ? WHERE so_cccd IN ($placeholders)";
            $paramsUpdate = array_merge([$sessionId], $cccds);
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute($paramsUpdate);

            // Update nguyen_vong table as well
            $sqlUpdateNV = "UPDATE nguyen_vong SET dot_tuyen_sinh_id = ? WHERE so_cccd IN ($placeholders)";
            $stmtUpdateNV = $this->db->prepare($sqlUpdateNV);
            $stmtUpdateNV->execute(array_merge([$sessionId], $cccds));

            // Update ngoai_le_xet_tuyen table if exists
            try {
                $sqlUpdateEx = "UPDATE ngoai_le_xet_tuyen SET dot_tuyen_sinh_id = ? WHERE so_cccd IN ($placeholders)";
                $stmtUpdateEx = $this->db->prepare($sqlUpdateEx);
                $stmtUpdateEx->execute(array_merge([$sessionId], $cccds));
            } catch (\Exception $e) {
                // Ignore if table does not exist
            }

            // Update diem_nang_khieu table if exists
            try {
                $sqlUpdateNK = "UPDATE diem_nang_khieu SET dot_tuyen_sinh_id = ? WHERE so_cccd IN ($placeholders)";
                $stmtUpdateNK = $this->db->prepare($sqlUpdateNK);
                $stmtUpdateNK->execute(array_merge([$sessionId], $cccds));
            } catch (\Exception $e) {
                // Ignore if table does not exist
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Transfer session failed: " . $e->getMessage());
            return false;
        }
    }

    public function bulkDelete($cccds)
    {
        if (empty($cccds)) return false;
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));

        $sql = "UPDATE thi_sinh SET deleted_at = NOW() WHERE so_cccd IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($cccds);
    }

    public function bulkRestore($cccds)
    {
        if (empty($cccds)) return false;
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));

        $sql = "UPDATE thi_sinh SET deleted_at = NULL WHERE so_cccd IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($cccds);
    }

    public function bulkForceDelete($cccds)
    {
        if (empty($cccds)) return false;
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));

        try {
            $this->db->beginTransaction();

            $tables = ['chung_chi_thi_sinh', 'nguyen_vong', 'ho_so_xet_tuyen', 'diem_thi_thpt', 'ket_qua_hoc_tap'];
            foreach ($tables as $tb) {
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

    public function emptyTrash()
    {
        try {
            $this->db->beginTransaction();

            $dependentTables = [
                'nguyen_vong' => 'so_cccd',
                'ho_so_xet_tuyen' => 'so_cccd',
                'chung_chi_thi_sinh' => 'so_cccd',
                'ket_qua_hoc_tap' => 'so_cccd',
                'diem_chi_tiet' => 'so_cccd',
                'diem_thi_thpt' => 'so_cccd',
                'diem_nang_khieu' => 'so_cccd',
                'notification_reads' => 'user_cccd'
            ];

            foreach ($dependentTables as $table => $column) {
                $sql = "DELETE FROM $table WHERE $column IN (SELECT so_cccd FROM {$this->table} WHERE deleted_at IS NOT NULL)";
                $this->db->exec($sql);
            }

            $sql = "DELETE FROM {$this->table} WHERE deleted_at IS NOT NULL";
            $result = $this->db->exec($sql);

            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in emptyTrash: " . $e->getMessage());
            throw $e;
        }
    }

    public function hasEditRequest($cccd)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE so_cccd = ? AND yeu_cau_chinh_sua = TRUE");
        $stmt->execute([$cccd]);
        return $stmt->fetchColumn() > 0;
    }

    public function approveEditRequest($cccd)
    {
        $stmt = $this->db->prepare("UPDATE ho_so_xet_tuyen SET yeu_cau_chinh_sua = FALSE, trang_thai = 'Chờ duyệt', nguoi_duyet_id = NULL WHERE so_cccd = ? AND yeu_cau_chinh_sua = TRUE");
        return $stmt->execute([$cccd]);
    }

    public function requestEditPermission($applicationId)
    {
        $stmt = $this->db->prepare("UPDATE ho_so_xet_tuyen SET yeu_cau_chinh_sua = TRUE, trang_thai = 'Yêu cầu sửa', ghi_chu = CONCAT(COALESCE(ghi_chu, ''), '\n[Hệ thống]: Thí sinh chủ động đề xuất chỉnh sửa.'), updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$applicationId]);
    }

    public function updateApplicationStatus($cccd, $status, $note = null, $reviewerId = null)
    {
        // Safety check: Prevent URL or long invalid strings from being saved as status
        if (strpos($status, 'http') !== false || strpos($status, '/TS/') !== false || strlen($status) > 50) {
            $status = 'Chờ duyệt';
        }

        $sql = "UPDATE ho_so_xet_tuyen SET trang_thai = ?, yeu_cau_chinh_sua = FALSE";
        $params = [$status];

        if ($note !== null) {
            $sql .= ", ghi_chu = ?";
            $params[] = $note;
        }

        if ($status === 'Chờ duyệt') {
            $sql .= ", nguoi_duyet_id = NULL";
        } elseif ($reviewerId !== null) {
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

    public function getNextPendingCandidate($currentCCCD, $sessionId = null, $year = null)
    {
        $stmt = $this->db->prepare("SELECT dot_tuyen_sinh_id, created_at, id FROM ho_so_xet_tuyen WHERE so_cccd = ?");
        $stmt->execute([$currentCCCD]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            $sql = "SELECT hs.so_cccd FROM ho_so_xet_tuyen hs WHERE hs.trang_thai = 'Chờ duyệt'";
            $params = [];
            if ($sessionId) {
                $sql .= " AND hs.dot_tuyen_sinh_id = ?";
                $params[] = $sessionId;
            }
            $sql .= " ORDER BY hs.created_at ASC, hs.id ASC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        }

        $sid = $sessionId ?: $current['dot_tuyen_sinh_id'];

        // Cursor pagination style query to handle exact matching timestamps robustly
        $sql = "SELECT hs.so_cccd 
                FROM ho_so_xet_tuyen hs
                WHERE hs.dot_tuyen_sinh_id = ? 
                AND hs.trang_thai = 'Chờ duyệt'
                AND (hs.created_at > ? OR (hs.created_at = ? AND hs.id > ?))
                ORDER BY hs.created_at ASC, hs.id ASC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sid, $current['created_at'], $current['created_at'], $current['id']]);
        $next = $stmt->fetchColumn();

        if ($next) return $next;

        $sqlFallback = "SELECT hs.so_cccd FROM ho_so_xet_tuyen hs WHERE hs.dot_tuyen_sinh_id = ? AND hs.trang_thai = 'Chờ duyệt' AND hs.so_cccd != ? ORDER BY hs.created_at ASC, hs.id ASC LIMIT 1";
        $stmt = $this->db->prepare($sqlFallback);
        $stmt->execute([$sid, $currentCCCD]);
        return $stmt->fetchColumn();
    }

    public function getAdjacentCandidates($currentCCCD)
    {
        // Combined query: fetch session ID, prev/next CCCD, current position, and total count in one go
        $sql = "WITH session_info AS (
            SELECT dot_tuyen_sinh_id FROM ho_so_xet_tuyen WHERE so_cccd = ?
        ),
        ordered AS (
            SELECT 
                so_cccd,
                LAG(so_cccd) OVER (ORDER BY created_at ASC) as prev_cccd,
                LEAD(so_cccd) OVER (ORDER BY created_at ASC) as next_cccd,
                ROW_NUMBER() OVER (ORDER BY created_at ASC) as pos,
                COUNT(*) OVER () as total_count
            FROM ho_so_xet_tuyen
            WHERE dot_tuyen_sinh_id = (SELECT dot_tuyen_sinh_id FROM session_info)
        )
        SELECT prev_cccd, next_cccd, pos, total_count FROM ordered WHERE so_cccd = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$currentCCCD, $currentCCCD]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['prev' => null, 'next' => null, 'position' => 0, 'total' => 0];
        }

        return [
            'prev' => $row['prev_cccd'],
            'next' => $row['next_cccd'],
            'position' => (int) $row['pos'],
            'total' => (int) $row['total_count']
        ];
    }

    public function getReviewerStats($sessionId = null, $year = null)
    {
        $sql = "SELECT qtv.ho_ten, qtv.ten_dang_nhap, 
                       COUNT(hs.id) FILTER (WHERE hs.trang_thai ILIKE 'Đã duyệt%' OR hs.trang_thai ILIKE 'approved%' OR hs.trang_thai ILIKE 'DaDuyet%') as approved_count,
                       COUNT(hs.id) FILTER (WHERE hs.trang_thai ILIKE 'Yêu cầu sửa%' OR hs.trang_thai ILIKE 'edit%' OR hs.trang_thai ILIKE 'YeuCauSua%') as edit_count
                FROM quan_tri_vien qtv
                JOIN ho_so_xet_tuyen hs ON qtv.id = hs.nguoi_duyet_id
                LEFT JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id
                WHERE hs.deleted_at IS NULL 
                AND (
                    hs.trang_thai ILIKE 'Đã duyệt%' OR hs.trang_thai ILIKE 'approved%' OR hs.trang_thai ILIKE 'DaDuyet%'
                    OR hs.trang_thai ILIKE 'Yêu cầu sửa%' OR hs.trang_thai ILIKE 'edit%' OR hs.trang_thai ILIKE 'YeuCauSua%'
                )";
        $params = [];

        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        } elseif ($year) {
            $sql .= " AND (dt.nam_tuyen_sinh = ? OR dt.dm_nam_tuyen_sinh_nam = ?)";
            $params[] = $year;
            $params[] = $year;
        }

        $sql .= " GROUP BY qtv.id, qtv.ho_ten, qtv.ten_dang_nhap ORDER BY approved_count DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats($sessionId = null, $year = null, $startDate = null, $endDate = null)
    {
        return $this->model->getStats($sessionId, $year, $startDate, $endDate);
    }

    public function getCertifications($cccd)
    {
        return $this->model->getCertifications($cccd);
    }

    public function saveCertifications($cccd, $certs)
    {
        return $this->model->saveCertifications($cccd, $certs);
    }

    public function updateDocuments($cccd, $documents)
    {
        return $this->model->updateDocuments($cccd, $documents);
    }

    public function updateStatusAndNotes($cccd, $status, $notes = null)
    {
        $sql = "UPDATE ho_so_xet_tuyen SET trang_thai = :status";
        $params = ['status' => $status, 'cccd' => $cccd];

        if ($status === 'Chờ duyệt') {
            $sql .= ", nguoi_duyet_id = NULL";
        }

        if ($notes !== null) {
            $sql .= ", ghi_chu = :notes";
            $params['notes'] = $notes;
        }

        $sql .= " WHERE so_cccd = :cccd";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $stmt2 = $this->db->prepare("UPDATE nguyen_vong SET trang_thai = :status WHERE so_cccd = :cccd");
            $stmt2->execute(['status' => $status, 'cccd' => $cccd]);

            return true;
        } catch (\Exception $e) {
            error_log("Update status/notes failed: " . $e->getMessage());
            return false;
        }
    }

    public function updatePasswordByEmail($email, $hashedPassword)
    {
        return $this->model->updatePasswordByEmail($email, $hashedPassword);
    }

    public function verifyEmailAndCCCD($email, $cccd)
    {
        return $this->model->verifyEmailAndCCCD($email, $cccd);
    }

    public function updatePasswordByCCCD($cccd, $hashedPassword)
    {
        return $this->model->updatePasswordByCCCD($cccd, $hashedPassword);
    }

    public function getProvinceStats($limit = 10, $startDate = null, $endDate = null, $sessionId = null)
    {
        return $this->model->getProvinceStats($limit, $startDate, $endDate, $sessionId);
    }

    public function getSchoolStats($limit = 10, $startDate = null, $endDate = null, $sessionId = null)
    {
        return $this->model->getSchoolStats($limit, $startDate, $endDate, $sessionId);
    }

    public function getGenderStats($startDate = null, $endDate = null, $sessionId = null)
    {
        return $this->model->getGenderStats($startDate, $endDate, $sessionId);
    }

    public function getAreaStats($startDate = null, $endDate = null, $sessionId = null)
    {
        return $this->model->getAreaStats($startDate, $endDate, $sessionId);
    }

    public function getObjectStats($startDate = null, $endDate = null, $sessionId = null)
    {
        return $this->model->getObjectStats($startDate, $endDate, $sessionId);
    }

    public function getCombinedDemographicStats($startDate = null, $endDate = null, $sessionId = null): array
    {
        return $this->model->getCombinedDemographicStats($startDate, $endDate, $sessionId);
    }

    public function saveImportedCandidate($data)
    {
        $existing = $this->findByCCCD($data['so_cccd']);
        if ($existing) {
            return $this->model->updateFullProfile($data['so_cccd'], $data);
        } else {
            return $this->model->create($data);
        }
    }

    public function findByRememberToken($token)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE remember_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public function updateRememberToken($id, $token)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET remember_token = ? WHERE id = ?");
        return $stmt->execute([$token, $id]);
    }

    public function getRecentRegistrationStats($sessionId = null)
    {
        return $this->model->getRecentRegistrationStats($sessionId);
    }

    public function getLatestCandidates($limit = 5, $sessionId = null)
    {
        return $this->model->getLatestCandidates($limit, $sessionId);
    }

    public function upsertBatch(array $candidatesData)
    {
        if (empty($candidatesData)) return 0;

        $cols = ['so_cccd', 'ho_va_ten', 'ngay_sinh', 'gioi_tinh', 'doi_tuong_uu_tien', 'khu_vuc_uu_tien', 'nam_tot_nghiep', 'ma_tinh_ho_khau', 'ma_tinh_thuong_tru', 'ma_xa_thuong_tru', 'ma_truong_lop_12', 'email', 'mat_khau', 'nguon_du_lieu'];
        
        $placeholders = [];
        $values = [];
        
        foreach ($candidatesData as $data) {
            $rowPlaceholders = [];
            foreach ($cols as $col) {
                $rowPlaceholders[] = '?';
                $values[] = $data[$col] ?? null;
            }
            $placeholders[] = '(' . implode(',', $rowPlaceholders) . ')';
        }
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $cols) . ") VALUES " . implode(', ', $placeholders);
        $sql .= " ON CONFLICT (so_cccd) DO UPDATE SET ";
        
        $updateCols = [];
        foreach ($cols as $col) {
            if ($col !== 'so_cccd' && $col !== 'mat_khau' && $col !== 'email') { 
                // Do not blindly overwrite password or email if they exist, but update other profile fields
                $updateCols[] = "$col = EXCLUDED.$col";
            }
        }
        $sql .= implode(', ', $updateCols);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return count($candidatesData);
    }
}
