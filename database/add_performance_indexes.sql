-- Phase 1 Performance: Critical Database Indexes
-- Run this in Supabase SQL Editor to speed up Dashboard & Stats

-- 1. Indexes for Session Filters (Used on every admin page)
CREATE INDEX IF NOT EXISTS idx_hoso_dot_tuyen_sinh ON ho_so_xet_tuyen(dot_tuyen_sinh_id);

-- 2. Indexes for Date Range Stats (Used in Reports/Stats)
CREATE INDEX IF NOT EXISTS idx_hoso_created_at ON ho_so_xet_tuyen(created_at);

-- 3. Composite Index for Session + Date (Most common filter combo)
CREATE INDEX IF NOT EXISTS idx_hoso_dot_created ON ho_so_xet_tuyen(dot_tuyen_sinh_id, created_at);

-- 4. Indexes for Status Aggregation (Dashboard counts)
CREATE INDEX IF NOT EXISTS idx_hoso_trang_thai ON ho_so_xet_tuyen(trang_thai);
CREATE INDEX IF NOT EXISTS idx_nguyen_vong_trang_thai_full ON nguyen_vong(trang_thai);

-- 5. Indexes for JOINs in Stats (Province, School, etc.)
CREATE INDEX IF NOT EXISTS idx_thi_sinh_tinh_ho_khau ON thi_sinh(ma_tinh_ho_khau);
CREATE INDEX IF NOT EXISTS idx_thi_sinh_truong_lop_12 ON thi_sinh(ma_truong_lop_12);
CREATE INDEX IF NOT EXISTS idx_thi_sinh_gioi_tinh ON thi_sinh(gioi_tinh);
CREATE INDEX IF NOT EXISTS idx_thi_sinh_khu_vuc ON thi_sinh(khu_vuc_uu_tien);
CREATE INDEX IF NOT EXISTS idx_thi_sinh_doi_tuong ON thi_sinh(doi_tuong_uu_tien);

-- 6. Index for Edit Requests (Dashboard Badge)
CREATE INDEX IF NOT EXISTS idx_hoso_yeu_cau_chinh_sua ON ho_so_xet_tuyen(yeu_cau_chinh_sua) WHERE yeu_cau_chinh_sua = TRUE;

-- 7. Analyze tables to update query planner
ANALYZE ho_so_xet_tuyen;
ANALYZE nguyen_vong;
ANALYZE thi_sinh;
ANALYZE dot_tuyen_sinh;
