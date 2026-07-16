-- =============================================
-- Migration v9: Nâng cấp theo TT06/2026/TT-BGDĐT
-- 1. Thêm cột nhóm ngành + ngưỡng vào dm_nganh
-- 2. Đảm bảo cột ĐTB/Học lực/Hạnh kiểm cả năm trong ket_qua_hoc_tap
-- 3. Xóa trắng dữ liệu thí sinh cũ
-- =============================================

-- 1. Thêm cột phân loại nhóm ngành và ngưỡng đầu vào
ALTER TABLE public.dm_nganh ADD COLUMN IF NOT EXISTS nhom_nganh TEXT DEFAULT 'Khac';
ALTER TABLE public.dm_nganh ADD COLUMN IF NOT EXISTS nguong_hoc_luc TEXT;
ALTER TABLE public.dm_nganh ADD COLUMN IF NOT EXISTS nguong_diem_thpt FLOAT;

-- 2. Đảm bảo cột ĐTB cả năm, học lực cả năm, hạnh kiểm cả năm
ALTER TABLE public.ket_qua_hoc_tap ADD COLUMN IF NOT EXISTS diem_tb_ca_nam FLOAT;
ALTER TABLE public.ket_qua_hoc_tap ADD COLUMN IF NOT EXISTS hoc_luc_ca_nam TEXT;
ALTER TABLE public.ket_qua_hoc_tap ADD COLUMN IF NOT EXISTS hanh_kiem_ca_nam TEXT;

-- 3. XÓA TRẮNG dữ liệu thí sinh cũ (theo yêu cầu)
DO $$ BEGIN
    DELETE FROM diem_chi_tiet;
EXCEPTION WHEN undefined_table THEN NULL;
END $$;

DO $$ BEGIN
    DELETE FROM diem_nang_khieu_ngoai;
EXCEPTION WHEN undefined_table THEN NULL;
END $$;

DO $$ BEGIN
    DELETE FROM nguyen_vong;
EXCEPTION WHEN undefined_table THEN NULL;
END $$;

DO $$ BEGIN
    DELETE FROM ho_so_xet_tuyen;
EXCEPTION WHEN undefined_table THEN NULL;
END $$;

DO $$ BEGIN
    DELETE FROM diem_thi_thpt;
EXCEPTION WHEN undefined_table THEN NULL;
END $$;

DO $$ BEGIN
    DELETE FROM ket_qua_hoc_tap;
EXCEPTION WHEN undefined_table THEN NULL;
END $$;

DO $$ BEGIN
    DELETE FROM chung_chi_ngoai_ngu;
EXCEPTION WHEN undefined_table THEN NULL;
END $$;

-- 4. Cập nhật ngưỡng cho các nhóm ngành (mẫu - admin có thể tùy chỉnh sau)
-- Nhóm Sư phạm: Giỏi + 20 điểm THPT
UPDATE dm_nganh SET nhom_nganh = 'SuPham', nguong_hoc_luc = 'Gioi', nguong_diem_thpt = 18.00
WHERE ma_nganh LIKE '7140%' 
  AND ma_nganh NOT IN ('7140206','7140221','7140222');

-- Nhóm Sư phạm đặc thù (GDTC, Âm nhạc, Mỹ thuật): Khá + 18 điểm
UPDATE dm_nganh SET nhom_nganh = 'SuPhamDacThu', nguong_hoc_luc = 'Kha', nguong_diem_thpt = 16.50
WHERE ma_nganh IN ('7140206','7140221','7140222');

-- Nhóm Điều dưỡng: Khá + 20 điểm
UPDATE dm_nganh SET nhom_nganh = 'DieuDuong', nguong_hoc_luc = 'Kha', nguong_diem_thpt = 16.50
WHERE ma_nganh = '7720301';
