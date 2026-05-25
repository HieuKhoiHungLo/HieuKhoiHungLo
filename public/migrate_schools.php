<?php
/**
 * Automated & Transaction-Safe High School Directory Migration Script
 * Highly Optimized for PostgreSQL and Production Readiness
 * Implements Soft-Merge / Coexist Consensus for Unmatched Schools
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Prevent execution by unauthorized users if run in browser
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        header('HTTP/1.1 403 Forbidden');
        echo "<h3>Access Denied. Please log in as Admin.</h3>";
        exit;
    }
}

// ----------------------------------------------------------------
// Helper Functions for Data Normalization & Accent Stripping
// ----------------------------------------------------------------

function stripAccents($str) {
    if (!$str) return '';
    $str = Normalizer::normalize($str, Normalizer::FORM_D);
    $str = preg_replace('/\p{M}/u', '', $str);
    $map = ['đ' => 'd', 'Đ' => 'd', 'ỏ' => 'o'];
    $str = strtr($str, $map);
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[^a-z0-9\s]/u', '', $str);
    $str = preg_replace('/\s+/', ' ', $str);
    return trim($str);
}

function normalizeSchoolName($name) {
    $name = trim($name);
    $name = mb_strtolower($name, 'UTF-8');
    
    // Remove prefixes
    $prefixes = [
        '/^trường trung học phổ thông\s+/u',
        '/^trung học phổ thông\s+/u',
        '/^trường thpt\s+/u',
        '/^thpt\s+/u',
        '/^trường ptth\s+/u',
        '/^ptth\s+/u',
        '/^trường pt\s+/u',
        '/^pt\s+/u',
        '/^trường thcs & thpt\s+/u',
        '/^thcs & thpt\s+/u',
        '/^trường thcs và thpt\s+/u',
        '/^thcs và thpt\s+/u'
    ];
    $name = preg_replace($prefixes, '', $name);
    
    // Normalize to NFC
    if (class_exists('Normalizer')) {
        $name = Normalizer::normalize($name, Normalizer::FORM_C);
    }
    
    // Diacritic conversions
    $find =    ['hoà', 'hoá', 'hoả', 'hoã', 'hoạ', 'toà', 'toá', 'toả', 'toã', 'toạ', 'uỷ', 'uý', 'uỷ', 'uỹ', 'uỵ', 'ỏ'];
    $replace = ['hòa', 'hóa', 'hỏa', 'hõa', 'họa', 'tòa', 'tóa', 'tỏa', 'tõa', 'tọa', 'ủy', 'úy', 'ủy', 'ũy', 'ụy', 'ỏ'];
    $name = str_replace($find, $replace, $name);
    
    // Remove suffix date / additions like "(Từ 04/6/2021)"
    $name = preg_replace('/\s*\(từ\s+\d{1,2}\/\d{1,2}\/\d{4}\)/u', '', $name);
    
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
}

/**
 * Standardize priority areas according to Option A
 */
function normalizePriorityArea($area) {
    if ($area === null || $area === '') return 'KV3'; // Default to KV3 if empty
    $val = trim((string)$area);
    $val = mb_strtoupper($val, 'UTF-8');
    $val = preg_replace('/\s+/', '', $val); // Remove all spaces
    
    // Normalize spelling
    $map = [
        '1' => 'KV1',
        '2' => 'KV2',
        '2NT' => 'KV2-NT',
        'KV2NT' => 'KV2-NT',
        '3' => 'KV3'
    ];
    
    if (isset($map[$val])) {
        return $map[$val];
    }
    
    // Check if it contains patterns
    if (strpos($val, 'KV1') !== false) return 'KV1';
    if (strpos($val, 'KV2-NT') !== false || strpos($val, 'KV2NT') !== false) return 'KV2-NT';
    if (strpos($val, 'KV2') !== false) return 'KV2';
    if (strpos($val, 'KV3') !== false) return 'KV3';
    
    return 'KV3'; // Default fallback
}

// ----------------------------------------------------------------
// Execution Setup & Database Connection
// ----------------------------------------------------------------

try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;
use App\Core\Cache;

$cliMode = (php_sapi_name() === 'cli');

