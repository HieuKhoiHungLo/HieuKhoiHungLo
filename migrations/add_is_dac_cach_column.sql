-- Migration to add is_dac_cach column to thi_sinh table
ALTER TABLE thi_sinh ADD COLUMN is_dac_cach BOOLEAN DEFAULT FALSE;
