-- Migration: v20 - Add ghi_chu column to ket_qua_hoc_tap
-- Fixed: SQLSTATE[42703]: Undefined column: 7 ERROR: column "ghi_chu" does not exist

ALTER TABLE ket_qua_hoc_tap ADD COLUMN IF NOT EXISTS ghi_chu TEXT;
