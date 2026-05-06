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
    public static function resolvePhuongThuc(string $ma_hien_tai, array $major): string {
        // Nếu đã là mã TSxx thì trả về luôn (tránh xử lý lại)
        if (strpos($ma_hien_tai, 'TS') === 0) return $ma_hien_tai;

        if ($ma_hien_tai === '100') {
            // TS04: THPT + Năng khiếu
            return !empty($major['co_diem_nangkhieu_thpt']) ? 'TS04' : 'TS01';
        }
        
        if ($ma_hien_tai === '200') {
            // TS03: Học bạ + Chứng chỉ Quốc tế
            if (!empty($major['co_xet_chung_chi'])) {
                return 'TS03';
            }
            // TS05: Học bạ + Năng khiếu
            return !empty($major['co_diem_nangkhieu_hochba']) ? 'TS05' : 'TS02';
        }
        
        return $ma_hien_tai;
    }
}
