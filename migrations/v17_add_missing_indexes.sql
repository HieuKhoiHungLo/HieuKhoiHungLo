-- Migration v17: Tối ưu hoá truy vấn với các file Index mới (Chống nghẽn Full Table Scan)
-- 1. Bảng nguyen_vong thiếu Index ở dot_tuyen_sinh_id. 
-- Điều này khiến các lệnh UPDATE ... WHERE dot_tuyen_sinh_id = ? phải quét toàn bộ bảng.
CREATE INDEX IF NOT EXISTS idx_nguyenvong_dot_tuyen_sinh ON public.nguyen_vong(dot_tuyen_sinh_id);

-- 2. Tối ưu Bulk-Join trong lệnh syncData (Khớp cccd + dot_tuyen_sinh)
CREATE INDEX IF NOT EXISTS idx_nguyenvong_cccd_dot ON public.nguyen_vong(so_cccd, dot_tuyen_sinh_id);
CREATE INDEX IF NOT EXISTS idx_hoso_cccd_dot ON public.ho_so_xet_tuyen(so_cccd, dot_tuyen_sinh_id);
