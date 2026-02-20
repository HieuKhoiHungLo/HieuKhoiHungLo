-- Migration to link Wards (dm_xa) directly to Provinces (dm_tinh)

-- 1. Add ma_tinh column if it doesn't exist
ALTER TABLE public.dm_xa 
ADD COLUMN IF NOT EXISTS ma_tinh TEXT;

-- 2. Add Foreign Key constraint
-- Note: This might fail if you have existing filter data in dm_xa that doesn't match dm_tinh.
-- Ensure your data is clean or empty before running constraints.
ALTER TABLE public.dm_xa
ADD CONSTRAINT fk_dm_xa_dm_tinh
FOREIGN KEY (ma_tinh) 
REFERENCES public.dm_tinh (ma_tinh);

-- 3. (Optional) Index for performance
CREATE INDEX IF NOT EXISTS idx_dm_xa_ma_tinh ON public.dm_xa(ma_tinh);
