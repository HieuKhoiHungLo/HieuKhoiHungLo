<?php
namespace App\Services;

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdmissionResultService {
    protected $db;
    protected $mailer;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->mailer = new MailerService();
    }

    /**
     * Import thí sinh từ file Excel — tối ưu cho file lớn (batch insert + in-memory dedup)
     */
    public function importFromExcel($filePath, $batchId, $updateExisting = true) {
        $startTime = microtime(true);
        error_log("--- IMPORT START ---");
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        // ── 1. ĐỌC EXCEL: chỉ lấy dữ liệu thô, bỏ qua formula/formatting ──────
        $reader = IOFactory::createReaderForFile($filePath);
        // $reader->setReadDataOnly(true); // Commented out to preserve formatting in output file
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow    = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();

        $data = $sheet->rangeToArray(
            'A1:' . $highestColumn . $highestRow,
            null, true, false, true   // false = không tính formula
        );

        error_log("Step 1 (Load Excel): " . (microtime(true) - $startTime) . "s");
        $stepTime = microtime(true);

        // ── 2. MAP HEADER ────────────────────────────────────────────────────────
        $headers = array_map('trim', array_map('strval', $data[1]));
        $colMap  = [];
        foreach ($headers as $col => $header) {
            $h = $this->normalizeHeader($header);
            $fieldMap = [
                'cccd' => 'cccd',
                'socccd' => 'cccd',
                'hoten' => 'hoten',
                'hovaten' => 'hoten',
                'ngaysinh' => 'ngaysinh',
                'sbd' => 'sbd',
                'sbdthithpt' => 'sbd',
                'sobaodanh' => 'sbd',
                'kv' => 'kv',
                'khuvuc' => 'kv',
                'doituong' => 'doituong',
                'tohop' => 'tohop',
                'dm1' => 'dm1',
                'diemmon1' => 'dm1',
                'dm2' => 'dm2',
                'diemmon2' => 'dm2',
                'dm3' => 'dm3',
                'diemmon3' => 'dm3',
                'diemtohop' => 'diemtohop',
                'diemut' => 'diemut',
                'diemuutien' => 'diemut',
                'utq' => 'utq',
                'utquydoi' => 'utq',
                'diemxt' => 'diemxt',
                'diemxettuyen' => 'diemxt',
                'manganh' => 'manganh',
                'nganh' => 'nganh',
                'tennganh' => 'nganh',
                'nganhtt' => 'nganhtt',
                'tennganhtt' => 'nganhtt',
                'nganhingiaybao' => 'nganhtt',
                'nganhtrungtuyen' => 'nganhtt',
                'khoa' => 'khoa',
                'tenkhoa' => 'khoa',
                'sotk' => 'sotk',
                'sotaikhoan' => 'sotk',
                'nganhang' => 'nganhang',
                'tennganhang' => 'nganhang',
                'sotien' => 'sotien',
                'sotiennop' => 'sotiennop',
                'sotiendanop' => 'sotiennop',
                'noidung' => 'noidung',
                'noidungck' => 'noidung',
                'noidungchuyenkhoan' => 'noidung',
                'email' => 'email',
                'sdt' => 'sdt',
                'dienthoai' => 'sdt',
                'sodienthoai' => 'sdt',
                'ghichu' => 'ghichu',
                'phuongthuc' => 'phuongthuc',
                'sogiaybao' => 'sogiaybao',
                'sogb' => 'sogiaybao',
                'thoigiannhap' => 'thoigiannhap',
                'thoigiannhaphoc' => 'thoigiannhap',
                'kinhphi' => 'kinhphi',
                'noidungthu' => 'kinhphi',
                'xacnhanbo' => 'xacnhanbo',
                'xnbo' => 'xacnhanbo',
                'trangthaixnbo' => 'xacnhanbo',
                'xacnhantruong' => 'xacnhantruong',
                'xntruong' => 'xacnhantruong',
                'trangthaixntruong' => 'xacnhantruong',
                'nhaphoc' => 'nhaphoc',
                'trangthainhaphoc' => 'nhaphoc',
                'nopkinhphi' => 'nopkinhphi',
                'trangthainopkinhphi' => 'nopkinhphi',
                'filegiaybao' => 'filegiaybao',
                'linkgiaybao' => 'filegiaybao',
                'anhgiaybao' => 'filegiaybao',
            ];
            if (isset($fieldMap[$h])) $colMap[$fieldMap[$h]] = $col;
        }

        if (!isset($colMap['cccd'])) {
            throw new \Exception("File Excel thiếu cột 'Số CCCD' bắt buộc.");
        }

        // ── 3. PRE-LOAD CCCD đã tồn tại TRONG ĐỢT NÀY (session_id = $batchId) ──
        $existStmt = $this->db->prepare(
            "SELECT so_cccd FROM ket_qua_trung_tuyen WHERE session_id = ?"
        );
        $existStmt->execute([$batchId]);
        $existingCCCDs = array_flip($existStmt->fetchAll(\PDO::FETCH_COLUMN));

        // ── 4. CHUẨN BỊ DỮ LIỆU ──────────────────────────────────────────────
        $parseFloat = function($val) {
            $val = str_replace(',', '.', trim((string)($val ?? '0')));
            return is_numeric($val) ? (float)$val : 0;
        };

        $parseBool = function($val) {
            if ($val === null || $val === '') return null;
            $str = mb_strtolower(trim((string)$val));
            return in_array($str, ['1', 'true', 'x', 'có', 'co', 'đã xác nhận', 'da xac nhan', 'rồi', 'roi', 'đã nhập học', 'da nhap hoc', 'đã nộp', 'da nop']);
        };

        $insertRows = [];
        $updateRows = [];
        $nhapHocRows = [];
        $ignored    = 0;
        $seenInFile = [];
        $rowStatuses = []; // Track status of each row for download result Excel

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $data[$row] ?? [];

            $cccd  = trim(isset($colMap['cccd']) ? ($rowData[$colMap['cccd']] ?? '') : '');
            if (empty($cccd)) { 
                $ignored++; 
                $rowStatuses[$row] = ['status' => 'Bỏ qua', 'detail' => 'Thiếu Số CCCD'];
                continue; 
            }

            $email = trim(isset($colMap['email']) ? ($rowData[$colMap['email']] ?? '') : '');
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = $cccd . '@student.hvu.edu.vn';
            }

            if (isset($seenInFile[$cccd])) { 
                $ignored++; 
                $rowStatuses[$row] = ['status' => 'Bỏ qua', 'detail' => 'Số CCCD bị trùng lặp trong file Excel'];
                continue; 
            }
            $seenInFile[$cccd] = true;

            $soTienStr = isset($colMap['sotien']) ? preg_replace('/[^0-9]/', '', strval($rowData[$colMap['sotien']] ?? '')) : '';
            $soTien    = $soTienStr ? (int)$soTienStr : 0;

            $record = [
                'batch_id'       => $batchId,
                'cccd'           => $cccd,
                'hoten'          => trim(isset($colMap['hoten']) ? ($rowData[$colMap['hoten']] ?? '') : ''),
                'ngaysinh'       => trim(isset($colMap['ngaysinh']) ? ($rowData[$colMap['ngaysinh']] ?? '') : ''),
                'sbd'            => trim(isset($colMap['sbd']) ? ($rowData[$colMap['sbd']] ?? '') : ''),
                'kv'             => trim(isset($colMap['kv']) ? ($rowData[$colMap['kv']] ?? '') : ''),
                'doituong'       => trim(isset($colMap['doituong']) ? ($rowData[$colMap['doituong']] ?? '') : ''),
                'tohop'          => trim(isset($colMap['tohop']) ? ($rowData[$colMap['tohop']] ?? '') : ''),
                'dm1'            => $parseFloat(isset($colMap['dm1']) ? ($rowData[$colMap['dm1']] ?? null) : null),
                'dm2'            => $parseFloat(isset($colMap['dm2']) ? ($rowData[$colMap['dm2']] ?? null) : null),
                'dm3'            => $parseFloat(isset($colMap['dm3']) ? ($rowData[$colMap['dm3']] ?? null) : null),
                'diemtohop'      => $parseFloat(isset($colMap['diemtohop']) ? ($rowData[$colMap['diemtohop']] ?? null) : null),
                'diemut'         => $parseFloat(isset($colMap['diemut']) ? ($rowData[$colMap['diemut']] ?? null) : null),
                'utq'            => $parseFloat(isset($colMap['utq']) ? ($rowData[$colMap['utq']] ?? null) : null),
                'diemxt'         => $parseFloat(isset($colMap['diemxt']) ? ($rowData[$colMap['diemxt']] ?? null) : null),
                'manganh'        => trim(isset($colMap['manganh']) ? ($rowData[$colMap['manganh']] ?? '') : ''),
                'nganh'          => trim(isset($colMap['nganh']) ? ($rowData[$colMap['nganh']] ?? '') : ''),
                'phuongthuc'     => trim(isset($colMap['phuongthuc']) ? ($rowData[$colMap['phuongthuc']] ?? '') : ''),
                'sotk'           => str_replace(' ', '', trim(isset($colMap['sotk']) ? ($rowData[$colMap['sotk']] ?? '') : '')),
                'nganhang'       => trim(isset($colMap['nganhang']) ? ($rowData[$colMap['nganhang']] ?? '') : ''),
                'sotien'         => $soTien,
                'noidung'        => trim(isset($colMap['noidung']) ? ($rowData[$colMap['noidung']] ?? '') : ''),
                'email'          => $email,
                'sdt'            => trim(isset($colMap['sdt']) ? ($rowData[$colMap['sdt']] ?? '') : ''),
                'ghichu'         => trim(isset($colMap['ghichu']) ? ($rowData[$colMap['ghichu']] ?? '') : ''),
                'sogiaybao'      => trim(isset($colMap['sogiaybao']) ? ($rowData[$colMap['sogiaybao']] ?? '') : ''),
                'thoigiannhap'   => trim(isset($colMap['thoigiannhap']) ? ($rowData[$colMap['thoigiannhap']] ?? '') : ''),
                'nganhtt'        => trim(isset($colMap['nganhtt']) ? ($rowData[$colMap['nganhtt']] ?? '') : ''),
                'khoa'           => trim(isset($colMap['khoa']) ? ($rowData[$colMap['khoa']] ?? '') : ''),
                'kinhphi'        => trim(isset($colMap['kinhphi']) ? ($rowData[$colMap['kinhphi']] ?? '') : ''),
                'xacnhanbo'      => ($xBo = $parseBool(isset($colMap['xacnhanbo']) ? ($rowData[$colMap['xacnhanbo']] ?? null) : null)) !== null ? ($xBo ? 1 : 0) : null,
                'xacnhantruong'  => ($xTr = $parseBool(isset($colMap['xacnhantruong']) ? ($rowData[$colMap['xacnhantruong']] ?? null) : null)) !== null ? ($xTr ? 1 : 0) : null,
                'filegiaybao'    => trim(isset($colMap['filegiaybao']) ? ($rowData[$colMap['filegiaybao']] ?? '') : ''),
            ];

            // Capture enrollment status if present
            $nhStatus = $parseBool(isset($colMap['nhaphoc']) ? ($rowData[$colMap['nhaphoc']] ?? null) : null);
            $nopFee   = $parseBool(isset($colMap['nopkinhphi']) ? ($rowData[$colMap['nopkinhphi']] ?? null) : null);
            $soTienNopStr = isset($colMap['sotiennop']) ? preg_replace('/[^0-9]/', '', strval($rowData[$colMap['sotiennop']] ?? '')) : '';
            $soTienNop    = $soTienNopStr ? (int)$soTienNopStr : null;

            if ($nhStatus !== null || $nopFee !== null || $soTienNop !== null) {
                $nhapHocRows[] = [
                    'session_id' => $batchId,
                    'so_cccd'    => $cccd,
                    'trang_thai' => $nhStatus ? 'da_nhap_hoc' : ($nhStatus === false ? 'chua_nhap_hoc' : null),
                    'da_nop'     => $nopFee,
                    'so_tien'    => $soTienNop
                ];
            }

            if (isset($existingCCCDs[$cccd])) {
                if ($updateExisting) {
                    $record['excel_row_num'] = $row;
                    $updateRows[] = $record;
                } else {
                    $ignored++;
                    $rowStatuses[$row] = ['status' => 'Bỏ qua', 'detail' => 'Thí sinh đã tồn tại trong đợt (Không ghi đè)'];
                }
            } else {
                $insertRows[] = [
                    'row_num' => $row,
                    'values'  => array_values($record)
                ];
            }
        }

        error_log("Step 2-4 (Process array): " . (microtime(true) - $stepTime) . "s");
        $stepTime = microtime(true);

        // ── 5. BATCH INSERT & UPDATE — kiểm soát đúng đợt tuyển sinh (session_id) ──
        $imported = 0;
        $updated  = 0;

        $this->db->beginTransaction();
        try {
            // 5a. Insert thí sinh mới vào đợt
            if (!empty($insertRows)) {
                $batchSize = 500;
                $colCount  = 33;
                $baseSql   = "INSERT INTO ket_qua_trung_tuyen (
                    session_id, so_cccd, ho_ten, ngay_sinh, sbd, khu_vuc, doi_tuong, to_hop,
                    diem_mon_1, diem_mon_2, diem_mon_3, diem_to_hop, diem_ut, ut_quy_doi,
                    diem_xt, ma_nganh, ten_nganh, phuong_thuc,
                    so_tai_khoan, ngan_hang, so_tien, noi_dung_ck,
                    email, sdt, ghi_chu,
                    so_giay_bao, thoi_gian_nhap, nganh_tt, ten_khoa, kinh_phi, xac_nhan_bo, xac_nhan_truong, file_giay_bao
                ) VALUES ";

                $chunks = array_chunk($insertRows, $batchSize);
                foreach ($chunks as $chunk) {
                    $placeholders = array_fill(0, $colCount, '?');
                    $placeholders[30] = "NULLIF(?::text, '')::boolean"; // xac_nhan_bo
                    $placeholders[31] = "NULLIF(?::text, '')::boolean"; // xac_nhan_truong
                    $placeholderRow = '(' . implode(',', $placeholders) . ')';
                    $sql = $baseSql . implode(',', array_fill(0, count($chunk), $placeholderRow));
                    
                    $flatValues = [];
                    foreach ($chunk as $item) {
                        $flatValues[] = $item['values'];
                    }
                    $flat = array_merge(...$flatValues);

                    $this->db->prepare($sql)->execute($flat);
                    $imported += count($chunk);
                    foreach ($chunk as $item) {
                        $rowStatuses[$item['row_num']] = ['status' => 'Thêm mới', 'detail' => 'Thêm mới thành công'];
                    }
                }
            }

            // 5b. Update thí sinh đã có trong đúng đợt tuyển sinh (session_id)
            if (!empty($updateRows)) {
                // Create temp table for updates
                $this->db->exec("
                    CREATE TEMP TABLE temp_update_results (
                        so_cccd varchar,
                        so_giay_bao varchar,
                        thoi_gian_nhap varchar,
                        nganh_tt varchar,
                        ten_khoa varchar,
                        kinh_phi varchar,
                        file_giay_bao varchar,
                        so_tai_khoan varchar,
                        ngan_hang varchar,
                        so_tien integer,
                        noi_dung_ck varchar,
                        email varchar,
                        sdt varchar,
                        ghi_chu varchar,
                        ho_ten varchar,
                        ngay_sinh varchar,
                        phuong_thuc varchar,
                        xac_nhan_bo boolean,
                        xac_nhan_truong boolean
                    ) ON COMMIT DROP
                ");

                // Insert update rows into temp table in chunks
                $updateChunkSize = 500;
                $updateColCount = 19;
                $updateBaseSql = "INSERT INTO temp_update_results (
                    so_cccd, so_giay_bao, thoi_gian_nhap, nganh_tt, ten_khoa, kinh_phi, file_giay_bao,
                    so_tai_khoan, ngan_hang, so_tien, noi_dung_ck, email, sdt, ghi_chu, ho_ten, ngay_sinh,
                    phuong_thuc, xac_nhan_bo, xac_nhan_truong
                ) VALUES ";

                $updateChunks = array_chunk($updateRows, $updateChunkSize);
                foreach ($updateChunks as $chunk) {
                    $placeholders = array_fill(0, $updateColCount, '?');
                    $placeholders[17] = "NULLIF(?::text, '')::boolean"; // xac_nhan_bo
                    $placeholders[18] = "NULLIF(?::text, '')::boolean"; // xac_nhan_truong
                    $placeholderRow = '(' . implode(',', $placeholders) . ')';
                    $sql = $updateBaseSql . implode(',', array_fill(0, count($chunk), $placeholderRow));
                    
                    $flatValues = [];
                    foreach ($chunk as $r) {
                        $flatValues[] = [
                            $r['cccd'],
                            $r['sogiaybao'],
                            $r['thoigiannhap'],
                            $r['nganhtt'],
                            $r['khoa'],
                            $r['kinhphi'],
                            $r['filegiaybao'],
                            $r['sotk'],
                            $r['nganhang'],
                            $r['sotien'],
                            $r['noidung'],
                            $r['email'],
                            $r['sdt'],
                            $r['ghichu'],
                            $r['hoten'],
                            $r['ngaysinh'],
                            $r['phuongthuc'],
                            $r['xacnhanbo'] !== null ? ($r['xacnhanbo'] ? true : false) : null,
                            $r['xacnhantruong'] !== null ? ($r['xacnhantruong'] ? true : false) : null,
                        ];
                    }
                    $flat = array_merge(...$flatValues);
                    $this->db->prepare($sql)->execute($flat);
                }

                // Execute the single bulk update query joining on temp table
                $bulkUpdateSql = "
                    UPDATE ket_qua_trung_tuyen AS k
                    SET
                        so_giay_bao     = CASE WHEN tmp.so_giay_bao != '' THEN tmp.so_giay_bao ELSE k.so_giay_bao END,
                        thoi_gian_nhap  = CASE WHEN tmp.thoi_gian_nhap != '' THEN tmp.thoi_gian_nhap ELSE k.thoi_gian_nhap END,
                        nganh_tt        = CASE WHEN tmp.nganh_tt != '' THEN tmp.nganh_tt ELSE k.nganh_tt END,
                        ten_khoa        = CASE WHEN tmp.ten_khoa != '' THEN tmp.ten_khoa ELSE k.ten_khoa END,
                        kinh_phi        = CASE WHEN tmp.kinh_phi != '' THEN tmp.kinh_phi ELSE k.kinh_phi END,
                        file_giay_bao   = CASE WHEN tmp.file_giay_bao != '' THEN tmp.file_giay_bao ELSE k.file_giay_bao END,
                        so_tai_khoan    = CASE WHEN tmp.so_tai_khoan != '' THEN tmp.so_tai_khoan ELSE k.so_tai_khoan END,
                        ngan_hang       = CASE WHEN tmp.ngan_hang != '' THEN tmp.ngan_hang ELSE k.ngan_hang END,
                        so_tien         = CASE WHEN tmp.so_tien > 0 THEN tmp.so_tien ELSE k.so_tien END,
                        noi_dung_ck     = CASE WHEN tmp.noi_dung_ck != '' THEN tmp.noi_dung_ck ELSE k.noi_dung_ck END,
                        email           = CASE WHEN tmp.email != '' THEN tmp.email ELSE k.email END,
                        sdt             = CASE WHEN tmp.sdt != '' THEN tmp.sdt ELSE k.sdt END,
                        ghi_chu         = CASE WHEN tmp.ghi_chu != '' THEN tmp.ghi_chu ELSE k.ghi_chu END,
                        ho_ten          = CASE WHEN tmp.ho_ten != '' THEN tmp.ho_ten ELSE k.ho_ten END,
                        ngay_sinh       = CASE WHEN tmp.ngay_sinh != '' THEN tmp.ngay_sinh ELSE k.ngay_sinh END,
                        phuong_thuc     = CASE WHEN tmp.phuong_thuc != '' THEN tmp.phuong_thuc ELSE k.phuong_thuc END,
                        xac_nhan_bo     = CASE WHEN tmp.xac_nhan_bo IS NOT NULL THEN tmp.xac_nhan_bo ELSE k.xac_nhan_bo END,
                        xac_nhan_truong = CASE WHEN tmp.xac_nhan_truong IS NOT NULL THEN tmp.xac_nhan_truong ELSE k.xac_nhan_truong END
                    FROM temp_update_results AS tmp
                    WHERE k.session_id = ? AND k.so_cccd = tmp.so_cccd
                ";
                $this->db->prepare($bulkUpdateSql)->execute([$batchId]);
                $updated = count($updateRows);

                // Populate status mapping for the output Excel file
                foreach ($updateRows as $r) {
                    $rowStatuses[$r['excel_row_num']] = ['status' => 'Cập nhật', 'detail' => 'Cập nhật thành công'];
                }
            }

            // 5c. Upsert enrollment and fee payment records in nhap_hoc table
            if (!empty($nhapHocRows)) {
                $nhChunkSize = 500;
                $nhBaseSql = "
                    INSERT INTO nhap_hoc (ket_qua_id, session_id, so_cccd, trang_thai, da_nop_tien, so_tien_da_nop, updated_at)
                    SELECT k.id, v.session_id, v.so_cccd, v.trang_thai, v.da_nop_tien, v.so_tien_da_nop, NOW()
                    FROM (VALUES %s) AS v(session_id, so_cccd, trang_thai, da_nop_tien, so_tien_da_nop)
                    JOIN ket_qua_trung_tuyen k ON k.session_id = v.session_id AND k.so_cccd = v.so_cccd
                    ON CONFLICT (session_id, so_cccd) DO UPDATE SET
                        trang_thai = CASE WHEN EXCLUDED.trang_thai IS NOT NULL THEN EXCLUDED.trang_thai ELSE nhap_hoc.trang_thai END,
                        da_nop_tien = CASE WHEN EXCLUDED.da_nop_tien IS NOT NULL THEN EXCLUDED.da_nop_tien ELSE nhap_hoc.da_nop_tien END,
                        so_tien_da_nop = CASE WHEN EXCLUDED.so_tien_da_nop IS NOT NULL THEN EXCLUDED.so_tien_da_nop ELSE nhap_hoc.so_tien_da_nop END,
                        updated_at = NOW()
                ";
                
                $nhChunks = array_chunk($nhapHocRows, $nhChunkSize);
                foreach ($nhChunks as $chunk) {
                    $placeholders = [];
                    $flat = [];
                    foreach ($chunk as $nh) {
                        $placeholders[] = "(?::integer, ?::varchar, NULLIF(?::text, '')::varchar, NULLIF(?::text, '')::boolean, NULLIF(?::text, '')::numeric)";
                        $flat[] = $nh['session_id'];
                        $flat[] = $nh['so_cccd'];
                        $flat[] = $nh['trang_thai'];
                        $flat[] = $nh['da_nop'] !== null ? ($nh['da_nop'] ? '1' : '0') : null;
                        $flat[] = $nh['so_tien'];
                    }
                    $sql = sprintf($nhBaseSql, implode(', ', $placeholders));
                    $this->db->prepare($sql)->execute($flat);
                }
            }

            $this->db->commit();
            error_log("Step 5 (Database ops): " . (microtime(true) - $stepTime) . "s");
            $stepTime = microtime(true);

            $resultFilePath = $this->writeResultExcel($spreadsheet, $highestColumn, $highestRow, $rowStatuses);
            error_log("Step 6 (Write Excel): " . (microtime(true) - $stepTime) . "s");
            error_log("--- IMPORT TOTAL TIME ---: " . (microtime(true) - $startTime) . "s");
        } catch (\Exception $e) {
            $this->db->rollBack();
            // Fill error statuses for unresolved rows
            for ($row = 2; $row <= $highestRow; $row++) {
                if (!isset($rowStatuses[$row])) {
                    $rowStatuses[$row] = ['status' => 'Lỗi', 'detail' => 'Lỗi import: ' . $e->getMessage()];
                }
            }
            $resultFilePath = $this->writeResultExcel($spreadsheet, $highestColumn, $highestRow, $rowStatuses);
            if (!empty($resultFilePath)) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['last_import_result_file'] = $resultFilePath;
            }
            throw $e;
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'ignored' => $ignored,
            'result_file' => $resultFilePath
        ];
    }

    /**
     * Ghi nhận kết quả xử lý của từng dòng và xuất file Excel kết quả
     */
    private function writeResultExcel($spreadsheet, $highestColumn, $highestRow, $rowStatuses) {
        $sheet = $spreadsheet->getActiveSheet();
        
        $resultCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn) + 1
        );
        $detailCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn) + 2
        );

        $sheet->setCellValue($resultCol . '1', 'Kết quả xử lý');
        $sheet->setCellValue($detailCol . '1', 'Chi tiết');

        // Style header cells
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0066FF']
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle($resultCol . '1')->applyFromArray($headerStyle);
        $sheet->getStyle($detailCol . '1')->applyFromArray($headerStyle);

        $resultData = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $status = $rowStatuses[$row]['status'] ?? 'Bỏ qua';
            $detail = $rowStatuses[$row]['detail'] ?? '';
            $resultData[] = [$status, $detail];
        }
        
        // Write all values at once (extremely fast compared to cell-by-cell)
        if (!empty($resultData)) {
            $sheet->fromArray($resultData, null, $resultCol . '2');
            // Apply bold to the entire result column efficiently
            $sheet->getStyle($resultCol . '2:' . $resultCol . $highestRow)->getFont()->setBold(true);
        }

        // Auto-size the new columns
        $sheet->getColumnDimension($resultCol)->setAutoSize(true);
        $sheet->getColumnDimension($detailCol)->setAutoSize(true);

        $outputFilename = 'ket_qua_import_' . date('Ymd_His') . '.xlsx';
        $outputPath = __DIR__ . '/../../public/uploads/import_results/' . $outputFilename;
        
        // Ensure directory exists
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);

        return 'uploads/import_results/' . $outputFilename;
    }

    /**
     * Lấy danh sách các đợt gửi
     */
    public function getBatches() {
        $stmt = $this->db->query("
            SELECT 
                session_id,
                MIN(created_at) as created_at,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM ket_qua_trung_tuyen
            GROUP BY session_id
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chi tiết thí sinh của 1 đợt
     */
    public function getBatchDetails($batchId, $statusFilter = '') {
        return $this->getCandidates(['session_id' => $batchId, 'status' => $statusFilter]);
    }

    /**
     * Lấy tất cả các mẫu email có sẵn
     */
    public function getTemplates() {
        $stmt = $this->db->query("SELECT id, subject, code FROM email_templates ORDER BY id ASC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách thí sinh với bộ lọc
     */
    public function getCandidates($filters = []) {
        $page = (int)($filters['page'] ?? 1);
        $limit = (int)($filters['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $sql = "FROM ket_qua_trung_tuyen WHERE 1=1";
        $params = [];

        if (!empty($filters['session_id'])) {
            $sql .= " AND session_id = ?";
            $params[] = $filters['session_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['q'])) {
            $sql .= " AND (ho_ten ILIKE ? OR so_cccd ILIKE ? OR email ILIKE ? OR sdt ILIKE ?)";
            $q = "%" . $filters['q'] . "%";
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        // Detailed filters from table header
        if (!empty($filters['f_name'])) {
            $sql .= " AND ho_ten ILIKE ?";
            $params[] = "%" . $filters['f_name'] . "%";
        }
        if (!empty($filters['f_cccd'])) {
            $sql .= " AND so_cccd ILIKE ?";
            $params[] = "%" . $filters['f_cccd'] . "%";
        }
        if (!empty($filters['f_phone'])) {
            $sql .= " AND sdt ILIKE ?";
            $params[] = "%" . $filters['f_phone'] . "%";
        }
        if (!empty($filters['f_email'])) {
            $sql .= " AND email ILIKE ?";
            $params[] = "%" . $filters['f_email'] . "%";
        }
        if (!empty($filters['f_dob'])) {
            $sql .= " AND ngay_sinh ILIKE ?";
            $params[] = "%" . $filters['f_dob'] . "%";
        }
        if (!empty($filters['f_major'])) {
            $sql .= " AND ten_nganh ILIKE ?";
            $params[] = "%" . $filters['f_major'] . "%";
        }

        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) " . $sql);
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalItems / $limit);

        // Get data
        $dataSql = "SELECT * " . $sql . " ORDER BY ma_nganh DESC, diem_xt ASC, id ASC LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($dataSql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'limit' => $limit
            ]
        ];
    }

    /**
     * Đưa các thí sinh được chọn vào queue với template cụ thể
     */
    public function enqueueSelected($ids, $templateId) {
        if (empty($ids)) return 0;

        // Fetch Template
        $tplStmt = $this->db->prepare("SELECT * FROM email_templates WHERE id = ?");
        $tplStmt->execute([$templateId]);
        $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$template) {
            throw new \Exception("Mẫu email không tồn tại.");
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM ket_qua_trung_tuyen WHERE id IN ($placeholders) ORDER BY ma_nganh DESC, diem_xt ASC, id ASC");
        $stmt->execute($ids);
        $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $enqueuedCount = 0;
        foreach ($candidates as $candidate) {
            if (empty($candidate['email'])) continue;

            $subject = $template['subject'] ?? 'Thông báo';
            $body = $this->renderTemplate($template['body'], $candidate);
            
            $this->mailer->enqueue($candidate['email'], $subject, $body, true, 'admission_letter');
            
            $upd = $this->db->prepare("UPDATE ket_qua_trung_tuyen SET status = 'queued' WHERE id = ?");
            $upd->execute([$candidate['id']]);

            $enqueuedCount++;
        }

        return $enqueuedCount;
    }

    /**
     * Xóa các thí sinh được chọn
     */
    public function deleteSelected($ids) {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM ket_qua_trung_tuyen WHERE id IN ($placeholders)");
        return $stmt->execute($ids);
    }

    /**
     * Gắn toàn bộ email đợt này vào queue để gửi dần
     */
    public function enqueueBatch($batchId) {
        $stmt = $this->db->prepare("SELECT * FROM ket_qua_trung_tuyen WHERE session_id = ? AND status IN ('pending', 'failed') ORDER BY ma_nganh DESC, diem_xt ASC, id ASC");
        $stmt->execute([$batchId]);
        $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Fetch Template
        $tplStmt = $this->db->prepare("SELECT * FROM email_templates WHERE code = 'ADMISSION_LETTER'");
        $tplStmt->execute();
        $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$template) {
            throw new \Exception("Chưa cấu hình template email ADMISSION_LETTER");
        }

        $enqueuedCount = 0;
        foreach ($candidates as $candidate) {
            if (empty($candidate['email'])) continue;

            $subject = $template['subject'] ?? 'Thông báo trúng tuyển';
            
            // Render template
            $body = $this->renderTemplate($template['body'], $candidate);
            
            // Queue via MailerService
            $this->mailer->enqueue($candidate['email'], $subject, $body, true, 'admission_letter');
            
            // Cập nhật trạng thái thành queue
            $upd = $this->db->prepare("UPDATE ket_qua_trung_tuyen SET status = 'queued' WHERE id = ?");
            $upd->execute([$candidate['id']]);

            $enqueuedCount++;
        }

        return $enqueuedCount;
    }

    /**
     * Render nội dung thư
     */
    public function renderTemplate($templateHtml, $data) {
        $chiTieu = '';
        $diemNamTruoc = '';
        if (!empty($data['ma_nganh'])) {
            $stmt = $this->db->prepare("SELECT chi_tieu, diem_nam_truoc FROM dm_nganh WHERE ma_nganh = ? LIMIT 1");
            $stmt->execute([$data['ma_nganh']]);
            $nganhInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($nganhInfo) {
                $chiTieu = $nganhInfo['chi_tieu'] !== null ? $nganhInfo['chi_tieu'] : '';
                $diemNamTruoc = $nganhInfo['diem_nam_truoc'] !== null ? number_format((float)$nganhInfo['diem_nam_truoc'], 2, '.', '') : '';
                if ($diemNamTruoc !== '') {
                    $diemNamTruoc = rtrim(rtrim($diemNamTruoc, '0'), '.');
                }
            }
        }

        $replacements = [
            // 1. Thông tin cá nhân thí sinh
            '{{HoTen}}'       => $data['ho_ten'] ?? '',
            '{{HOTEN}}'       => $data['ho_ten'] ?? '',
            '{{name}}'        => $data['ho_ten'] ?? '',
            '{{CCCD}}'        => $data['so_cccd'] ?? '',
            '{{SO_CCCD}}'     => $data['so_cccd'] ?? '',
            '{{NgaySinh}}'    => $data['ngay_sinh'] ?? '',
            '{{NGAYSINH}}'    => $data['ngay_sinh'] ?? '',
            '{{SBD}}'         => $data['sbd'] ?? '',
            '{{Email}}'       => $data['email'] ?? '',
            '{{EMAIL}}'       => $data['email'] ?? '',
            '{{SDT}}'         => $data['sdt'] ?? '',
            '{{KhuVuc}}'      => $data['khu_vuc'] ?? '',
            '{{KHUVUC}}'      => $data['khu_vuc'] ?? '',
            '{{DoiTuong}}'    => $data['doi_tuong'] ?? '',
            '{{DOITUONG}}'    => $data['doi_tuong'] ?? '',

            // 2. Thông tin xét tuyển & điểm
            '{{PhuongThuc}}'  => $data['phuong_thuc'] ?? '',
            '{{PHUONGTHUC}}'  => $data['phuong_thuc'] ?? '',
            '{{ToHop}}'       => $data['to_hop'] ?? '',
            '{{TOHOP}}'       => $data['to_hop'] ?? '',
            '{{DM1}}'         => $data['diem_mon_1'] ?? '',
            '{{DM2}}'         => $data['diem_mon_2'] ?? '',
            '{{DM3}}'         => $data['diem_mon_3'] ?? '',
            '{{DiemToHop}}'   => $data['diem_to_hop'] ?? '',
            '{{DIEMTOHOP}}'   => $data['diem_to_hop'] ?? '',
            '{{DiemUT}}'      => $data['diem_ut'] ?? '',
            '{{DIEMUT}}'      => $data['diem_ut'] ?? '',
            '{{UTQ}}'         => $data['ut_quy_doi'] ?? '',
            '{{DiemXT}}'      => rtrim(rtrim(number_format((float)($data['diem_xt'] ?? 0), 3, '.', ''), '0'), '.'),
            '{{DIEMXT}}'      => rtrim(rtrim(number_format((float)($data['diem_xt'] ?? 0), 3, '.', ''), '0'), '.'),
            '{{Nganh}}'       => $data['ten_nganh'] ?? '',
            '{{NGANH}}'       => $data['ten_nganh'] ?? '',
            '{{major}}'       => $data['ten_nganh'] ?? '',
            '{{MaNganh}}'     => $data['ma_nganh'] ?? '',
            '{{MANGANH}}'     => $data['ma_nganh'] ?? '',
            '{{ChiTieu}}'     => $chiTieu,
            '{{CHITIEU}}'     => $chiTieu,
            '{{DiemNamTruoc}}'=> $diemNamTruoc,
            '{{XepHang}}'     => $data['ghi_chu'] ?? '',
            '{{GhiChu}}'      => $data['ghi_chu'] ?? '',

            // 3. Thông tin Nhập học & Giấy báo trúng tuyển
            '{{SoGB}}'        => $data['so_giay_bao'] ?? '',
            '{{SOGIAYBAO}}'   => $data['so_giay_bao'] ?? '',
            '{{SOGB}}'        => $data['so_giay_bao'] ?? '',
            '{{ThoiGianNhap}}'=> $data['thoi_gian_nhap'] ?? '',
            '{{THOIGIANNHAP}}'=> $data['thoi_gian_nhap'] ?? '',
            '{{NganhTT}}'     => !empty($data['nganh_tt']) ? $data['nganh_tt'] : ($data['ten_nganh'] ?? ''),
            '{{NGANH_TT}}'    => !empty($data['nganh_tt']) ? $data['nganh_tt'] : ($data['ten_nganh'] ?? ''),
            '{{Khoa}}'        => $data['ten_khoa'] ?? '',
            '{{KHOA}}'        => $data['ten_khoa'] ?? '',
            '{{KinhPhi}}'     => $data['kinh_phi'] ?? '',
            '{{KINHPHI}}'     => $data['kinh_phi'] ?? '',
            '{{FileGiayBao}}' => $data['file_giay_bao'] ?? '',
            '{{LINKGIAYBAO}}' => $data['file_giay_bao'] ?? '',

            // 4. Thông tin Học phí & Ngân hàng
            '{{SOTK}}'        => $data['so_tai_khoan'] ?? '',
            '{{SOTAIKHOAN}}'  => $data['so_tai_khoan'] ?? '',
            '{{NGANHANG}}'    => $data['ngan_hang'] ?? '',
            '{{SoTien}}'      => number_format((float)($data['so_tien'] ?? 0), 0, ',', '.'),
            '{{SOTIEN}}'      => number_format((float)($data['so_tien'] ?? 0), 0, ',', '.'),
            '{{NOIDUNG}}'     => $data['noi_dung_ck'] ?? '',
            '{{NOIDUNGCK}}'   => $data['noi_dung_ck'] ?? '',
        ];

        // Tạo QR Code urls
        $bankName = strtolower(str_replace(' ', '', $data['ngan_hang'] ?? ''));
        $accountNum = $data['so_tai_khoan'] ?? '';
        $amount = $data['so_tien'] ?? 0;
        $content = urlencode(trim($data['noi_dung_ck'] ?? ''));

        if ($bankName && $accountNum) {
            $qrVietQR = '<img src="https://img.vietqr.io/image/' . $bankName . '-' . $accountNum . '-compact2.jpg?amount=' . $amount . '&addInfo=' . $content . '" alt="QR Thanh Toan" style="max-width: 250px; height: auto; border: 1px solid #eee; border-radius: 5px; display: inline-block;" />';
        } else {
            $qrVietQR = '<p style="color:#666; font-style:italic;">(Chưa có thông tin chuyển khoản)</p>';
        }

        $cccd = urlencode($data['so_cccd'] ?? '');
        if ($cccd) {
            $qrCCCD = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . $cccd . '" alt="QR CCCD" style="max-width: 200px; height:auto; border:1px solid #ccc; border-radius:6px;" />';
        } else {
            $qrCCCD = '';
        }

        $replacements['{{QR_ThanhToan}}'] = $qrVietQR;
        $replacements['{{QR_CCCD}}'] = $qrCCCD;
        $replacements['{{THANH_TIEN_DO_6_BUOC}}'] = $this->renderStepperHtml($data);

        return strtr($templateHtml, $replacements);
    }

    /**
     * Tính toán trạng thái 6 bước cho thí sinh
     */
    public function calculateEnrollmentSteps($data) {
        $step1 = true; // Đã trúng tuyển
        $step2 = !empty($data['xac_nhan_bo']); // Xác nhận trên Cổng Bộ
        $step3 = !empty($data['xac_nhan_truong']); // Xác nhận tại HVU
        $step4 = !empty($data['nop_kinh_phi']) || (!empty($data['so_tien_nop']) && (float)$data['so_tien_nop'] > 0); // Nộp kinh phí
        $step5 = !empty($data['nhap_hoc']); // Nộp hồ sơ điện tử
        $step6 = !empty($data['nhap_hoc']) && $step4; // Theo dõi trạng thái xử lý hồ sơ thời gian thực

        return [
            1 => ['title' => '1. Trúng tuyển', 'completed' => $step1, 'icon' => 'fa-graduation-cap', 'desc' => 'Đã có tên trong danh sách trúng tuyển'],
            2 => ['title' => '2. Xác nhận Bộ GD&ĐT', 'completed' => $step2, 'icon' => 'fa-building-columns', 'desc' => 'Xác nhận trên thisinh.thitotnghiepthpt.edu.vn'],
            3 => ['title' => '3. Xác nhận HVU', 'completed' => $step3, 'icon' => 'fa-university', 'desc' => 'Xác nhận nhập học Trường ĐH Hùng Vương'],
            4 => ['title' => '4. Nộp học phí', 'completed' => $step4, 'icon' => 'fa-credit-card', 'desc' => 'Chuyển khoản nộp kinh phí nhập học (VietQR)'],
            5 => ['title' => '5. Nộp hồ sơ online', 'completed' => $step5, 'icon' => 'fa-folder-open', 'desc' => 'Tải bản chụp 8 giấy tờ hồ sơ nhập học'],
            6 => ['title' => '6. Theo dõi hồ sơ', 'completed' => $step6, 'icon' => 'fa-clock-rotate-left', 'desc' => 'Nhà trường tiếp nhận & kiểm tra hồ sơ'],
        ];
    }

    /**
     * Render Thanh tiến độ 6 bước dạng HTML
     */
    public function renderStepperHtml($data) {
        $steps = $this->calculateEnrollmentSteps($data);
        $html = '<div class="hvu-stepper-container my-6 p-4 md:p-6 bg-slate-900 text-white rounded-2xl shadow-xl font-sans border border-slate-800">';
        $html .= '<div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">';
        $html .= '  <h4 class="text-sm font-bold tracking-wide uppercase text-amber-400 flex items-center gap-2"><i class="fas fa-route"></i> Tiến Trình Nhập Học 6 Bước</h4>';
        $html .= '  <span class="text-xs font-semibold px-2.5 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-lg"><i class="fas fa-sync-alt fa-spin text-[10px] mr-1"></i> Cập nhật thời gian thực</span>';
        $html .= '</div>';
        
        $html .= '<div class="grid grid-cols-2 md:grid-cols-6 gap-3 relative">';
        foreach ($steps as $num => $step) {
            $isDone = $step['completed'];
            $statusClass = $isDone ? 'bg-emerald-950/60 border-emerald-500/50 text-emerald-200' : 'bg-slate-800/60 border-slate-700/80 text-slate-400';
            $badgeIcon   = $isDone ? '<i class="fas fa-check text-emerald-950 text-xs"></i>' : '<span class="text-xs font-bold text-slate-300">' . $num . '</span>';
            $badgeBg     = $isDone ? 'bg-emerald-400' : 'bg-slate-700';
            
            $html .= '<div class="flex flex-col items-center text-center p-3 rounded-xl border transition-all ' . $statusClass . '">';
            $html .= '  <div class="w-7 h-7 rounded-full flex items-center justify-center mb-2 shadow-sm font-bold ' . $badgeBg . '">' . $badgeIcon . '</div>';
            $html .= '  <div class="text-[11px] font-bold leading-tight mb-1">' . htmlspecialchars($step['title']) . '</div>';
            $html .= '  <div class="text-[10px] opacity-75">' . ($isDone ? 'Đã hoàn thành' : 'Chưa thực hiện') . '</div>';
            $html .= '</div>';
        }
        $html .= '</div></div>';
        return $html;
    }
    
    /**
     * Lấy template báo trúng tuyển
     */
    public function getTemplate() {
        $stmt = $this->db->prepare("SELECT * FROM email_templates WHERE code = 'ADMISSION_LETTER'");
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Xóa đợt gửi
     */
    public function deleteBatch($batchId) {
        $stmt = $this->db->prepare("DELETE FROM ket_qua_trung_tuyen WHERE session_id = ?");
        return $stmt->execute([$batchId]);
    }

    /**
     * Xóa toàn bộ dữ liệu
     */
    public function deleteAll() {
        return $this->db->exec("DELETE FROM ket_qua_trung_tuyen");
    }

    /**
     * Gửi test email
     */
    public function sendTestEmail($email, $templateId, $ids = []) {
        $tplStmt = $this->db->prepare("SELECT * FROM email_templates WHERE id = ?");
        $tplStmt->execute([$templateId]);
        $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$template) {
            throw new \Exception("Mẫu email không tồn tại.");
        }

        $subject = $template['subject'] ?? 'Thông báo trúng tuyển';

        if (!empty($ids)) {
            // Gửi email test cho các thí sinh được chọn tới email nhận test
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("SELECT * FROM ket_qua_trung_tuyen WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($candidates as $candidate) {
                $body = $this->renderTemplate($template['body'], $candidate);
                $this->mailer->enqueue($email, $subject, $body, true, 'admission_letter');
            }
            return count($candidates);
        } else {
            // Tạo dữ liệu giả lập cho email test (logic cũ khi không chọn thí sinh nào)
            $fakeData = [
                'ho_ten' => 'Nguyễn Văn Test',
                'so_cccd' => '012345678912',
                'sbd' => 'T12345',
                'ngay_sinh' => '01/01/2005',
                'khu_vuc' => 'KV1',
                'doi_tuong' => 'UT1',
                'to_hop' => 'A00',
                'diem_mon_1' => 8.5,
                'diem_mon_2' => 8.0,
                'diem_mon_3' => 9.0,
                'diem_to_hop' => 25.5,
                'diem_ut' => 1.5,
                'ut_quy_doi' => 1.0,
                'diem_xt' => 28.0,
                'ten_nganh' => 'Công nghệ thông tin',
                'ma_nganh' => '7480201',
                'phuong_thuc' => 'Xét điểm thi THPT',
                'so_tk' => '1903123456789',
                'ngan_hang' => 'Techcombank',
                'so_tien' => 5000000,
                'noi_dung_ck' => 'Nguyễn Văn Test nop hoc phi',
                'email' => $email,
                'sdt' => '0987654321',
                'ghi_chu' => 'Test Ghi chú'
            ];

            $body = $this->renderTemplate($template['body'], $fakeData);
            return $this->mailer->enqueue($email, $subject, $body, true, 'admission_letter') ? 1 : 0;
        }
    }

    /**
     * Gắn toàn bộ email (theo đợt hoặc tất cả) vào queue để gửi dần
     */
    public function enqueueAll($templateId, $batchId = '') {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        $db = $this->db;

        // Fetch Template
        $tplStmt = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
        $tplStmt->execute([$templateId]);
        $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$template) {
            throw new \Exception("Mẫu email không tồn tại.");
        }

        $subject = $template['subject'] ?? 'Thông báo trúng tuyển';

        // Xây dựng điều kiện lọc theo scope
        if (!empty($batchId)) {
            $baseWhere  = "WHERE session_id = ? AND status IN ('pending', 'failed')";
            $baseParams = [$batchId];
        } else {
            $baseWhere  = "WHERE status IN ('pending', 'failed')";
            $baseParams = [];
        }

        // Đếm tổng
        $countStmt = $db->prepare("SELECT COUNT(*) FROM ket_qua_trung_tuyen $baseWhere");
        $countStmt->execute($baseParams);
        $total = (int)$countStmt->fetchColumn();

        if ($total === 0) {
            return 0;
        }

        // === BƯỚC 1: INSERT vào email_queue theo batch nhỏ 50 dòng ===
        // Không kèm UPDATE, không transaction lớn — tránh Supabase statement_timeout
        $batchSize     = 50;
        $offset        = 0;
        $totalEnqueued = 0;
        $insertedIds   = [];

        $insertStmt = $db->prepare(
            "INSERT INTO email_queue (recipient, subject, body, status, category, created_at)
             VALUES (?, ?, ?, 'pending', 'admission_letter', NOW())"
        );

        while ($offset < $total) {
            $params = array_merge($baseParams, [$batchSize, $offset]);
            $fetchStmt = $db->prepare("SELECT * FROM ket_qua_trung_tuyen $baseWhere ORDER BY ma_nganh DESC, diem_xt ASC, id ASC LIMIT ? OFFSET ?");
            $fetchStmt->execute($params);
            $candidates = $fetchStmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($candidates)) break;

            foreach ($candidates as $candidate) {
                if (empty($candidate['email'])) continue;
                $body = $this->renderTemplate($template['body'], $candidate);
                try {
                    $insertStmt->execute([$candidate['email'], $subject, $body]);
                    $insertedIds[] = $candidate['id'];
                    $totalEnqueued++;
                } catch (\Exception $e) {
                    error_log("enqueueAll insert skip id={$candidate['id']}: " . $e->getMessage());
                }
            }

            $offset += $batchSize;
            unset($candidates);
            gc_collect_cycles();
        }

        // === BƯỚC 2: UPDATE status='queued' theo batch nhỏ 50 ID ===
        // Tách hoàn toàn với INSERT để tránh lock contention
        foreach (array_chunk($insertedIds, 50) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            try {
                $db->prepare("UPDATE ket_qua_trung_tuyen SET status = 'queued' WHERE id IN ($placeholders)")
                   ->execute($chunk);
            } catch (\Exception $e) {
                error_log("enqueueAll status update skip: " . $e->getMessage());
            }
        }

        return $totalEnqueued;
    }

    /**
     * Chuẩn hóa tiêu đề: viết thường, xóa dấu Tiếng Việt, xóa tất cả các ký tự không phải chữ/số
     */
    private function normalizeHeader($str) {
        $str = mb_strtolower(trim((string)$str), 'UTF-8');
        $unicode = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        ];
        foreach ($unicode as $nonUnicode => $uni) {
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        }
        return preg_replace('/[^a-z0-9]/', '', $str);
    }
}
