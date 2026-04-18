-- Migration: v21 - Update aspirations uniqueness to support multiple admission methods
-- Corrects the previous constraint to include ma_phuong_thuc and to_hop_mon
-- Also ensures columns are NOT NULL for UPSERT compatibility

UPDATE nguyen_vong SET ma_phuong_thuc = '' WHERE ma_phuong_thuc IS NULL;
UPDATE nguyen_vong SET to_hop_mon = '' WHERE to_hop_mon IS NULL;

ALTER TABLE nguyen_vong ALTER COLUMN ma_phuong_thuc SET NOT NULL;
ALTER TABLE nguyen_vong ALTER COLUMN ma_phuong_thuc SET DEFAULT '';
ALTER TABLE nguyen_vong ALTER COLUMN to_hop_mon SET NOT NULL;
ALTER TABLE nguyen_vong ALTER COLUMN to_hop_mon SET DEFAULT '';

ALTER TABLE nguyen_vong DROP CONSTRAINT IF EXISTS uk_hoso_thutu;
ALTER TABLE nguyen_vong DROP CONSTRAINT IF EXISTS uk_hoso_nv_method_combo;
ALTER TABLE nguyen_vong ADD CONSTRAINT uk_hoso_nv_method_combo UNIQUE (ho_so_id, thu_tu_nguyen_vong, ma_phuong_thuc, to_hop_mon);
