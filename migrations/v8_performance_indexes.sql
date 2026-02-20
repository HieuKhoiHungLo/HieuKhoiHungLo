-- ============================================================
-- Migration: Add performance indexes for stats queries
-- Run this script on your Supabase SQL Editor.
-- ============================================================

-- 1. ho_so_xet_tuyen: INDEX chính cho stats queries (filter theo session + date)
CREATE INDEX IF NOT EXISTS idx_hoso_session_date
    ON public.ho_so_xet_tuyen(dot_tuyen_sinh_id, created_at);

-- 2. ho_so_xet_tuyen: INDEX cho filter theo trang_thai (status)
CREATE INDEX IF NOT EXISTS idx_hoso_trang_thai
    ON public.ho_so_xet_tuyen(trang_thai);

-- 3. ho_so_xet_tuyen: INDEX cho filter theo so_cccd (dùng trong JOIN với thi_sinh)
CREATE INDEX IF NOT EXISTS idx_hoso_cccd
    ON public.ho_so_xet_tuyen(so_cccd);

-- 4. thi_sinh: INDEX cho JOIN key (so_cccd là PK nhưng đảm bảo index tồn tại)
CREATE INDEX IF NOT EXISTS idx_thisinh_cccd
    ON public.thi_sinh(so_cccd);

-- 5. thi_sinh: INDEX cho GROUP BY khu_vuc / doi_tuong (dùng trong demographic stats)
CREATE INDEX IF NOT EXISTS idx_thisinh_khu_vuc
    ON public.thi_sinh(khu_vuc_uu_tien);

CREATE INDEX IF NOT EXISTS idx_thisinh_doi_tuong
    ON public.thi_sinh(doi_tuong_uu_tien);

-- 6. nguyen_vong: INDEX cho getMajorStats (JOIN + GROUP BY ma_nganh)
CREATE INDEX IF NOT EXISTS idx_nguyenvong_nganh
    ON public.nguyen_vong(ma_nganh);

CREATE INDEX IF NOT EXISTS idx_nguyenvong_cccd
    ON public.nguyen_vong(so_cccd);

-- Verify
SELECT tablename, indexname FROM pg_indexes
WHERE schemaname = 'public'
  AND tablename IN ('ho_so_xet_tuyen', 'thi_sinh', 'nguyen_vong')
ORDER BY tablename, indexname;
