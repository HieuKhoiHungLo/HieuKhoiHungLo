-- Migration v34: Add Enrollment Guide columns to ket_qua_trung_tuyen
-- Description: Thêm các cột cho chức năng Hướng dẫn nhập học (Bàn nhập học, Vị trí nhập học, Link sơ đồ chỉ dẫn, Thông tin GVCN)

ALTER TABLE public.ket_qua_trung_tuyen
    ADD COLUMN IF NOT EXISTS ban_nhap_hoc VARCHAR(100),
    ADD COLUMN IF NOT EXISTS vi_tri_nhap_hoc VARCHAR(255),
    ADD COLUMN IF NOT EXISTS link_so_do TEXT,
    ADD COLUMN IF NOT EXISTS gvcn VARCHAR(255);
