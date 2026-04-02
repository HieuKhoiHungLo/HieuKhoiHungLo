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
        $sql = "INSERT INTO {$this->table} (so_cccd, ho_so_id, thu_tu_nguyen_vong, ma_truong, ma_nganh, ten_nganh, ma_phuong_thuc, ten_phuong_thuc, to_hop_mon) 
                VALUES (:so_cccd, :ho_so_id, :thu_tu_nguyen_vong, :ma_truong, :ma_nganh, :ten_nganh, :ma_phuong_thuc, :ten_phuong_thuc, :to_hop_mon)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function save($cccd, $batchOrAppId, $data) { 
        $this->db->beginTransaction();
        try {
            // 1. Resolve actual recruitment batch ID (dot_tuyen_sinh_id)
            // If batchId is actually ho_so_xet_tuyen.id, look up its batch!
            $dotTuyenSinhId = (int)$batchOrAppId;
            
            // Check if this ID belongs to ho_so_xet_tuyen or dot_tuyen_sinh
            // Typically, dot_tuyen_sinh IDs are small (1, 2, 3), 
            // while application IDs are large (e.g. 100+).
            // But let's be explicitly safe by checking the ho_so_xet_tuyen table.
            $stmtCheck = $this->db->prepare("SELECT dot_tuyen_sinh_id FROM ho_so_xet_tuyen WHERE id = ?");
            $stmtCheck->execute([$batchOrAppId]);
            $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $dotTuyenSinhId = (int)$row['dot_tuyen_sinh_id'];
            }

            // 2. Delete old choices ONLY FOR THIS RECRUITMENT BATCH
            // This prevents candidates from losing aspirations in other sessions.
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
            $stmt->execute([$cccd, $dotTuyenSinhId]);

            // 3. Determine status (sync with application status)
            $stmtStatus = $this->db->prepare("SELECT trang_thai FROM ho_so_xet_tuyen WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
            $stmtStatus->execute([$cccd, $dotTuyenSinhId]);
            $hsStatus = $stmtStatus->fetchColumn();
            $newStatus = ($hsStatus && (strpos($hsStatus, 'Đã duyệt') !== false || $hsStatus === 'DaDuyet')) ? 'DaDuyet' : 'ChoDuyet';

            if (!empty($data)) {
                $insertValues = [];
                $insertParams = [];
                
                foreach ($data as $index => $item) {
                    $insertValues[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    array_push($insertParams,
                        $cccd,
                        $dotTuyenSinhId,
                        $index + 1,
                        $item['ma_nganh'], 
                        $item['ten_nganh'] ?? null,
                        $item['ma_phuong_thuc'] ?? '200', 
                        $item['ten_phuong_thuc'] ?? 'Xét học bạ',
                        $item['to_hop_mon'] ?? 'A00',
                        $newStatus
                    );
                }

                $sql = "INSERT INTO {$this->table} (
                    so_cccd, dot_tuyen_sinh_id, thu_tu_nguyen_vong, ma_nganh, ten_nganh, 
                    ma_phuong_thuc, ten_phuong_thuc, to_hop_mon, trang_thai
                ) VALUES " . implode(', ', $insertValues); 
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($insertParams);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("SAVE NGUYEN VONG ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($cccd, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET trang_thai = ?, updated_at = NOW() WHERE so_cccd = ?");
        return $stmt->execute([$status, $cccd]);
    }

    public function getMajorStats($limit = 10, $startDate = null, $endDate = null, $sessionId = null) {
        $sql = "SELECT nv.ma_nganh, nv.ten_nganh, COUNT(*) as count 
                FROM {$this->table} nv
                JOIN ho_so_xet_tuyen hs ON nv.so_cccd = hs.so_cccd
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

        $sql .= " GROUP BY nv.ma_nganh, nv.ten_nganh 
                  ORDER BY count DESC 
                  LIMIT " . intval($limit);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetailedMajorStats($startDate = null, $endDate = null, $sessionId = null) {
        // Query to get detailed stats per major (Total, NV1, NV2, Others)
        // We join dm_nganh to ensure we get all majors and their quota (chỉ tiêu)
        
        $params = [];
        $hsFilter = "";
        
        if ($startDate && $endDate) {
            $hsFilter .= " AND hs.created_at >= ? AND hs.created_at <= ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }

        if ($sessionId) {
             $hsFilter .= " AND hs.dot_tuyen_sinh_id = ?";
             $params[] = $sessionId;
        }

        $sql = "SELECT 
                    n.ma_nganh, 
                    n.ten_nganh, 
                    n.chi_tieu,
                    COUNT(hs.so_cccd) as tong_nv,
                    SUM(CASE WHEN nv.thu_tu_nguyen_vong = 1 THEN 1 ELSE 0 END) as nv1,
                    SUM(CASE WHEN nv.thu_tu_nguyen_vong = 2 THEN 1 ELSE 0 END) as nv2,
                    SUM(CASE WHEN nv.thu_tu_nguyen_vong > 2 THEN 1 ELSE 0 END) as nv_con_lai
                FROM dm_nganh n
                LEFT JOIN nguyen_vong nv ON n.ma_nganh = nv.ma_nganh
                LEFT JOIN ho_so_xet_tuyen hs ON nv.so_cccd = hs.so_cccd $hsFilter
                GROUP BY n.ma_nganh, n.ten_nganh, n.chi_tieu
                ORDER BY n.ma_nganh ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
