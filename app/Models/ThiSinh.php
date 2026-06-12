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
        $startTime = microtime(true);
        \App\Core\Logger::log("ThiSinh::getFiltered start. Status: $applicationStatus, Session: $sessionId");
        $params = [];
        
        $transcriptStatusSql = "";
        if (isset($extraFilters['transcript'])) {
            $transcriptStatusSql = ", (SELECT 
                CASE 
                    WHEN COUNT(*) = 0 THEN 'not_entered'
                    WHEN COUNT(*) FILTER (WHERE lop = 12) = 0 AND COUNT(*) FILTER (WHERE lop IN (10, 11)) > 0 THEN 'missing_12'
                    WHEN COUNT(DISTINCT lop) >= 3 THEN 'full'
                    ELSE 'partial'
                END
             FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd) as transcript_status";
        }

        $baseSelect = "t.*, t.ghi_chu as base_ghi_chu, p.ten_tinh as province_name, s.ten_truong as school_name, qtv_base.ho_ten as reviewer_name,
                         (SELECT dth.diem_xet_tot_nghiep FROM diem_thi_thpt dth WHERE dth.so_cccd = t.so_cccd " . ($year ? " AND dth.nam_thi = " . (int)$year : "") . " LIMIT 1) as graduation_score,
                         (SELECT hb.diem_tb_ca_nam FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 LIMIT 1) as tb_chung_12,
                         (SELECT hb.hoc_luc_ca_nam FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 LIMIT 1) as hoc_luc_12,
                         (SELECT hb.hanh_kiem_ca_nam FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 LIMIT 1) as hanh_kiem_12
                         $transcriptStatusSql";
        $baseJoins = " LEFT JOIN dm_tinh p ON t.ma_tinh_ho_khau = p.ma_tinh
                       LEFT JOIN dm_truong_thpt s ON t.ma_truong_lop_12 = s.ma_truong AND s.is_active = TRUE
                       LEFT JOIN ho_so_xet_tuyen hs_base ON t.so_cccd = hs_base.so_cccd " . ($sessionId ? " AND hs_base.dot_tuyen_sinh_id = " . (int)$sessionId : "") . "
                       LEFT JOIN quan_tri_vien qtv_base ON hs_base.nguoi_duyet_id = qtv_base.id";

        // Optimize for 'submitted' mode by joining ho_so_xet_tuyen early
        if ($applicationStatus === 'submitted') {
            $sql = "SELECT $baseSelect, 
                    (SELECT COALESCE(dmn_sub.ten_nganh, nv_sub.ten_nganh) 
                     FROM nguyen_vong nv_sub 
                     LEFT JOIN dm_nganh dmn_sub ON nv_sub.ma_nganh = dmn_sub.ma_nganh
                     WHERE (nv_sub.ho_so_id = hs.id OR (nv_sub.so_cccd = t.so_cccd AND nv_sub.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id AND nv_sub.ho_so_id IS NULL))
                     ORDER BY nv_sub.thu_tu_nguyen_vong ASC LIMIT 1) as nv1 
                    FROM {$this->table} t 
                    INNER JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd 
                    $baseJoins
                    WHERE EXISTS (SELECT 1 FROM nguyen_vong nv_check WHERE nv_check.ho_so_id = hs.id OR (nv_check.so_cccd = hs.so_cccd AND nv_check.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id AND nv_check.ho_so_id IS NULL))";
            
            if ($sessionId) {
                $sql .= " AND hs.dot_tuyen_sinh_id = ?";
                $params[] = $sessionId;
            } elseif ($year) {
                $sql .= " AND EXISTS (SELECT 1 FROM dot_tuyen_sinh dt WHERE hs.dot_tuyen_sinh_id = dt.id AND dt.dm_nam_tuyen_sinh_nam = ?)";
                $params[] = $year;
            }

            if ($excludeTrash) {
                $sql .= " AND t.deleted_at IS NULL";
            }

            if ($onlyEditRequests) {
                $sql .= " AND hs.yeu_cau_chinh_sua = TRUE";
            }
        } else {
            $sql = "SELECT $baseSelect, 
                    (SELECT nv_sub.ten_nganh FROM nguyen_vong nv_sub 
                     WHERE nv_sub.so_cccd = t.so_cccd " . ($sessionId ? " AND nv_sub.dot_tuyen_sinh_id = " . (int)$sessionId : "") . "
                     ORDER BY nv_sub.thu_tu_nguyen_vong ASC LIMIT 1) as nv1 
                    FROM {$this->table} t $baseJoins 
                    WHERE 1=1";
            
            if ($applicationStatus === 'trash') {
                $sql .= " AND t.deleted_at IS NOT NULL";
            } elseif ($excludeTrash) {
                $sql .= " AND t.deleted_at IS NULL";
            }

            if ($applicationStatus === 'all') {
                if ($sessionId) {
                    $sql .= " AND (EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.dot_tuyen_sinh_id = ?) OR NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = t.so_cccd))";
                    $params[] = $sessionId;
                } elseif ($year) {
                    $sql .= " AND (EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id WHERE hs.so_cccd = t.so_cccd AND dt.dm_nam_tuyen_sinh_nam = ?) OR NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = t.so_cccd))";
                    $params[] = $year;
                }
            } elseif ($applicationStatus === 'ghost') {
                $sql .= " AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND EXISTS (SELECT 1 FROM nguyen_vong nv_check WHERE nv_check.ho_so_id = hs.id OR (nv_check.so_cccd = hs.so_cccd AND nv_check.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id)))";
            }

            if ($onlyEditRequests) {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.yeu_cau_chinh_sua = TRUE)";
            }
        }

        if (!empty($search)) {
            $sql .= " AND (t.ho_va_ten ILIKE ? OR t.so_cccd ILIKE ? OR t.email ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            if ($status === 'Đã duyệt') {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_st WHERE hs_st.so_cccd = t.so_cccd AND hs_st.deleted_at IS NULL AND (hs_st.trang_thai ILIKE 'Đã duyệt%' OR hs_st.trang_thai ILIKE 'approved%' OR hs_st.trang_thai ILIKE 'DaDuyet%'))";
            } elseif ($status === 'Chờ duyệt') {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_st WHERE hs_st.so_cccd = t.so_cccd AND hs_st.deleted_at IS NULL AND (hs_st.trang_thai ILIKE 'Chờ duyệt%' OR hs_st.trang_thai ILIKE 'pending%' OR hs_st.trang_thai ILIKE 'ChoDuyet%'))";
            } else {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_st WHERE hs_st.so_cccd = t.so_cccd AND hs_st.deleted_at IS NULL AND hs_st.trang_thai ILIKE ?)";
                $params[] = "%$status%";
            }
        }

        if ($hocBaStatus !== '') {
            $sql .= " AND t.da_du_6_ky = ?";
            $params[] = ($hocBaStatus == '1' ? 'true' : 'false');
        }

        // Extra column-specific filters
        if (!empty($extraFilters)) {
            foreach ($extraFilters as $field => $val) {
                if ($val === '' || $val === null) continue;
                if ($field === 'phone') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.dien_thoai IS NULL OR t.dien_thoai = '')";
                    } else {
                        $sql .= " AND t.dien_thoai ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'dob') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND t.ngay_sinh IS NULL";
                    } else {
                        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', trim($val), $matches)) {
                            $formattedDate = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                            $sql .= " AND t.ngay_sinh = ?";
                            $params[] = $formattedDate;
                        } else {
                            $sql .= " AND t.ngay_sinh::text ILIKE ?";
                            $params[] = "%$val%";
                        }
                    }
                } elseif ($field === 'province') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.ma_tinh_ho_khau IS NULL OR t.ma_tinh_ho_khau = '' OR NOT EXISTS (SELECT 1 FROM dm_tinh dt WHERE dt.ma_tinh = t.ma_tinh_ho_khau))";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM dm_tinh dt WHERE dt.ma_tinh = t.ma_tinh_ho_khau AND dt.ten_tinh ILIKE ?)";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'school') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.ma_truong_lop_12 IS NULL OR t.ma_truong_lop_12 = '' OR NOT EXISTS (SELECT 1 FROM dm_truong_thpt ds WHERE ds.ma_truong = t.ma_truong_lop_12 AND ds.is_active = TRUE))";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM dm_truong_thpt ds WHERE ds.ma_truong = t.ma_truong_lop_12 AND ds.ten_truong ILIKE ? AND ds.is_active = TRUE)";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'nv1') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    $nv1Where = "SELECT 1 FROM nguyen_vong nv LEFT JOIN dm_nganh dn ON nv.ma_nganh = dn.ma_nganh WHERE nv.so_cccd = t.so_cccd AND nv.thu_tu_nguyen_vong = 1";
                    if ($sessionId) {
                        $nv1Where .= " AND nv.dot_tuyen_sinh_id = " . (int)$sessionId;
                    }
                    if ($trimVal === 'chưa đk' || $trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS ($nv1Where)";
                    } else {
                        $sql .= " AND EXISTS ($nv1Where AND (nv.ten_nganh ILIKE ? OR dn.ten_nganh ILIKE ?))";
                        $params[] = "%$val%";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'gender') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.gioi_tinh IS NULL OR t.gioi_tinh = '')";
                    } else {
                        $sql .= " AND t.gioi_tinh ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'ethnicity') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.dan_toc IS NULL OR t.dan_toc = '')";
                    } else {
                        $sql .= " AND t.dan_toc ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'area') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.khu_vuc_uu_tien IS NULL OR t.khu_vuc_uu_tien = '')";
                    } else {
                        $sql .= " AND t.khu_vuc_uu_tien ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'object') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.doi_tuong_uu_tien IS NULL OR t.doi_tuong_uu_tien = '')";
                    } else {
                        $sql .= " AND t.doi_tuong_uu_tien ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'grad_year') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND t.nam_tot_nghiep IS NULL";
                    } else {
                        $sql .= " AND t.nam_tot_nghiep::text ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'email') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.email IS NULL OR t.email = '')";
                    } else {
                        $sql .= " AND t.email ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'note') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        if ($sessionId) {
                            $sql .= " AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.dot_tuyen_sinh_id = " . (int)$sessionId . " AND hs.ghi_chu IS NOT NULL AND hs.ghi_chu != '')";
                        } else {
                            $sql .= " AND (t.ghi_chu IS NULL OR t.ghi_chu = '') AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.ghi_chu IS NOT NULL AND hs.ghi_chu != '')";
                        }
                    } else {
                        if ($sessionId) {
                            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.dot_tuyen_sinh_id = " . (int)$sessionId . " AND hs.ghi_chu ILIKE ?)";
                            $params[] = "%$val%";
                        } else {
                            $sql .= " AND (t.ghi_chu ILIKE ? OR EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.ghi_chu ILIKE ?))";
                            $params[] = "%$val%";
                            $params[] = "%$val%";
                        }
                    }
                } elseif ($field === 'transcript') {
                    $sql .= " AND (SELECT 
                        CASE 
                            WHEN COUNT(*) = 0 THEN 'not_entered'
                            WHEN COUNT(*) FILTER (WHERE lop = 12) = 0 AND COUNT(*) FILTER (WHERE lop IN (10, 11)) > 0 THEN 'missing_12'
                            WHEN COUNT(DISTINCT lop) >= 3 THEN 'full'
                            ELSE 'partial'
                        END
                     FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd) = ?";
                    $params[] = $val;
                } elseif ($field === 'reviewer') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_rv WHERE hs_rv.so_cccd = t.so_cccd AND hs_rv.nguoi_duyet_id IS NOT NULL" . ($sessionId ? " AND hs_rv.dot_tuyen_sinh_id = " . (int)$sessionId : "") . ")";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_rv JOIN quan_tri_vien qtv_rv ON hs_rv.nguoi_duyet_id = qtv_rv.id WHERE hs_rv.so_cccd = t.so_cccd AND (qtv_rv.ho_ten ILIKE ? OR qtv_rv.ten_dang_nhap ILIKE ?)" . ($sessionId ? " AND hs_rv.dot_tuyen_sinh_id = " . (int)$sessionId : "") . ")";
                        $params[] = "%$val%";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'graduation_score') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS (SELECT 1 FROM diem_thi_thpt dth WHERE dth.so_cccd = t.so_cccd AND dth.diem_xet_tot_nghiep IS NOT NULL" . ($year ? " AND dth.nam_thi = " . (int)$year : "") . ")";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM diem_thi_thpt dth WHERE dth.so_cccd = t.so_cccd AND dth.diem_xet_tot_nghiep::text ILIKE ?" . ($year ? " AND dth.nam_thi = " . (int)$year : "") . ")";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'tb_chung_12') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.diem_tb_ca_nam IS NOT NULL)";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.diem_tb_ca_nam::text ILIKE ?)";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'hoc_luc_12') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.hoc_luc_ca_nam IS NOT NULL AND hb.hoc_luc_ca_nam != '')";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.hoc_luc_ca_nam ILIKE ?)";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'hanh_kiem_12') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.hanh_kiem_ca_nam IS NOT NULL AND hb.hanh_kiem_ca_nam != '')";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.hanh_kiem_ca_nam ILIKE ?)";
                        $params[] = "%$val%";
                    }
                }
            }
        }

        // Validate sort field and map to exact SQL expressions
        $sortMap = [
            'ho_va_ten' => 't.ho_va_ten',
            'so_cccd' => 't.so_cccd',
            'ngay_sinh' => 't.ngay_sinh',
            'dien_thoai' => 't.dien_thoai',
            'ngay_tao' => 't.ngay_tao',
            'ghi_chu' => 't.ghi_chu',
            'email' => 't.email',
            'reviewer_name' => 'reviewer_name',
            'province' => 'p.ten_tinh',
            'school' => 's.ten_truong',
            'nv1' => 'nv1',
            'gender' => 't.gioi_tinh',
            'ethnicity' => 't.dan_toc',
            'area' => 't.khu_vuc_uu_tien',
            'object' => 't.doi_tuong_uu_tien',
            'grad_year' => 't.nam_tot_nghiep',
            'transcript_status' => 'transcript_status',
            'graduation_score' => 'graduation_score',
            'tb_chung_12' => 'tb_chung_12',
            'hoc_luc_12' => 'hoc_luc_12',
            'hanh_kiem_12' => 'hanh_kiem_12'
        ];

        $orderBy = 't.ngay_tao';
        if (array_key_exists($sort, $sortMap)) {
            $orderBy = $sortMap[$sort];
        }
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY $orderBy $dir LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($candidates)) return [];

        $cccds = array_column($candidates, 'so_cccd');
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));

        // Filter statuses and master status by session if provided
        $statusSql = "SELECT nv.so_cccd, string_agg(nv.trang_thai, ', ') as statuses 
                      FROM nguyen_vong nv
                      WHERE nv.so_cccd IN ($placeholders)";
        $statusParams = $cccds;
        if ($sessionId) {
            $statusSql .= " AND nv.dot_tuyen_sinh_id = ?";
            $statusParams[] = $sessionId;
        } elseif ($year) {
            $statusSql .= " AND EXISTS (SELECT 1 FROM dot_tuyen_sinh dt WHERE nv.dot_tuyen_sinh_id = dt.id AND dt.dm_nam_tuyen_sinh_nam = ?)";
            $statusParams[] = $year;
        }
        $statusSql .= " GROUP BY nv.so_cccd";
        
        $stmtStatus = $this->db->prepare($statusSql);
        $stmtStatus->execute($statusParams);
        $statusMap = $stmtStatus->fetchAll(PDO::FETCH_KEY_PAIR);

        $editSql = "SELECT hs.so_cccd, COUNT(*) > 0 as has_edit_request, string_agg(hs.trang_thai, ', ') as master_status, string_agg(hs.ghi_chu, '\n') as ghi_chu
                    FROM ho_so_xet_tuyen hs
                    WHERE hs.so_cccd IN ($placeholders)";
        $editParams = $cccds;
        if ($sessionId) {
            $editSql .= " AND hs.dot_tuyen_sinh_id = ?";
            $editParams[] = $sessionId;
        } elseif ($year) {
            $editSql .= " AND EXISTS (SELECT 1 FROM dot_tuyen_sinh dt WHERE hs.dot_tuyen_sinh_id = dt.id AND dt.dm_nam_tuyen_sinh_nam = ?)";
            $editParams[] = $year;
        }
        $editSql .= " GROUP BY hs.so_cccd";
        
        $stmtEdit = $this->db->prepare($editSql);
        $stmtEdit->execute($editParams);
        $masterStatusMap = $stmtEdit->fetchAll(PDO::FETCH_ASSOC);
        $editMap = [];
        $statusMapHoso = [];
        $noteMap = [];
        foreach($masterStatusMap as $ms) {
            $cleanedCCCD = trim($ms['so_cccd']);
            $editMap[$cleanedCCCD] = $ms['has_edit_request'];
            $statusMapHoso[$cleanedCCCD] = $ms['master_status'];
            $noteMap[$cleanedCCCD] = $ms['ghi_chu'];
        }

        // Fetch and merge Transcript Notes
        $transcriptNoteSql = "SELECT so_cccd, string_agg(DISTINCT ghi_chu, '; ') as transcript_notes 
                              FROM ket_qua_hoc_tap 
                              WHERE so_cccd IN ($placeholders) AND ghi_chu IS NOT NULL AND ghi_chu != ''
                              GROUP BY so_cccd";
        $stmtTranscriptNote = $this->db->prepare($transcriptNoteSql);
        $stmtTranscriptNote->execute($cccds);
        $transcriptNotes = $stmtTranscriptNote->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($transcriptNotes as $cccd => $tn) {
            $cleanedCCCD = trim($cccd);
            if (isset($noteMap[$cleanedCCCD]) && !empty($noteMap[$cleanedCCCD])) {
                if (strpos($noteMap[$cleanedCCCD], $tn) === false) {
                    $noteMap[$cleanedCCCD] .= " (" . $tn . ")";
                }
            } else {
                $noteMap[$cleanedCCCD] = $tn;
            }
        }

        // Metadata (Province, School, NV1) is now fetched via LEFT JOINs in the primary query.
        // Secondary mapping queries for statuses and edit requests remain separate for group-by efficiency.

        foreach ($candidates as &$candidate) {
            $cccd = trim($candidate['so_cccd']);
            $candidate['statuses'] = $statusMap[$cccd] ?? '';
            $candidate['master_status'] = $statusMapHoso[$cccd] ?? ''; // Use ho_so_xet_tuyen status
            
            // Prioritize the application/review note (which includes transcript note suffixes).
            // If it is set, it replaces/overrides the base candidate note (thi_sinh note).
            $hosoNotes = $noteMap[$cccd] ?? '';
            $baseNote = $candidate['base_ghi_chu'] ?? '';
            
            if (!empty($hosoNotes)) {
                $candidate['ghi_chu'] = $hosoNotes;
            } else {
                // Only fallback to base note (thi_sinh.ghi_chu) if we aren't filtering by a specific session.
                // This prevents old session notes that leaked into thi_sinh from appearing in new sessions.
                $candidate['ghi_chu'] = $sessionId ? '' : $baseNote;
            }
            $candidate['has_edit_request'] = !empty($editMap[$cccd]);
        }

        $duration = microtime(true) - $startTime;
        \App\Core\Logger::log("ThiSinh::getFiltered end. Duration: " . round($duration, 4) . "s. Count: " . count($candidates));
        return $candidates;
    }

    public function countFiltered($search = '', $status = '', $hocBaStatus = '', $sessionId = null, $onlyEditRequests = false, $year = null, $excludeTrash = true, $extraFilters = [], $applicationStatus = 'all') {
        $startTime = microtime(true);
        \App\Core\Logger::log("ThiSinh::countFiltered start. Status: $applicationStatus");
        $params = [];
        
        if ($applicationStatus === 'submitted') {
            $sql = "SELECT COUNT(DISTINCT t.so_cccd) FROM {$this->table} t 
                    INNER JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd 
                    WHERE EXISTS (SELECT 1 FROM nguyen_vong nv_check WHERE nv_check.ho_so_id = hs.id OR (nv_check.so_cccd = hs.so_cccd AND nv_check.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id AND nv_check.ho_so_id IS NULL))";
            
            if ($excludeTrash) {
                $sql .= " AND t.deleted_at IS NULL";
            }
            
            if ($sessionId) {
                $sql .= " AND hs.dot_tuyen_sinh_id = ?";
                $params[] = $sessionId;
            } elseif ($year) {
                $sql .= " AND EXISTS (SELECT 1 FROM dot_tuyen_sinh dt WHERE hs.dot_tuyen_sinh_id = dt.id AND dt.dm_nam_tuyen_sinh_nam = ?)";
                $params[] = $year;
            }
            
            if ($onlyEditRequests) {
                $sql .= " AND hs.yeu_cau_chinh_sua = TRUE";
            }
        } else {
            $sql = "SELECT COUNT(*) FROM {$this->table} t WHERE 1=1";
            
            if ($applicationStatus === 'trash') {
                $sql .= " AND t.deleted_at IS NOT NULL";
            } elseif ($excludeTrash) {
                $sql .= " AND t.deleted_at IS NULL";
            }

            if ($applicationStatus === 'all') {
                if ($sessionId) {
                    $sql .= " AND (EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.dot_tuyen_sinh_id = ?) OR NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = t.so_cccd))";
                    $params[] = $sessionId;
                } elseif ($year) {
                    $sql .= " AND (EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id WHERE hs.so_cccd = t.so_cccd AND dt.dm_nam_tuyen_sinh_nam = ?) OR NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = t.so_cccd))";
                    $params[] = $year;
                }
            } elseif ($applicationStatus === 'ghost') {
                $sql .= " AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND EXISTS (SELECT 1 FROM nguyen_vong nv_check WHERE nv_check.ho_so_id = hs.id OR (nv_check.so_cccd = hs.so_cccd AND nv_check.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id)))";
            }

            if ($onlyEditRequests) {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.yeu_cau_chinh_sua = TRUE)";
            }
        }

        if (!empty($search)) {
            $sql .= " AND (t.ho_va_ten ILIKE ? OR t.so_cccd ILIKE ? OR t.email ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            if ($status === 'Đã duyệt') {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_st WHERE hs_st.so_cccd = t.so_cccd AND hs_st.deleted_at IS NULL AND (hs_st.trang_thai ILIKE 'Đã duyệt%' OR hs_st.trang_thai ILIKE 'approved%' OR hs_st.trang_thai ILIKE 'DaDuyet%'))";
            } elseif ($status === 'Chờ duyệt') {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_st WHERE hs_st.so_cccd = t.so_cccd AND hs_st.deleted_at IS NULL AND (hs_st.trang_thai ILIKE 'Chờ duyệt%' OR hs_st.trang_thai ILIKE 'pending%' OR hs_st.trang_thai ILIKE 'ChoDuyet%'))";
            } else {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_st WHERE hs_st.so_cccd = t.so_cccd AND hs_st.deleted_at IS NULL AND hs_st.trang_thai ILIKE ?)";
                $params[] = "%$status%";
            }
        }

        if ($hocBaStatus !== '') {
            $sql .= " AND t.da_du_6_ky = ?";
            $params[] = ($hocBaStatus == '1' ? 'true' : 'false');
        }

        if (!empty($extraFilters)) {
            foreach ($extraFilters as $field => $val) {
                if ($val === '' || $val === null) continue;
                if ($field === 'phone') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.dien_thoai IS NULL OR t.dien_thoai = '')";
                    } else {
                        $sql .= " AND t.dien_thoai ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'dob') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND t.ngay_sinh IS NULL";
                    } else {
                        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', trim($val), $matches)) {
                            $formattedDate = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                            $sql .= " AND t.ngay_sinh = ?";
                            $params[] = $formattedDate;
                        } else {
                            $sql .= " AND t.ngay_sinh::text ILIKE ?";
                            $params[] = "%$val%";
                        }
                    }
                } elseif ($field === 'province') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.ma_tinh_ho_khau IS NULL OR t.ma_tinh_ho_khau = '' OR NOT EXISTS (SELECT 1 FROM dm_tinh dt WHERE dt.ma_tinh = t.ma_tinh_ho_khau))";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM dm_tinh dt WHERE dt.ma_tinh = t.ma_tinh_ho_khau AND dt.ten_tinh ILIKE ?)";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'school') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.ma_truong_lop_12 IS NULL OR t.ma_truong_lop_12 = '' OR NOT EXISTS (SELECT 1 FROM dm_truong_thpt ds WHERE ds.ma_truong = t.ma_truong_lop_12 AND ds.is_active = TRUE))";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM dm_truong_thpt ds WHERE ds.ma_truong = t.ma_truong_lop_12 AND ds.ten_truong ILIKE ? AND ds.is_active = TRUE)";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'nv1') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    $nv1Where = "SELECT 1 FROM nguyen_vong nv LEFT JOIN dm_nganh dn ON nv.ma_nganh = dn.ma_nganh WHERE nv.so_cccd = t.so_cccd AND nv.thu_tu_nguyen_vong = 1";
                    if ($sessionId) {
                        $nv1Where .= " AND nv.dot_tuyen_sinh_id = " . (int)$sessionId;
                    }
                    if ($trimVal === 'chưa đk' || $trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS ($nv1Where)";
                    } else {
                        $sql .= " AND EXISTS ($nv1Where AND (nv.ten_nganh ILIKE ? OR dn.ten_nganh ILIKE ?))";
                        $params[] = "%$val%";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'gender') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.gioi_tinh IS NULL OR t.gioi_tinh = '')";
                    } else {
                        $sql .= " AND t.gioi_tinh ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'ethnicity') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.dan_toc IS NULL OR t.dan_toc = '')";
                    } else {
                        $sql .= " AND t.dan_toc ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'area') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.khu_vuc_uu_tien IS NULL OR t.khu_vuc_uu_tien = '')";
                    } else {
                        $sql .= " AND t.khu_vuc_uu_tien ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'object') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.doi_tuong_uu_tien IS NULL OR t.doi_tuong_uu_tien = '')";
                    } else {
                        $sql .= " AND t.doi_tuong_uu_tien ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'grad_year') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND t.nam_tot_nghiep IS NULL";
                    } else {
                        $sql .= " AND t.nam_tot_nghiep::text ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'email') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND (t.email IS NULL OR t.email = '')";
                    } else {
                        $sql .= " AND t.email ILIKE ?";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'note') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        if ($sessionId) {
                            $sql .= " AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.dot_tuyen_sinh_id = " . (int)$sessionId . " AND hs.ghi_chu IS NOT NULL AND hs.ghi_chu != '')";
                        } else {
                            $sql .= " AND (t.ghi_chu IS NULL OR t.ghi_chu = '') AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.ghi_chu IS NOT NULL AND hs.ghi_chu != '')";
                        }
                    } else {
                        if ($sessionId) {
                            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.dot_tuyen_sinh_id = " . (int)$sessionId . " AND hs.ghi_chu ILIKE ?)";
                            $params[] = "%$val%";
                        } else {
                            $sql .= " AND (t.ghi_chu ILIKE ? OR EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.ghi_chu ILIKE ?))";
                            $params[] = "%$val%";
                            $params[] = "%$val%";
                        }
                    }
                } elseif ($field === 'transcript') {
                    $sql .= " AND (SELECT 
                        CASE 
                            WHEN COUNT(*) = 0 THEN 'not_entered'
                            WHEN COUNT(*) FILTER (WHERE lop = 12) = 0 AND COUNT(*) FILTER (WHERE lop IN (10, 11)) > 0 THEN 'missing_12'
                            WHEN COUNT(DISTINCT lop) >= 3 THEN 'full'
                            ELSE 'partial'
                        END
                     FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd) = ?";
                    $params[] = $val;
                } elseif ($field === 'reviewer') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_rv WHERE hs_rv.so_cccd = t.so_cccd AND hs_rv.nguoi_duyet_id IS NOT NULL" . ($sessionId ? " AND hs_rv.dot_tuyen_sinh_id = " . (int)$sessionId : "") . ")";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_rv JOIN quan_tri_vien qtv_rv ON hs_rv.nguoi_duyet_id = qtv_rv.id WHERE hs_rv.so_cccd = t.so_cccd AND (qtv_rv.ho_ten ILIKE ? OR qtv_rv.ten_dang_nhap ILIKE ?)" . ($sessionId ? " AND hs_rv.dot_tuyen_sinh_id = " . (int)$sessionId : "") . ")";
                        $params[] = "%$val%";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'graduation_score') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS (SELECT 1 FROM diem_thi_thpt dth WHERE dth.so_cccd = t.so_cccd AND dth.diem_xet_tot_nghiep IS NOT NULL" . ($year ? " AND dth.nam_thi = " . (int)$year : "") . ")";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM diem_thi_thpt dth WHERE dth.so_cccd = t.so_cccd AND dth.diem_xet_tot_nghiep::text ILIKE ?" . ($year ? " AND dth.nam_thi = " . (int)$year : "") . ")";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'tb_chung_12') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.diem_tb_ca_nam IS NOT NULL)";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.diem_tb_ca_nam::text ILIKE ?)";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'hoc_luc_12') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.hoc_luc_ca_nam IS NOT NULL AND hb.hoc_luc_ca_nam != '')";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.hoc_luc_ca_nam ILIKE ?)";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'hanh_kiem_12') {
                    $trimVal = trim(mb_strtolower($val, 'UTF-8'));
                    if ($trimVal === 'trống' || $trimVal === 'empty') {
                        $sql .= " AND NOT EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.hanh_kiem_ca_nam IS NOT NULL AND hb.hanh_kiem_ca_nam != '')";
                    } else {
                        $sql .= " AND EXISTS (SELECT 1 FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 AND hb.hanh_kiem_ca_nam ILIKE ?)";
                        $params[] = "%$val%";
                    }
                }
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $count = (int)$stmt->fetchColumn();
        $duration = microtime(true) - $startTime;
        \App\Core\Logger::log("ThiSinh::countFiltered end. Duration: " . round($duration, 4) . "s. Count: $count");
        return $count;
    }

    public function getStats($sessionId = null, $year = null, $startDate = null, $endDate = null) {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'require_edit' => 0,
            'edit_requests' => 0,
            'ghost' => 0
        ];

        // 1. Get Application Related Stats (total, pending, approved, etc.)
        $hsWhere = " WHERE hs.deleted_at IS NULL AND t.deleted_at IS NULL AND EXISTS (SELECT 1 FROM nguyen_vong nv_check WHERE nv_check.ho_so_id = hs.id OR (nv_check.so_cccd = hs.so_cccd AND nv_check.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id))";
        $params = [];
        
        // Use JOIN instead of EXISTS for better performance, excluding soft-deleted candidates
        $from = "ho_so_xet_tuyen hs INNER JOIN {$this->table} t ON hs.so_cccd = t.so_cccd";
        if ($sessionId) {
            $hsWhere .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = (int)$sessionId;
        } elseif ($year) {
            $from .= " INNER JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id";
            $hsWhere .= " AND (dt.nam_tuyen_sinh = ? OR dt.dm_nam_tuyen_sinh_nam = ?)";
            $params[] = (int)$year;
            $params[] = (int)$year;
        }

        if ($startDate && $endDate) {
            $hsWhere .= " AND hs.created_at >= ? AND hs.created_at <= ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }

        // Optimized status filtering: use simple case-insensitive comparison where possible or combine common strings
        // Standardize status strings for the query
        $sqlHS = "SELECT 
            COUNT(*) as total,
            COUNT(*) FILTER (WHERE hs.trang_thai ILIKE 'Đã duyệt%' OR hs.trang_thai ILIKE 'approved%' OR hs.trang_thai ILIKE 'DaDuyet%') as approved,
            COUNT(*) FILTER (WHERE hs.trang_thai ILIKE 'Yêu cầu sửa%' OR hs.trang_thai ILIKE 'require_edit%' OR hs.trang_thai ILIKE 'Yêu cầu chỉnh sửa%') as require_edit,
            COUNT(*) FILTER (WHERE hs.yeu_cau_chinh_sua = TRUE) as edit_requests
            FROM $from $hsWhere";

        try {
            $stmt = $this->db->prepare($sqlHS);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $stats['total'] = (int)$result['total'];
                $stats['approved'] = (int)$result['approved'];
                $stats['require_edit'] = (int)$result['require_edit'];
                $stats['edit_requests'] = (int)$result['edit_requests'];
                $stats['pending'] = $stats['total'] - $stats['approved'] - $stats['require_edit'];
            }
        } catch (\PDOException $e) {
            error_log("Error in getStats (Application query): " . $e->getMessage());
        }

        // 2. Get Ghost Candidates
        $ghostWhere = " WHERE NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND EXISTS (SELECT 1 FROM nguyen_vong nv_check WHERE nv_check.ho_so_id = hs.id OR (nv_check.so_cccd = hs.so_cccd AND nv_check.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id))) AND t.deleted_at IS NULL";
        $ghostParams = [];
        if ($year) {
            $ghostWhere .= " AND EXTRACT(YEAR FROM t.ngay_tao) = ?";
            $ghostParams[] = (int)$year;
        }

        $sqlGhost = "SELECT COUNT(*) FROM {$this->table} t $ghostWhere";

        try {
            $stmt = $this->db->prepare($sqlGhost);
            $stmt->execute($ghostParams);
            $stats['ghost'] = (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Error in getStats (Ghost query): " . $e->getMessage());
        }

        return $stats;
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (so_cccd, ho_va_ten, mat_khau, dien_thoai, email) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        $cccd = $data['so_cccd'] ?? $data['cccd'] ?? null;
        $name = $data['ho_va_ten'] ?? $data['fullname'] ?? '';
        $pass = $data['mat_khau'] ?? $data['password'] ?? '';
        $phone = $data['so_dien_thoai'] ?? $data['dien_thoai'] ?? $data['phone'] ?? '';
        $email = $data['email'] ?? '';

        return $stmt->execute([
            $cccd,
            $name,
            $pass,
            $phone,
            $email
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
        $res = $stmt->execute($params);
        if ($res) {
            try {
                $stmtTouch = $this->db->prepare("UPDATE nguyen_vong SET updated_at = CURRENT_TIMESTAMP WHERE so_cccd = ?");
                $stmtTouch->execute([$cccd]);
            } catch (\Exception $e) {
                error_log("Failed to touch nguyen_vong in ThiSinh::updateFullProfile: " . $e->getMessage());
            }
        }
        return $res;
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
            // Delete old certs
            $stmt = $this->db->prepare("DELETE FROM chung_chi_thi_sinh WHERE so_cccd = ?");
            $stmt->execute([$cccd]);

            $hasCert = false;

            // Bulk Insert new certs
            if (!empty($certs)) {
                $insertValues = [];
                $params = [];
                foreach ($certs as $cert) {
                    if (!empty($cert['loai_chung_chi'])) {
                        $insertValues[] = "(?, ?, ?, ?)";
                        $params[] = $cccd;
                        $params[] = $cert['loai_chung_chi'];
                        $params[] = $cert['diem_chung_chi'] ?? null;
                        $params[] = $cert['file_minh_chung_cc'] ?? null;
                        $hasCert = true;
                    }
                }

                if ($hasCert) {
                    $sql = "INSERT INTO chung_chi_thi_sinh (so_cccd, loai_chung_chi, diem_chung_chi, file_minh_chung_cc) VALUES " . implode(', ', $insertValues);
                    $this->db->prepare($sql)->execute($params);
                }
            }

            // Sync flag in thi_sinh
            $stmt = $this->db->prepare("UPDATE {$this->table} SET co_chung_chi_qt = ? WHERE so_cccd = ?");
            $stmt->execute([$hasCert ? 'true' : 'false', $cccd]);

            return true;
        } catch (\Exception $e) {
            error_log("SAVE CERTS ERROR: " . $e->getMessage());
            return false;
        }
    }


    public function getSchoolStats($limit = 10, $startDate = null, $endDate = null, $sessionId = null) {
         $sql = "SELECT COALESCE(truong.ten_truong, 'Chưa cập nhật') as label, COUNT(DISTINCT ts.so_cccd) as count 
                FROM {$this->table} ts
                JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
                LEFT JOIN dm_truong_thpt truong ON ts.ma_truong_lop_12 = truong.ma_truong AND truong.is_active = TRUE
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
        $sql .= " GROUP BY p.ten_tinh ORDER BY count DESC LIMIT " . intval($limit);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getGenderStats($startDate = null, $endDate = null, $sessionId = null) {
        $sql = "SELECT CASE WHEN ts.gioi_tinh = 'Nam' THEN 'Nam' WHEN ts.gioi_tinh = 'Nữ' THEN 'Nữ' ELSE 'Khác' END as label, COUNT(DISTINCT ts.so_cccd) as count 
                FROM {$this->table} ts
                JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
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
        $sql .= " GROUP BY ts.gioi_tinh";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAreaStats($startDate = null, $endDate = null, $sessionId = null) {
        $sql = "SELECT COALESCE(ts.khu_vuc_uu_tien, 'Không') as label, COUNT(DISTINCT ts.so_cccd) as count 
                FROM {$this->table} ts
                JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
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
        $sql .= " GROUP BY ts.khu_vuc_uu_tien";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getObjectStats($startDate = null, $endDate = null, $sessionId = null) {
        $sql = "SELECT COALESCE(ts.doi_tuong_uu_tien, 'Không') as label, COUNT(DISTINCT ts.so_cccd) as count 
                FROM {$this->table} ts
                JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
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
        $where  = "WHERE hs.deleted_at IS NULL";
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

        $sqlGender = "SELECT 'gender' as type, COALESCE(ts.gioi_tinh, 'Khác') as label, COUNT(DISTINCT ts.so_cccd) as count 
                      FROM {$this->table} ts JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd $where GROUP BY 1, 2";
        
        $sqlArea = "SELECT 'area' as type, COALESCE(ts.khu_vuc_uu_tien, 'Không') as label, COUNT(DISTINCT ts.so_cccd) as count 
                    FROM {$this->table} ts JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd $where GROUP BY 1, 2";
        
        $sqlObject = "SELECT 'object' as type, COALESCE(ts.doi_tuong_uu_tien, 'Không') as label, COUNT(DISTINCT ts.so_cccd) as count 
                      FROM {$this->table} ts JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd $where GROUP BY 1, 2";

        $combinedSql = "$sqlGender UNION ALL $sqlArea UNION ALL $sqlObject";
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
                  COUNT(DISTINCT CASE WHEN hs.created_at >= ? THEN hs.so_cccd END) as count_today,
                  COUNT(DISTINCT CASE WHEN hs.created_at >= ? THEN hs.so_cccd END) as count_week
                FROM ho_so_xet_tuyen hs
                WHERE EXISTS (SELECT 1 FROM nguyen_vong nv_check WHERE nv_check.ho_so_id = hs.id OR (nv_check.so_cccd = hs.so_cccd AND nv_check.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id))";
        
        $params = [$todayStart, $weekStart];

        if ($sessionId) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
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
                WHERE EXISTS (SELECT 1 FROM nguyen_vong nv_check WHERE nv_check.ho_so_id = hs.id OR (nv_check.so_cccd = hs.so_cccd AND nv_check.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id))";
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
