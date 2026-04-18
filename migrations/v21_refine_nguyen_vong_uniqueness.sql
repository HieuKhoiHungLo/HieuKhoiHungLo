-- Migration: v21 - Update aspirations uniqueness to support multiple admission methods
-- Corrects the previous constraint to include ma_phuong_thuc and to_hop_mon

ALTER TABLE nguyen_vong DROP CONSTRAINT IF EXISTS uk_hoso_thutu;
ALTER TABLE nguyen_vong ADD CONSTRAINT uk_hoso_nv_method_combo UNIQUE (ho_so_id, thu_tu_nguyen_vong, ma_phuong_thuc, to_hop_mon);