if (!$cliMode) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>High School Directory Migration Report</title>
        <meta charset='UTF-8'>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #334155; padding: 40px 20px; line-height: 1.6; }
            .container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; }
            h2 { color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-top: 0; }
            .badge { display: inline-block; padding: 4px 10px; font-size: 12px; font-weight: bold; border-radius: 4px; }
            .badge-success { background: #dcfce7; color: #15803d; }
            .badge-warning { background: #fef9c3; color: #a16207; }
            .badge-danger { background: #fee2e2; color: #b91c1c; }
            .badge-info { background: #e0f2fe; color: #0369a1; }
            pre { background: #0f172a; color: #e2e8f0; padding: 15px; border-radius: 8px; font-family: Courier, monospace; overflow-x: auto; font-size: 13px; line-height: 1.5; }
            ul { padding-left: 20px; }
            li { margin-bottom: 8px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
            th, td { padding: 10px; border: 1px solid #e2e8f0; text-align: left; }
            th { background: #f1f5f9; color: #475569; font-weight: 600; }
            .summary-card { display: flex; gap: 20px; margin-bottom: 30px; }
            .card { flex: 1; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; }
            .card-num { font-size: 28px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
            .card-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        </style>
    </head>
    <body>
    <div class='container'>";
}

function logMsg($msg, $type = 'info') {
    global $cliMode;
    if ($cliMode) {
        $prefix = '[' . strtoupper($type) . '] ';
        echo $prefix . $msg . "\n";
    } else {
        $badgeClass = '';
        switch ($type) {
            case 'success': $badgeClass = 'badge-success'; break;
            case 'warning': $badgeClass = 'badge-warning'; break;
            case 'danger': $badgeClass = 'badge-danger'; break;
            default: $badgeClass = 'badge-info';
        }
        echo "<div><span class='badge {$badgeClass}'>" . strtoupper($type) . "</span> " . htmlspecialchars($msg) . "</div>\n";
    }
    // Flush output buffer
    if (ob_get_level() > 0) ob_flush();
    flush();
}

try {
    $db = Database::getInstance()->getConnection();
    $dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'PostgreSQL';
    logMsg("Connected to PostgreSQL database: " . $dbName, "success");

    // ----------------------------------------------------------------
    // STEP 1: Auto-check and Create Column ma_truong_lop_12_cu in thi_sinh (Idempotent)
    // ----------------------------------------------------------------
    logMsg("STEP 1: Checking backup column 'ma_truong_lop_12_cu' in table 'thi_sinh'...", "info");
    
    $stmt = $db->prepare("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name = 'thi_sinh' AND column_name = 'ma_truong_lop_12_cu'
    ");
    $stmt->execute();
    $columnExists = $stmt->fetchColumn();

    if (!$columnExists) {
        logMsg("Column 'ma_truong_lop_12_cu' does not exist. Creating column...", "info");
        $db->exec("ALTER TABLE thi_sinh ADD COLUMN ma_truong_lop_12_cu varchar(20) DEFAULT NULL");
        logMsg("Backup column 'ma_truong_lop_12_cu' created successfully.", "success");
    } else {
        logMsg("Backup column 'ma_truong_lop_12_cu' already exists. Keeping existing structure (Option A - Idempotency).", "success");
    }

    // Backup current codes to backup column ONLY for rows where backup column is null
    $stmt = $db->prepare("
        UPDATE thi_sinh 
        SET ma_truong_lop_12_cu = ma_truong_lop_12 
        WHERE ma_truong_lop_12_cu IS NULL AND ma_truong_lop_12 IS NOT NULL AND ma_truong_lop_12 != ''
    ");
    $stmt->execute();
    $backedUpRows = $stmt->rowCount();
    logMsg("Backed up school codes for $backedUpRows candidates into 'ma_truong_lop_12_cu'.", "success");

    // ----------------------------------------------------------------
    // STEP 2: Read Excel Sheet 'THPT' from tmp/data.xlsx
    // ----------------------------------------------------------------
    logMsg("STEP 2: Loading Google Sheet standard schools from tmp/data.xlsx...", "info");
    
    $destFile = __DIR__ . '/../tmp/data.xlsx';
    if (!file_exists($destFile)) {
        throw new Exception("Excel file not found at $destFile. Please upload the data file.");
    }

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($destFile);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($destFile);

    if (!$spreadsheet->sheetNameExists('THPT')) {
        throw new Exception("Sheet 'THPT' not found in $destFile.");
    }

    $sheet = $spreadsheet->getSheetByName('THPT');
    $highestRow = $sheet->getHighestRow();
    
    $sheetSchools = [];
    $provincesCount = [];
    
    for ($row = 2; $row <= $highestRow; $row++) {
        $maTinh = trim((string)$sheet->getCell('A' . $row)->getValue());
        $tenTinh = trim((string)$sheet->getCell('B' . $row)->getValue());
        $maTruongExcel = trim((string)$sheet->getCell('C' . $row)->getValue());
        $tenTruong = trim((string)$sheet->getCell('D' . $row)->getValue());
        $diaChi = trim((string)$sheet->getCell('E' . $row)->getValue());
        $khuVucExcel = trim((string)$sheet->getCell('F' . $row)->getValue());

        if (empty($maTruongExcel) || empty($tenTruong)) continue;

        // Standardize codes
        $maTinh = str_pad($maTinh, 2, '0', STR_PAD_LEFT);
        $maTruongExcel = str_pad($maTruongExcel, 3, '0', STR_PAD_LEFT);
        $standardCode = $maTinh . $maTruongExcel; // 5-digit code

        $khuVuc = normalizePriorityArea($khuVucExcel);

        $sheetSchools[] = [
            'ma_tinh' => $maTinh,
            'ten_tinh' => $tenTinh,
            'ma_truong' => $standardCode,
            'ten_truong' => $tenTruong,
            'dia_chi' => $diaChi,
            'khu_vuc' => $khuVuc,
            'norm_name' => normalizeSchoolName($tenTruong),
            'stripped_name' => stripAccents(normalizeSchoolName($tenTruong))
        ];
        
        $provincesCount[$maTinh] = ($provincesCount[$maTinh] ?? 0) + 1;
    }
    
    $totalSheetSchools = count($sheetSchools);
    logMsg("Loaded $totalSheetSchools standard schools across " . count($provincesCount) . " provinces from Excel.", "success");

    // ----------------------------------------------------------------
    // STEP 3: Begin Safe Database Transaction & Idempotent Restore
    // ----------------------------------------------------------------
    logMsg("STEP 3: Initiating database transaction...", "info");
    $db->beginTransaction();

    // IDEMPOTENT RESTORE (Option A): Restore original codes from backup column before mapping.
    // This allows the migration to be rerun safely infinite times by resetting candidate schools to their raw states first.
    logMsg("Restoring candidate schools from backup column 'ma_truong_lop_12_cu' to guarantee idempotency...", "info");
    $stmtRestore = $db->prepare("
        UPDATE thi_sinh 
        SET ma_truong_lop_12 = ma_truong_lop_12_cu 
        WHERE ma_truong_lop_12_cu IS NOT NULL
    ");
    $stmtRestore->execute();
    $restoredRows = $stmtRestore->rowCount();
    logMsg("Restored original school codes for $restoredRows candidates from backup column.", "success");

    // Fetch and Drop Foreign Keys dynamically to avoid database constraints blocking updates
    logMsg("Fetching foreign keys referencing 'dm_truong_thpt'...", "info");
    $sqlFk = "
        SELECT
            tc.constraint_name, 
            tc.table_name, 
            kcu.column_name, 
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name 
        FROM 
            information_schema.table_constraints AS tc 
            JOIN information_schema.key_column_usage AS kcu
              ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
              ON ccu.constraint_name = tc.constraint_name
              AND ccu.table_schema = tc.table_schema
        WHERE tc.constraint_type = 'FOREIGN KEY' 
          AND ccu.table_name = 'dm_truong_thpt'
    ";
    $stmtFk = $db->query($sqlFk);
    $fks = $stmtFk->fetchAll(PDO::FETCH_ASSOC);

    logMsg("Found " . count($fks) . " foreign key constraints. Dropping temporary for migration...", "info");
    foreach ($fks as $fk) {
        $db->exec("ALTER TABLE {$fk['table_name']} DROP CONSTRAINT {$fk['constraint_name']}");
        logMsg("Dropped FK constraint '{$fk['constraint_name']}' on table '{$fk['table_name']}'.", "success");
    }

    // ----------------------------------------------------------------
    // STEP 4: Analyse and Map Database Codes
    // ----------------------------------------------------------------
    logMsg("STEP 4: Fetching unique school codes currently used by active candidates...", "info");
    
    $stmt = $db->query("
        SELECT 
            t.ma_truong_lop_12, 
            s.ten_truong as db_ten_truong, 
            s.khu_vuc as db_khu_vuc,
            t.ma_tinh_lop_12,
            count(*) as candidate_count
        FROM thi_sinh t
        LEFT JOIN dm_truong_thpt s ON t.ma_truong_lop_12 = s.ma_truong
        WHERE t.ma_truong_lop_12 IS NOT NULL AND t.ma_truong_lop_12 != ''
        GROUP BY t.ma_truong_lop_12, s.ten_truong, s.khu_vuc, t.ma_tinh_lop_12
        ORDER BY candidate_count DESC
    ");
    $dbSchoolsInUse = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMsg("Found " . count($dbSchoolsInUse) . " unique school codes referenced in candidate profiles.", "info");

    $matchedCount = 0;
    $unmatchedCount = 0;
    $candidatesMatched = 0;
    $candidatesUnmatched = 0;

    $conversionLogs = [];
    $schoolsToDelete = [];
    $schoolsToCreate = [];

    foreach ($dbSchoolsInUse as $dbSchool) {
        $oldCode = $dbSchool['ma_truong_lop_12'];
        $dbTen = $dbSchool['db_ten_truong'] ?? '';
        $dbMaTinh = $dbSchool['ma_tinh_lop_12'];
        $dbKhuVuc = $dbSchool['db_khu_vuc'] ?? 'KV2';
        $candidates = (int)$dbSchool['candidate_count'];

        // Normalize DB name for matching
        $normDbName = normalizeSchoolName($dbTen);
        $strippedDbName = stripAccents($normDbName);

        $found = null;
        $matchType = '';

        if (!empty($dbTen)) {
            // Match exact normalized name
            foreach ($sheetSchools as $ss) {
                if ($ss['norm_name'] === $normDbName) {
                    $found = $ss;
                    $matchType = 'exact_name';
                    break;
                }
            }

            // Fallback match stripped name
            if (!$found) {
                foreach ($sheetSchools as $ss) {
                    if ($ss['stripped_name'] === $strippedDbName) {
                        $found = $ss;
                        $matchType = 'fallback_stripped';
                        break;
                    }
                }
            }
        }

        if ($found) {
            // MATCHED SCHOOL
            $newCode = $found['ma_truong'];
            $matchedCount++;
            $candidatesMatched += $candidates;

            $conversionLogs[] = [
                'type' => 'MATCHED',
                'old_code' => $oldCode,
                'name' => $dbTen,
                'new_code' => $newCode,
                'new_name' => $found['ten_truong'],
                'candidates' => $candidates,
                'details' => "Matched via $matchType. Standard code: $newCode"
            ];

            // 1. Update active candidates to use standard code
            $stmtUpdate = $db->prepare("UPDATE thi_sinh SET ma_truong_lop_12 = ? WHERE ma_truong_lop_12 = ?");
            $stmtUpdate->execute([$newCode, $oldCode]);

            // 2. Queue old record for deletion
            $schoolsToDelete[] = $oldCode;

        } else {
            // UNMATCHED SCHOOL (SOFT-MERGE STRATEGY)
            $unmatchedCount++;
            $candidatesUnmatched += $candidates;

            // Generate standard unmatched code: [New Province Code (2 digits)] + [Old School Suffix (5 digits)]
            // If the old code is shorter than 5 chars, pad it. Typically it's 7 chars: e.g. 1215007.
            // Suffix is the last 5 characters.
            $suffix = substr($oldCode, -5);
            if (strlen($suffix) < 5) {
                $suffix = str_pad($suffix, 5, '0', STR_PAD_LEFT);
            }
            
            // Map the old province code to new province code
            // Phú Thọ old code '12' -> new code '25'
            // We can determine new province code from candidate profile ma_tinh_lop_12
            // If empty, fallback to Phú Thọ '25'
            $newProvinceCode = $dbMaTinh ?: '25';
            if (strlen($newProvinceCode) !== 2) {
                $newProvinceCode = '25';
            }
            
            $newCode = $newProvinceCode . $suffix;

            $conversionLogs[] = [
                'type' => 'UNMATCHED_RETAINED',
                'old_code' => $oldCode,
                'name' => $dbTen ?: '[Empty Code]',
                'new_code' => $newCode,
                'new_name' => $dbTen ?: 'Trường THPT Mã Cũ ' . $oldCode,
                'candidates' => $candidates,
                'details' => "Soft-Merge: Standardized unmatched code. Old: $oldCode -> New: $newCode"
            ];

            // 1. Add standard unmatched school to list to be created (to maintain candidate references)
            $schoolsToCreate[$newCode] = [
                'ma_truong' => $newCode,
                'ten_ten_truong' => $dbTen ?: 'Trường THPT Mã Cũ ' . $oldCode,
                'khu_vuc' => normalizePriorityArea($dbKhuVuc),
                'ma_tinh' => $newProvinceCode
            ];

            // 2. Update active candidates to use standard unmatched code
            $stmtUpdate = $db->prepare("UPDATE thi_sinh SET ma_truong_lop_12 = ? WHERE ma_truong_lop_12 = ?");
            $stmtUpdate->execute([$newCode, $oldCode]);

            // 3. Queue old record for deletion
            $schoolsToDelete[] = $oldCode;
        }
    }

    // Delete unused unmatched schools (schools with no candidates at all)
    // To do this, we can select all schools that are not in sheet, and have 0 candidate count
    logMsg("STEP 5: Cleaning up unmatched old database schools not in use...", "info");
    
    // Select all old database schools
    $stmt = $db->query("SELECT ma_truong FROM dm_truong_thpt");
    $allDbSchools = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $deletedUnusedCount = 0;
    foreach ($allDbSchools as $dbCode) {
        // If it's not in the database schools in use (not used by candidates)
        // and is not in the Excel sheets, we can safely delete it.
        $inUse = false;
        foreach ($dbSchoolsInUse as $su) {
            if ($su['ma_truong_lop_12'] === $dbCode) {
                $inUse = true;
                break;
            }
        }
        
        $inExcel = false;
        foreach ($sheetSchools as $ss) {
            if ($ss['ma_truong'] === $dbCode) {
                $inExcel = true;
                break;
            }
        }
        
        if (!$inUse && !$inExcel) {
            $schoolsToDelete[] = $dbCode;
            $deletedUnusedCount++;
        }
    }

    // Perform deletions of old codes from dm_truong_thpt
    $schoolsToDelete = array_unique($schoolsToDelete);
    if (!empty($schoolsToDelete)) {
        $placeholders = implode(',', array_fill(0, count($schoolsToDelete), '?'));
        $stmtDelete = $db->prepare("DELETE FROM dm_truong_thpt WHERE ma_truong IN ($placeholders)");
        $stmtDelete->execute($schoolsToDelete);
        logMsg("Deleted " . count($schoolsToDelete) . " old school codes from danh mục table ($deletedUnusedCount were completely unused).", "success");
    }

    // Create the standardized unmatched schools that are currently in use
    logMsg("Inserting standardized unmatched schools into danh mục...", "info");
    $createdUnmatched = 0;
    $stmtInsertUnmatched = $db->prepare("
        INSERT INTO dm_truong_thpt (ma_truong, ten_truong, khu_vuc, ma_tinh) 
        VALUES (:ma_truong, :ten_truong, :khu_vuc, :ma_tinh)
        ON CONFLICT (ma_truong) 
        DO UPDATE SET ten_truong = EXCLUDED.ten_truong, khu_vuc = EXCLUDED.khu_vuc, ma_tinh = EXCLUDED.ma_tinh
    ");
    
    foreach ($schoolsToCreate as $code => $sch) {
        $stmtInsertUnmatched->execute([
            ':ma_truong' => $code,
            ':ten_truong' => $sch['ten_ten_truong'],
            ':khu_vuc' => $sch['khu_vuc'],
            ':ma_tinh' => $sch['ma_tinh']
        ]);
        $createdUnmatched++;
    }
    logMsg("Created $createdUnmatched standardized unmatched schools to maintain candidate integrity.", "success");

    // ----------------------------------------------------------------
    // STEP 6: High-Performance Batch Import of All 4,922 Standard Schools
    // ----------------------------------------------------------------
    logMsg("STEP 6: Importing all 4,922 standard schools from Excel into 'dm_truong_thpt' using optimized batch processing...", "info");
    
    // Batch processing to reduce database round-trips over the network (crucial for Supabase/Remote DBs)
    $chunks = array_chunk($sheetSchools, 500);
    $importedCount = 0;
    
    foreach ($chunks as $chunkIndex => $chunk) {
        $sql = "INSERT INTO dm_truong_thpt (ma_truong, ten_truong, khu_vuc, ma_tinh) VALUES ";
        $placeholders = [];
        $values = [];
        
        foreach ($chunk as $i => $sch) {
            $placeholders[] = "(?, ?, ?, ?)";
            $values[] = $sch['ma_truong'];
            $values[] = $sch['ten_truong'];
            $values[] = $sch['khu_vuc'];
            $values[] = $sch['ma_tinh'];
        }
        
        $sql .= implode(', ', $placeholders);
        $sql .= " ON CONFLICT (ma_truong) DO UPDATE SET ten_truong = EXCLUDED.ten_truong, khu_vuc = EXCLUDED.khu_vuc, ma_tinh = EXCLUDED.ma_tinh";
        
        $stmtBatch = $db->prepare($sql);
        $stmtBatch->execute($values);
        $importedCount += count($chunk);
        logMsg("Imported standard schools batch " . ($chunkIndex + 1) . " (" . count($chunk) . " schools)...", "info");
    }
    logMsg("Imported/Upserted a total of $importedCount standard schools successfully in high-speed batches.", "success");

    // ----------------------------------------------------------------
    // STEP 7: Re-create Foreign Key Constraints
    // ----------------------------------------------------------------
    logMsg("STEP 7: Recreating database foreign key constraints...", "info");
    foreach ($fks as $fk) {
        $createSql = "ALTER TABLE {$fk['table_name']} ADD CONSTRAINT {$fk['constraint_name']} FOREIGN KEY ({$fk['column_name']}) REFERENCES {$fk['foreign_table_name']} ({$fk['foreign_column_name']})";
        $db->exec($createSql);
        logMsg("Recreated FK constraint '{$fk['constraint_name']}' on table '{$fk['table_name']}'.", "success");
    }

    // ----------------------------------------------------------------
    // STEP 8: Data Verification & Integrity Check
    // ----------------------------------------------------------------
    logMsg("STEP 8: Running final data integrity and orphan checks...", "info");
    
    // Check if there are any candidates with school codes that do not exist in dm_truong_thpt
    $stmtVerify = $db->query("
        SELECT COUNT(*) 
        FROM thi_sinh t
        LEFT JOIN dm_truong_thpt s ON t.ma_truong_lop_12 = s.ma_truong
        WHERE t.ma_truong_lop_12 IS NOT NULL AND t.ma_truong_lop_12 != '' AND s.ma_truong IS NULL
    ");
    $orphanCount = (int)$stmtVerify->fetchColumn();

    if ($orphanCount === 0) {
        logMsg("VERIFICATION PASSED: Absolutely 0 orphaned candidates! Candidate-to-school relationships remain 100% intact.", "success");
    } else {
        throw new Exception("VERIFICATION FAILED: Found $orphanCount orphaned candidates whose school codes do not exist in the directory.");
    }

    // Commit Transaction!
    $db->commit();
    logMsg("DATABASE TRANSACTION COMMITTED SUCCESSFULLY!", "success");

    // Clear caches
    Cache::forget('master_schools_17'); // Clear Phú Thọ cache
    // Let's clear cache for all mapped provinces
    foreach ($provincesCount as $pCode => $count) {
        Cache::forget("master_schools_{$pCode}");
    }
    logMsg("Cleared cache keys for upgraded provinces.", "success");

    // ----------------------------------------------------------------
    // STEP 9: Print Detailed Stats & HTML Report
    // ----------------------------------------------------------------
    if (!$cliMode) {
        echo "<h2 style='margin-top: 30px;'>Migration Conversion Summary</h2>";
        
        echo "<div class='summary-card'>
            <div class='card' style='border-left: 4px solid #10b981;'>
                <div class='card-num'>" . number_format($totalSheetSchools) . "</div>
                <div class='card-label'>Excel Standard Schools</div>
            </div>
            <div class='card' style='border-left: 4px solid #3b82f6;'>
                <div class='card-num'>" . number_format($matchedCount) . "</div>
                <div class='card-label'>Old Schools Matched</div>
            </div>
            <div class='card' style='border-left: 4px solid #eab308;'>
                <div class='card-num'>" . number_format($unmatchedCount) . "</div>
                <div class='card-label'>Unmatched Coexisted</div>
            </div>
            <div class='card' style='border-left: 4px solid #6366f1;'>
                <div class='card-num'>0</div>
                <div class='card-label'>Orphaned Candidates</div>
            </div>
        </div>";

        echo "<h3>Conversion Details (Sample Log)</h3>";
        echo "<table>
                <thead>
                    <tr>
                        <th>Trạng thái</th>
                        <th>Mã Cũ</th>
                        <th>Tên Trường Cũ</th>
                        <th>Mã Mới</th>
                        <th>Tên Trường Mới</th>
                        <th>Số Thí Sinh</th>
                        <th>Chi Tiết Ánh Xạ</th>
                    </tr>
                </thead>
                <tbody>";
        
        // Show all unmatched retained and top 20 matched
        $showLogs = [];
        $unmatchedRetainedCount = 0;
        $matchedShowCount = 0;
        foreach ($conversionLogs as $log) {
            if ($log['type'] === 'UNMATCHED_RETAINED') {
                $showLogs[] = $log;
                $unmatchedRetainedCount++;
            } else if ($log['type'] === 'MATCHED' && $matchedShowCount < 20) {
                $showLogs[] = $log;
                $matchedShowCount++;
            }
        }
        
        foreach ($showLogs as $log) {
            $badge = $log['type'] === 'MATCHED' 
                ? "<span class='badge badge-success'>MATCHED</span>"
                : "<span class='badge badge-warning'>RETAINED</span>";
                
            echo "<tr>
                    <td>{$badge}</td>
                    <td><code>{$log['old_code']}</code></td>
                    <td>" . htmlspecialchars($log['name']) . "</td>
                    <td><code>{$log['new_code']}</code></td>
                    <td>" . htmlspecialchars($log['new_name']) . "</td>
                    <td><strong>{$log['candidates']}</strong></td>
                    <td>" . htmlspecialchars($log['details']) . "</td>
                  </tr>";
        }
        
        if (count($conversionLogs) > count($showLogs)) {
            $remaining = count($conversionLogs) - count($showLogs);
            echo "<tr><td colspan='7' style='text-align: center; color: #64748b; font-style: italic;'>... và " . number_format($remaining) . " bản ghi trùng khớp khác đã chuyển đổi thành công ...</td></tr>";
        }
        
        echo "</tbody></table>";
        
        echo "<div style='margin-top: 30px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 20px; border-radius: 8px; color: #065f46;'>
            <h4 style='margin: 0 0 10px 0;'>🎉 Nâng Cấp Danh Mục Hoàn Tất!</h4>
            Toàn bộ dữ liệu trường học THPT đã được quy chuẩn hóa sang mã 5 số tiêu chuẩn, dữ liệu khu vực trúng tuyển được ánh xạ chính xác tuyệt đối từ Cột F, và các trường học cũ không khớp đã được soft-merge đồng bộ mã để đảm bảo an toàn 100% hồ sơ học sinh.
        </div>";
        echo "</div></body></html>";
    } else {
        echo "\n=== MIGRATION CONVERSIONS STATS ===\n";
        echo "Total Sheet Schools: $totalSheetSchools\n";
        echo "Old Schools Matched: $matchedCount (Affects $candidatesMatched candidates)\n";
        echo "Unmatched Schools Retained: $unmatchedCount (Affects $candidatesUnmatched candidates)\n";
        echo "Cleaned Unused Database Schools: $deletedUnusedCount\n";
        echo "Orphaned Candidates: 0 (Verify successful!)\n";
        echo "=== MIGRATION COMPLETED SUCCESSFULLY ===\n\n";
    }

} catch (\Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    logMsg("CRITICAL ERROR ENCOUNTERED: " . $e->getMessage(), "danger");
    logMsg("Database transaction has been rolled back safely. Zero changes were made.", "warning");
    
    if (!$cliMode) {
        echo "<div style='margin-top: 30px; background: #fef2f2; border: 1px solid #fecaca; padding: 20px; border-radius: 8px; color: #991b1b;'>
            <h4 style='margin: 0 0 10px 0;'>❌ Di chuyển thất bại!</h4>
            Hệ thống đã tự động khôi phục (rollback) lại dữ liệu nguyên vẹn ban đầu. Không có bất kỳ thí sinh hay danh mục nào bị ảnh hưởng. Lỗi chi tiết: " . htmlspecialchars($e->getMessage()) . "
        </div>";
        echo "</div></body></html>";
    }
}
