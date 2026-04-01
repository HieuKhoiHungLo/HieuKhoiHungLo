-- Optimization Indexes for Scoring and Filtering
-- Run these in your database console (Supabase/PostgreSQL)

-- 1. Index for faster candidate lookup by session and status
CREATE INDEX IF NOT EXISTS idx_nguyen_vong_session_status 
ON nguyen_vong (dot_tuyen_sinh_id, trang_thai);

-- 2. Composite index for candidate and session in nguyen_vong (used in many joins)
CREATE INDEX IF NOT EXISTS idx_nguyen_vong_cccd_session 
ON nguyen_vong (so_cccd, dot_tuyen_sinh_id);

-- 3. Index for summary view performance
CREATE INDEX IF NOT EXISTS idx_calc_summary_nv_session 
ON v_calc_summary (nguyen_vong_id, dot_tuyen_sinh_id);

-- 4. Index for transcript lookups
CREATE INDEX IF NOT EXISTS idx_kqht_cccd_lop 
ON ket_qua_hoc_tap (so_cccd, lop);

-- 5. Index for national exam results
CREATE INDEX IF NOT EXISTS idx_thpt_cccd 
ON diem_thi_thpt (so_cccd);

-- 6. Analyzing tables to update statistics
ANALYZE thi_sinh;
ANALYZE nguyen_vong;
ANALYZE ket_qua_hoc_tap;
ANALYZE diem_thi_thpt;
ANALYZE v_calc_summary;
