-- Migration: Add file_giay_bao column to ket_qua_trung_tuyen
-- Run on PostgreSQL database

ALTER TABLE public.ket_qua_trung_tuyen
    ADD COLUMN IF NOT EXISTS file_giay_bao VARCHAR(500);
