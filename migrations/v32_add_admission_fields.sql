-- Migration: Add Admission & Enrollment Fields to ket_qua_trung_tuyen
-- Run on PostgreSQL database

ALTER TABLE public.ket_qua_trung_tuyen
    ADD COLUMN IF NOT EXISTS so_giay_bao VARCHAR(50),
    ADD COLUMN IF NOT EXISTS thoi_gian_nhap VARCHAR(100),
    ADD COLUMN IF NOT EXISTS nganh_tt VARCHAR(255),
    ADD COLUMN IF NOT EXISTS ten_khoa VARCHAR(150),
    ADD COLUMN IF NOT EXISTS kinh_phi TEXT,
    ADD COLUMN IF NOT EXISTS xac_nhan_bo BOOLEAN DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS xac_nhan_truong BOOLEAN DEFAULT FALSE;
