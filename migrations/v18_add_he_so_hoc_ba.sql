-- =============================================
-- Migration v18: Thêm cấu hình hệ số quy đổi Học bạ
-- Cho phép Admin tùy chỉnh hệ số quy đổi điểm Học bạ (mặc định 0.95 = 95%)
-- =============================================

INSERT INTO cau_hinh (key, value, description) 
VALUES ('he_so_hoc_ba', '0.95', 'Hệ số quy đổi điểm học bạ (mặc định 0.95 = 95%). Có thể chỉnh trong Admin.')
ON CONFLICT (key) DO NOTHING;
