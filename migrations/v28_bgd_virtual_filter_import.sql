-- Migration v28: Thêm tính năng Import kết quả Lọc ảo Bộ GD&ĐT
-- Mục đích: Lưu kết quả trả về từ hệ thống liên trường của Bộ GD&ĐT,
--            đánh dấu thí sinh đã trúng tuyển ở trường khác để loại khỏi DS HVU

-- 1. Tạo bảng lưu dữ liệu gốc từ file kết quả Bộ GD&ĐT
CREATE TABLE IF NOT EXISTS public.ket_qua_loc_ao_bo_gd (
    id                      SERIAL PRIMARY KEY,
    dot_tuyen_sinh_id       INTEGER NOT NULL REFERENCES public.dot_tuyen_sinh(id) ON DELETE CASCADE,
    lan_loc_ao              VARCHAR(50),           -- VD: LOC_AO_BO_LAN2
    so_cccd                 VARCHAR(20) NOT NULL,  -- ĐDCN (khóa join với thi_sinh)
    sbd                     VARCHAR(20),           -- Số báo danh
    ho_va_ten               TEXT,
    ma_nganh_hvu            VARCHAR(20),           -- Mã xét tuyển tại HVU
    thu_tu_nv               INTEGER,               -- Thứ tự nguyện vọng đăng ký
    ket_qua                 VARCHAR(20),           -- 'Trúng' hoặc 'Đổ'
    ttnv_do                 INTEGER,               -- Thứ tự NV đỗ (nếu Trúng)
    ma_truong_trung_tuyen   VARCHAR(20),           -- Mã trường thực sự trúng (nếu ≠ DKS → trường khác)
    ma_nganh_trung_tuyen    VARCHAR(50),           -- Mã ngành tại trường đỗ
    ten_nganh_trung_tuyen   TEXT,                  -- Tên ngành tại trường đỗ
    imported_at             TIMESTAMP DEFAULT NOW(),
    imported_by             VARCHAR(100),          -- Tên admin thực hiện import
    -- Chỉ giữ 1 bản ghi mới nhất mỗi thí sinh per đợt (UPSERT sẽ cập nhật)
    UNIQUE(dot_tuyen_sinh_id, so_cccd)
);

-- 2. Thêm cột đánh dấu "bị loại bởi kết quả Bộ GD&ĐT" vào v_calc_summary
--    Cờ này ĐỘC LẬP với trang_thai_trung_tuyen (lọc ảo nội bộ)
ALTER TABLE public.v_calc_summary
    ADD COLUMN IF NOT EXISTS bi_loai_truong_khac BOOLEAN DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS ma_truong_trung_tuyen_bo VARCHAR(20) DEFAULT NULL;

-- 3. Index cho hiệu năng truy vấn
CREATE INDEX IF NOT EXISTS idx_ket_qua_bo_dot_cccd
    ON public.ket_qua_loc_ao_bo_gd(dot_tuyen_sinh_id, so_cccd);

CREATE INDEX IF NOT EXISTS idx_ket_qua_bo_ma_truong
    ON public.ket_qua_loc_ao_bo_gd(ma_truong_trung_tuyen);

CREATE INDEX IF NOT EXISTS idx_v_calc_bi_loai
    ON public.v_calc_summary(bi_loai_truong_khac)
    WHERE bi_loai_truong_khac = TRUE;

-- 4. Bật RLS cho bảng mới (nhất quán với các bảng khác)
ALTER TABLE public.ket_qua_loc_ao_bo_gd ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Deny public access to ket_qua_loc_ao_bo_gd" ON public.ket_qua_loc_ao_bo_gd;
CREATE POLICY "Deny public access to ket_qua_loc_ao_bo_gd"
    ON public.ket_qua_loc_ao_bo_gd FOR ALL USING (false);

-- 5. Comment bảng để documentation
COMMENT ON TABLE public.ket_qua_loc_ao_bo_gd IS
    'Lưu kết quả lọc ảo liên trường từ hệ thống Bộ GD&ĐT. Dùng để loại thí sinh đã trúng tuyển trường khác khỏi danh sách chính thức của HVU.';
COMMENT ON COLUMN public.ket_qua_loc_ao_bo_gd.ket_qua IS
    'Trúng = thí sinh trúng tuyển (xem ma_truong để biết trúng trường nào); Đổ = rớt toàn bộ';
COMMENT ON COLUMN public.ket_qua_loc_ao_bo_gd.ma_truong_trung_tuyen IS
    'Nếu = DKS thì trúng tuyển HVU; nếu khác DKS thì đã trúng trường khác → loại khỏi DS HVU';
COMMENT ON COLUMN public.v_calc_summary.bi_loai_truong_khac IS
    'TRUE nếu thí sinh đã trúng tuyển nguyện vọng cao hơn ở trường khác theo kết quả Bộ GD&ĐT';
