-- Migration v16: Tách biệt dữ liệu tính toán và lọc ảo ra bảng chuyên biệt
-- Tránh làm thay đổi cấu trúc/dữ liệu của các bảng đăng ký gốc

CREATE TABLE IF NOT EXISTS public.v_calc_summary (
    id SERIAL PRIMARY KEY,
    nguyen_vong_id BIGINT UNIQUE NOT NULL REFERENCES public.nguyen_vong(id) ON DELETE CASCADE,
    
    -- Kết quả tính toán điểm
    diem_xet_tuyen DECIMAL(5,2) DEFAULT NULL,
    to_hop_toi_uu VARCHAR(10) DEFAULT NULL,
    phuong_thuc_toi_uu VARCHAR(20) DEFAULT NULL,
    
    -- Chi tiết điểm thành phần (để hiển thị UI nhanh)
    diem_mon_1 DECIMAL(5,2) DEFAULT NULL,
    diem_mon_2 DECIMAL(5,2) DEFAULT NULL,
    diem_mon_3 DECIMAL(5,2) DEFAULT NULL,
    diem_uu_tien_goc DECIMAL(5,2) DEFAULT NULL,
    diem_uu_tien_qd DECIMAL(5,2) DEFAULT NULL,
    
    -- Dữ liệu kỹ thuật phục vụ tối ưu
    chi_tiet_diem JSONB DEFAULT NULL,
    data_hash VARCHAR(64) DEFAULT NULL, -- Dùng để Dirty Checking (Incremental Calculation)
    
    -- Kết quả Lọc ảo (Trúng tuyển dự kiến)
    trang_thai_trung_tuyen BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index phục vụ hiệu năng
CREATE INDEX IF NOT EXISTS idx_v_calc_summary_nguyen_vong_id ON public.v_calc_summary(nguyen_vong_id);
CREATE INDEX IF NOT EXISTS idx_v_calc_summary_data_hash ON public.v_calc_summary(data_hash);
CREATE INDEX IF NOT EXISTS idx_v_calc_summary_diem_xet_tuyen ON public.v_calc_summary(diem_xet_tuyen DESC);

-- Bật RLS (Nếu hệ thống đang dùng RLS cho các bảng khác)
ALTER TABLE public.v_calc_summary ENABLE ROW LEVEL SECURITY;

-- Cấp quyền (tùy chọn theo setup hiện tại)
-- GRANT ALL ON public.v_calc_summary TO postgres;
