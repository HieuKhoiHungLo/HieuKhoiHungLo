DO $$
DECLARE
    english_id INT;
BEGIN
    -- Get English Subject ID
    SELECT id INTO english_id FROM dm_mon WHERE ma_mon = 'N1'; -- N1 usually English, check later. Fallback to subquery if possible.
    -- Or try common code like 'TA', 'ENG'. Assuming 'N1' is Ngoai Ngu 1. 
    -- If null, we skip seeding or insert dummy.
    -- Let's try to be safe: default to 0 if not found, admin can fix.

    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'diem_nang_khieu') THEN
        CREATE TABLE diem_nang_khieu (
            id SERIAL PRIMARY KEY,
            so_cccd VARCHAR(20) NOT NULL,
            mon_nang_khieu_id INT NOT NULL,
            diem FLOAT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(so_cccd, mon_nang_khieu_id)
        );
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'cau_hinh_chung_chi') THEN
        CREATE TABLE cau_hinh_chung_chi (
            id SERIAL PRIMARY KEY,
            loai_chung_chi VARCHAR(50) NOT NULL,
            muc_diem_tu FLOAT NOT NULL,
            muc_diem_den FLOAT,
            diem_quy_doi FLOAT NOT NULL,
            mon_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        -- Seed Data (Example IELTS)
        -- Only insert if English ID known. 
        -- For now, create table empty. Admin can configure.
    END IF;

    -- Add columns to dm_nganh
    ALTER TABLE dm_nganh ADD COLUMN IF NOT EXISTS co_xet_chung_chi BOOLEAN DEFAULT FALSE;

    -- Add columns to nguyen_vong
    ALTER TABLE nguyen_vong ADD COLUMN IF NOT EXISTS diem_xet_tuyen FLOAT DEFAULT 0;
    ALTER TABLE nguyen_vong ADD COLUMN IF NOT EXISTS to_hop_xet_tuyen_id INT;
    ALTER TABLE nguyen_vong ADD COLUMN IF NOT EXISTS phuong_thuc_xet_tuyen VARCHAR(50);
    ALTER TABLE nguyen_vong ADD COLUMN IF NOT EXISTS chi_tiet_diem JSONB;
END $$;
