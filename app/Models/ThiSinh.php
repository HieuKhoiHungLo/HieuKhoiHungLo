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
        $params = [];
        
        $baseSelect = "t.*, t.ghi_chu as base_ghi_chu, p.ten_tinh as province_name, s.ten_truong as school_name";
        $baseJoins = " LEFT JOIN dm_tinh p ON t.ma_tinh_ho_khau = p.ma_tinh
                       LEFT JOIN dm_truong_thpt s ON t.ma_truong_lop_12 = s.ma_truong";

        // Optimize for 'submitted' mode by joining ho_so_xet_tuyen early
        if ($applicationStatus === 'submitted') {
            $sql = "SELECT $baseSelect, COALESCE(dmn_nv1.ten_nganh, nv_first.ten_nganh) as nv1 
                    FROM {$this->table} t 
                    INNER JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd 
                    $baseJoins
                    LEFT JOIN nguyen_vong nv_first ON (nv_first.ho_so_id = hs.id OR (nv_first.so_cccd = t.so_cccd AND nv_first.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id AND nv_first.ho_so_id IS NULL)) AND nv_first.thu_tu_nguyen_vong = 1
                    LEFT JOIN dm_nganh dmn_nv1 ON nv_first.ma_nganh = dmn_nv1.ma_nganh
                    WHERE 1=1";
            
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
            $sql = "SELECT $baseSelect, nv_first.ten_nganh as nv1 
                    FROM {$this->table} t $baseJoins 
                    LEFT JOIN nguyen_vong nv_first ON t.so_cccd = nv_first.so_cccd AND nv_first.thu_tu_nguyen_vong = 1
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
                $sql .= " AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd)";
            }

            if ($onlyEditRequests) {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.yeu_cau_chinh_sua = TRUE)";
            }
        }

        if (!empty($search)) {
            $sql .= " AND (t.ho_va_ten LIKE ? OR t.so_cccd LIKE ? OR t.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_st WHERE hs_st.so_cccd = t.so_cccd AND hs_st.trang_thai ILIKE ?)";
            $params[] = "%$status%";
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
                    $sql .= " AND t.dien_thoai ILIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'dob') {
                    $sql .= " AND t.ngay_sinh::text ILIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'province') {
                    $sql .= " AND EXISTS (SELECT 1 FROM dm_tinh dt WHERE dt.ma_tinh = t.ma_tinh_ho_khau AND dt.ten_tinh ILIKE ?)";
                    $params[] = "%$val%";
                } elseif ($field === 'school') {
                    $sql .= " AND EXISTS (SELECT 1 FROM dm_truong_thpt ds WHERE ds.ma_truong = t.ma_truong_lop_12 AND ds.ten_truong ILIKE ?)";
                    $params[] = "%$val%";
                } elseif ($field === 'nv1') {
                    $trimVal = trim(mb_strtolower($val));
                    $nv1Where = "SELECT 1 FROM nguyen_vong nv LEFT JOIN dm_nganh dn ON nv.ma_nganh = dn.ma_nganh WHERE nv.so_cccd = t.so_cccd AND nv.thu_tu_nguyen_vong = 1";
                    if ($sessionId) {
                        $nv1Where .= " AND nv.dot_tuyen_sinh_id = " . (int)$sessionId;
                    }
                    if ($trimVal === 'chưa đk') {
                        $sql .= " AND NOT EXISTS ($nv1Where)";
                    } else {
                        $sql .= " AND EXISTS ($nv1Where AND (nv.ten_nganh ILIKE ? OR dn.ten_nganh ILIKE ?))";
                        $params[] = "%$val%";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'gender') {
                    $sql .= " AND t.gioi_tinh ILIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'ethnicity') {
                    $sql .= " AND t.dan_toc ILIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'area') {
                    $sql .= " AND t.khu_vuc_uu_tien ILIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'object') {
                    $sql .= " AND t.doi_tuong_uu_tien ILIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'grad_year') {
                    $sql .= " AND t.nam_tot_nghiep::text LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'email') {
                    $sql .= " AND t.email LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'note') {
                    $trimVal = trim(mb_strtolower($val));
                    if ($trimVal === 'trống') {
                        $sql .= " AND (t.ghi_chu IS NULL OR t.ghi_chu = '') AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.ghi_chu IS NOT NULL AND hs.ghi_chu != '')";
                    } else {
                        $sql .= " AND (t.ghi_chu LIKE ? OR EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.ghi_chu LIKE ?))";
                        $params[] = "%$val%";
                        $params[] = "%$val%";
                    }
                }
            }
        }

        // Validate sort field
        $allowedSort = ['ho_va_ten', 'so_cccd', 'ngay_sinh', 'dien_thoai', 'ngay_tao', 'ghi_chu'];
        if (!in_array($sort, $allowedSort)) $sort = 'ngay_tao';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY t.$sort $dir LIMIT ? OFFSET ?";
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
                $noteMap[$cleanedCCCD] .= " (" . $tn . ")";
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
            
            // Combine base_ghi_chu (from thi_sinh) and ghi_chu (aggregated from ho_so_xet_tuyen)
            $hosoNotes = $noteMap[$cccd] ?? '';
            $baseNote = $candidate['base_ghi_chu'] ?? '';
            
            $combinedNotes = [];
            if (!empty($baseNote)) $combinedNotes[] = $baseNote;
            if (!empty($hosoNotes)) $combinedNotes[] = $hosoNotes;
            
            $candidate['ghi_chu'] = implode("\n", array_unique($combinedNotes));
            $candidate['has_edit_request'] = !empty($editMap[$cccd]);
        }

        return $candidates;
    }

    public function countFiltered($search = '', $status = '', $hocBaStatus = '', $sessionId = null, $onlyEditRequests = false, $year = null, $excludeTrash = true, $extraFilters = [], $applicationStatus = 'all') {
        $params = [];
        
        // Optimize for 'submitted' mode by joining ho_so_xet_tuyen early
        if ($applicationStatus === 'submitted') {
            $sql = "SELECT COUNT(DISTINCT t.so_cccd) FROM {$this->table} t 
                    INNER JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd 
                    WHERE 1=1";
            
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
                $sql .= " AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd)";
            }

            if ($onlyEditRequests) {
                $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.yeu_cau_chinh_sua = TRUE)";
            }
        }

        if (!empty($search)) {
            $sql .= " AND (t.ho_va_ten LIKE ? OR t.so_cccd LIKE ? OR t.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs_st WHERE hs_st.so_cccd = t.so_cccd AND hs_st.trang_thai IILIKE ?)";
            $params[] = "%$status%";
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
                    $sql .= " AND t.dien_thoai ILIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'dob') {
                    $sql .= " AND t.ngay_sinh::text ILIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'province') {
                    $sql .= " AND EXISTS (SELECT 1 FROM dm_tinh dt WHERE dt.ma_tinh = t.ma_tinh_ho_khau AND dt.ten_tinh ILIKE ?)";
                    $params[] = "%$val%";
                } elseif ($field === 'school') {
                    $sql .= " AND EXISTS (SELECT 1 FROM dm_truong_thpt ds WHERE ds.ma_truong = t.ma_truong_lop_12 AND ds.ten_truong ILIKE ?)";
                    $params[] = "%$val%";
                } elseif ($field === 'nv1') {
                    $trimVal = trim(mb_strtolower($val));
                    $nv1Where = "SELECT 1 FROM nguyen_vong nv LEFT JOIN dm_nganh dn ON nv.ma_nganh = dn.ma_nganh WHERE nv.so_cccd = t.so_cccd AND nv.thu_tu_nguyen_vong = 1";
                    if ($sessionId) {
                        $nv1Where .= " AND nv.dot_tuyen_sinh_id = " . (int)$sessionId;
                    }
                    if ($trimVal === 'chưa đk') {
                        $sql .= " AND NOT EXISTS ($nv1Where)";
                    } else {
                        $sql .= " AND EXISTS ($nv1Where AND (nv.ten_nganh ILIKE ? OR dn.ten_nganh ILIKE ?))";
                        $params[] = "%$val%";
                        $params[] = "%$val%";
                    }
                } elseif ($field === 'gender') {
                    $sql .= " AND t.gioi_tinh ILIKE ?";
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
                } elseif ($field === 'email') {
                    $sql .= " AND t.email LIKE ?";
                    $params[] = "%$val%";
                } elseif ($field === 'note') {
                    $trimVal = trim(mb_strtolower($val));
                    if ($trimVal === 'trống') {
                        $sql .= " AND (t.ghi_chu IS NULL OR t.ghi_chu = '') AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.ghi_chu IS NOT NULL AND hs.ghi_chu != '')";
                    } else {
                        $sql .= " AND (t.ghi_chu LIKE ? OR EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd AND hs.ghi_chu LIKE ?))";
                        $params[] = "%$val%";
                        $params[] = "%$val%";
                    }
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
            'edit_requests' => 0,
            'ghost' => 0
        ];

        // 1. Get Application Related Stats (total, pending, approved, etc.)
        $hsWhere = " WHERE 1=1";
        $params = [];
        if ($sessionId) {
            $hsWhere .= " AND dot_tuyen_sinh_id = " . (int)$sessionId;
        } elseif ($year) {
            $hsWhere .= " AND EXISTS (SELECT 1 FROM dot_tuyen_sinh dt WHERE dt.id = dot_tuyen_sinh_id AND dt.dm_nam_tuyen_sinh_nam = " . (int)$year . ")";
        }

        if ($startDate && $endDate) {
            $hsWhere .= " AND created_at >= ? AND created_at <= ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }

        $sqlHS = "SELECT 
            COUNT(*) as total,
            COUNT(*) FILTER (WHERE trang_thai ILIKE '%Đã duyệt%') as approved,
            COUNT(*) FILTER (WHERE trang_thai ILIKE '%Yêu cầu sửa%') as require_edit,
            COUNT(*) FILTER (WHERE yeu_cau_chinh_sua = TRUE) as edit_requests
            FROM ho_so_xet_tuyen $hsWhere";

        try {
            $stmt = $this->db->prepare($sqlHS);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $stats['total'] = (int)$result['total'];
                $stats['approved'] = (int)$result['approved'];
                $stats['require_edit'] = (int)$result['require_edit'];
                $stats['edit_requests'] = (int)$result['edit_requests'];
                // Pending = everything that is NOT approved and NOT require_edit
                $stats['pending'] = $stats['total'] - $stats['approved'] - $stats['require_edit'];
            }
        } catch (\PDOException $e) {
            error_log("Error in getStats (Application query): " . $e->getMessage());
        }

        // 2. Get Ghost Candidates (Accounts without applications)
        $ghostWhere = " WHERE NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = t.so_cccd) AND t.deleted_at IS NULL";
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
