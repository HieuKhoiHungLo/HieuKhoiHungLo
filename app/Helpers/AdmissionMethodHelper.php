<?php
namespace App\Helpers;

class AdmissionMethodHelper {
    /**
     * Resolve TS01-TS05 code from internal method code and major flags
     * 
     * @param string $ma_noi_bo Internal method code ('100' or '200')
     * @param array $major Array of major details containing flags
     * @return string Method code TS01-TS05
     */
    public static function resolvePhuongThuc(string $ma_noi_bo, array $major): string {
        if ($ma_noi_bo === '100') {
            return !empty($major['co_diem_nangkhieu_thpt']) ? 'TS04' : 'TS01';
        }
        
        // $ma_noi_bo === '200' or default
        if (!empty($major['co_xet_chung_chi'])) {
            return 'TS03';
        }
        if (!empty($major['co_diem_nangkhieu_hochba'])) {
            return 'TS05';
        }
        
        return 'TS02';
    }
}
