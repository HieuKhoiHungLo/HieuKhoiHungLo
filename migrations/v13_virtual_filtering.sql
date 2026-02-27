-- Migration v13: Cấu trúc Database cho Xét tuyển Lọc ảo (Virtual Filtering)
-- Thêm các trường lưu trữ tổ hợp điểm tối ưu và trạng thái xét duyệt

-- 1. Bổ sung các cột phục vụ Lọc Ảo vào bảng `nguyen_vong`
ALTER TABLE public.nguyen_vong
ADD COLUMN IF NOT EXISTS diem_mon_1 DECIMAL(5,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS diem_mon_2 DECIMAL(5,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS diem_mon_3 DECIMAL(5,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS diem_uu_tien_goc DECIMAL(5,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS diem_uu_tien_qd DECIMAL(5,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS diem_xet_tuyen DECIMAL(5,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS to_hop_toi_uu VARCHAR(10) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS phuong_thuc_toi_uu VARCHAR(10) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS trang_thai_trung_tuyen BOOLEAN DEFAULT FALSE;

-- 2. Đánh Index để tối ưu tốc độ cho quá trình Lọc Ảo (Thường xuyên query theo điểm xét tuyển)
CREATE INDEX IF NOT EXISTS idx_nguyen_vong_diem_xet_tuyen ON public.nguyen_vong(diem_xet_tuyen DESC);
CREATE INDEX IF NOT EXISTS idx_nguyen_vong_trang_thai_trung_tuyen ON public.nguyen_vong(trang_thai_trung_tuyen);

-- 3. Bảng `diem_chuan_du_kien` để lưu các mốc điểm do Hội đồng tuyển sinh kéo thả
CREATE TABLE IF NOT EXISTS public.diem_chuan_du_kien (
    id SERIAL PRIMARY KEY,
    dot_tuyen_sinh_id BIGINT REFERENCES public.dot_tuyen_sinh(id) ON DELETE CASCADE,
    ma_nganh VARCHAR(50) NOT NULL REFERENCES public.dm_nganh(ma_nganh) ON UPDATE CASCADE,
    chi_tieu_du_kien INTEGER DEFAULT 0,
    diem_chuan DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(dot_tuyen_sinh_id, ma_nganh)
);

-- Bật RLS cho bảng mới
ALTER TABLE public.diem_chuan_du_kien ENABLE ROW LEVEL SECURITY;
