-- =============================================
-- Migration v10: Fix Threshold Values per Bộ GD&ĐT Regulations
-- 1. Sửa giá trị ngưỡng điểm THPT cho Sư phạm, Điều dưỡng, SP đặc thù
-- 2. Thêm cột ngưỡng điểm xét tốt nghiệp (nguong_diem_xtn)
-- 3. Thêm cột ngưỡng ĐTB học bạ (nguong_diem_hocba) cho ngành ngoài SP
-- =============================================

-- 1. Thêm cột mới cho điều kiện OR (điểm xét tốt nghiệp THPT)
ALTER TABLE public.dm_nganh ADD COLUMN IF NOT EXISTS nguong_diem_xtn FLOAT;

-- 2. Thêm cột ngưỡng ĐTB học bạ cho xét tuyển phương thức học bạ
ALTER TABLE public.dm_nganh ADD COLUMN IF NOT EXISTS nguong_diem_hocba FLOAT;

-- 3. Sửa ngưỡng Sư phạm: 20.00 → 18.00 (đúng quy định Bộ GD&ĐT)
-- Thêm ngưỡng điểm xét TN: 8.50
UPDATE dm_nganh SET 
    nguong_diem_thpt = 18.00,
    nguong_diem_xtn = 8.50
WHERE nhom_nganh = 'SuPham';

-- 4. Sửa ngưỡng Điều dưỡng: 20.00 → 16.50 (đúng quy định Bộ GD&ĐT)
-- Thêm ngưỡng điểm xét TN: 6.50
UPDATE dm_nganh SET 
    nguong_diem_thpt = 16.50,
    nguong_diem_xtn = 6.50
WHERE ma_nganh = '7720301';

-- 5. Sửa ngưỡng SP đặc thù: 18.00 → 16.50 (đúng quy định Bộ GD&ĐT)
-- Thêm ngưỡng điểm xét TN: 6.50
UPDATE dm_nganh SET 
    nguong_diem_thpt = 16.50,
    nguong_diem_xtn = 6.50
WHERE nhom_nganh = 'SuPhamDacThu';

-- 6. Set ngưỡng ĐTB học bạ mặc định cho tất cả ngành (18.0 theo quy định)
-- Admin có thể tùy chỉnh cho từng ngành riêng sau
UPDATE dm_nganh SET nguong_diem_hocba = 18.00 
WHERE nhom_nganh = 'Khac' AND nguong_diem_hocba IS NULL;

-- 7. Set ngưỡng điểm THPT mặc định cho ngành Khác (15.00 theo quy định TS02 khoản 3)
UPDATE dm_nganh SET nguong_diem_thpt = 15.00 
WHERE nhom_nganh = 'Khac' AND nguong_diem_thpt IS NULL;
