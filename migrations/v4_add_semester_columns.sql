-- Thêm các cột điểm chi tiết HK1, HK2 cho các môn còn thiếu và các trường tổng kết
ALTER TABLE public.ket_qua_hoc_tap
ADD COLUMN IF NOT EXISTS diem_tin_hoc_hk1 FLOAT,
ADD COLUMN IF NOT EXISTS diem_tin_hoc_hk2 FLOAT,
ADD COLUMN IF NOT EXISTS diem_cong_nghe_hk1 FLOAT,
ADD COLUMN IF NOT EXISTS diem_cong_nghe_hk2 FLOAT,

-- Thêm các cột tổng kết theo kỳ (Học lực, Hạnh kiểm, ĐTB)
ADD COLUMN IF NOT EXISTS diem_tb_hk1 FLOAT,
ADD COLUMN IF NOT EXISTS diem_tb_hk2 FLOAT,
ADD COLUMN IF NOT EXISTS hoc_luc_hk1 TEXT,
ADD COLUMN IF NOT EXISTS hoc_luc_hk2 TEXT,
ADD COLUMN IF NOT EXISTS hanh_kiem_hk1 TEXT,
ADD COLUMN IF NOT EXISTS hanh_kiem_hk2 TEXT;
