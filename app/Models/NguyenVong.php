<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class NguyenVong extends Model {
    protected $table = 'nguyen_vong';

    public function getByCCCD($cccd) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd = ? ORDER BY thu_tu_nguyen_vong ASC");
        $stmt->execute([$cccd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByHoSoId($hoSoId) {
        $sql = "SELECT * FROM {$this->table} WHERE ho_so_id = :ho_so_id ORDER BY thu_tu_nguyen_vong ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['ho_so_id' => $hoSoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add($data) {
        // Đảm bảo tương thích ngược: nếu thiếu thu_tu_nv_bo thì mặc định là null
        if (!isset($data['thu_tu_nv_bo'])) {
            $data['thu_tu_nv_bo'] = null;
        }
        $sql = "INSERT INTO {$this->table} (so_cccd, ho_so_id, thu_tu_nguyen_vong, thu_tu_nv_bo, ma_truong, ma_nganh, ten_nganh, ma_phuong_thuc, ten_phuong_thuc, to_hop_mon) 
                VALUES (:so_cccd, :ho_so_id, :thu_tu_nguyen_vong, :thu_tu_nv_bo, :ma_truong, :ma_nganh, :ten_nganh, :ma_phuong_thuc, :ten_phuong_thuc, :to_hop_mon)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function save($cccd, $batchOrAppId, $data) { 
        try {
            // 1. Resolve actual recruitment batch ID (dot_tuyen_sinh_id)
            $dotTuyenSinhId = (int)$batchOrAppId;
            
            // Check if this ID belongs to ho_so_xet_tuyen or dot_tuyen_sinh
            $stmtCheck = $this->db->prepare("SELECT dot_tuyen_sinh_id, trang_thai FROM ho_so_xet_tuyen WHERE id = ?");
            $stmtCheck->execute([$batchOrAppId]);
            $rowHS = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($rowHS) {
                $dotTuyenSinhId = (int)$rowHS['dot_tuyen_sinh_id'];
                $hsStatus = $rowHS['trang_thai'];
            } else {
                // Pre-fetch status if we only have batchId
                $stmtStatus = $this->db->prepare("SELECT trang_thai FROM ho_so_xet_tuyen WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
                $stmtStatus->execute([$cccd, $dotTuyenSinhId]);
                $hsStatus = $stmtStatus->fetchColumn();
            }

            // 2. Delete old choices ONLY FOR THIS RECRUITMENT BATCH
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
            $stmt->execute([$cccd, $dotTuyenSinhId]);

            // 3. Determine status
            $newStatus = ($hsStatus && (stripos($hsStatus, 'Đã duyệt') !== false || stripos($hsStatus, 'approved') !== false || $hsStatus === 'DaDuyet')) 
                ? \App\Core\UserStatus::APPROVED 
                : \App\Core\UserStatus::PENDING;

            if (!empty($data)) {
                $insertValues = [];
                $insertParams = [];
                
                foreach ($data as $index => $item) {
                    $insertValues[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    array_push($insertParams,
                        $cccd,
                        $dotTuyenSinhId,
                        $index + 1,
                        null, 
                        $item['ma_nganh'], 
                        $item['ten_nganh'] ?? null,
                        $item['ma_phuong_thuc'] ?? '200', 
                        $item['ten_phuong_thuc'] ?? 'Xét học bạ',
                        $item['to_hop_mon'] ?? 'A00',
                        $newStatus
                    );
                }

                $sql = "INSERT INTO {$this->table} (
                    so_cccd, dot_tuyen_sinh_id, thu_tu_nguyen_vong, thu_tu_nv_bo, ma_nganh, ten_nganh, 
                    ma_phuong_thuc, ten_phuong_thuc, to_hop_mon, trang_thai
                ) VALUES " . implode(', ', $insertValues); 
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($insertParams);
            }

            return true;
        } catch (\Exception $e) {
            error_log("SAVE NGUYEN VONG ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($cccd, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET trang_thai = ?, updated_at = NOW() WHERE so_cccd = ?");
        return $stmt->execute([$status, $cccd]);
    }

    public function getMajorStats($limit = 10, $startDate = null, $endDate = null, $sessionId = null) {
        $sql = "SELECT nv.ma_nganh, nv.ten_nganh, COUNT(DISTINCT hs.so_cccd) as count 
                FROM {$this->table} nv
                JOIN ho_so_xet_tuyen hs ON nv.so_cccd = hs.so_cccd
                WHERE hs.deleted_at IS NULL";
        
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

        $sql .= " GROUP BY nv.ma_nganh, nv.ten_nganh 
                  ORDER BY count DESC 
                  LIMIT " . intval($limit);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetailedMajorStats($startDate = null, $endDate = null, $sessionId = null) {
        try {
            $params = [];
            $hsFilter = " AND hs.deleted_at IS NULL";
            
            if ($startDate && $endDate) {
                $hsFilter .= " AND hs.created_at >= ? AND hs.created_at <= ?";
                $params[] = $startDate . ' 00:00:00';
                $params[] = $endDate . ' 23:59:59';
            }

            if ($sessionId) {
                 $hsFilter .= " AND hs.dot_tuyen_sinh_id = ?";
                 $params[] = $sessionId;
            }

            // Optimized Query using Subquery for Aggregation
            $sql = "SELECT 
                        n.ma_nganh, 
                        n.ten_nganh, 
                        n.chi_tieu,
                        COALESCE(stats.tong_nv, 0) as tong_nv,
                        COALESCE(stats.nv1, 0) as nv1,
                        COALESCE(stats.nv2, 0) as nv2,
                        COALESCE(stats.nv_con_lai, 0) as nv_con_lai
                    FROM dm_nganh n
                    LEFT JOIN (
                        SELECT 
                            nv.ma_nganh,
                            COUNT(DISTINCT hs.so_cccd) as tong_nv,
                            SUM(CASE WHEN nv.thu_tu_nguyen_vong = 1 THEN 1 ELSE 0 END) as nv1,
                            SUM(CASE WHEN nv.thu_tu_nguyen_vong = 2 THEN 1 ELSE 0 END) as nv2,
                            SUM(CASE WHEN nv.thu_tu_nguyen_vong > 2 THEN 1 ELSE 0 END) as nv_con_lai
                        FROM nguyen_vong nv
                        JOIN ho_so_xet_tuyen hs ON nv.so_cccd = hs.so_cccd AND nv.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id
                        WHERE 1=1 $hsFilter
                        GROUP BY nv.ma_nganh
                    ) stats ON n.ma_nganh = stats.ma_nganh
                    ORDER BY n.ma_nganh ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log("DETAILED MAJOR STATS ERROR: " . $e->getMessage());
            return [];
        }
    }
}
