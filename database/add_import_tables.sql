DO $$
BEGIN
    -- 1. Create dot_tuyen_sinh table (Admission Batches)
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'dot_tuyen_sinh') THEN
        CREATE TABLE dot_tuyen_sinh (
            id SERIAL PRIMARY KEY,
            ten_dot VARCHAR(255) NOT NULL,
            nam INT NOT NULL,
            trang_thai VARCHAR(50) DEFAULT 'active', -- active, locked, completed
            mo_ta TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        -- Seed initial batch for existing data
        INSERT INTO dot_tuyen_sinh (ten_dot, nam, trang_thai) VALUES ('Đợt 1 - 2026', 2026, 'active');
    END IF;

    -- 2. Create log_import table (Import History)
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'log_import') THEN
        CREATE TABLE log_import (
            id SERIAL PRIMARY KEY,
            file_name VARCHAR(255) NOT NULL,
            loai_file VARCHAR(50), -- candidates, applications, transcripts
            record_count INT DEFAULT 0,
            success_count INT DEFAULT 0,
            error_log TEXT,
            imported_by INT, -- admin id
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    END IF;

    -- 3. Update nguyen_vong table (Applications)
    -- Add dot_tuyen_sinh_id
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'nguyen_vong' AND column_name = 'dot_tuyen_sinh_id') THEN
        ALTER TABLE nguyen_vong ADD COLUMN dot_tuyen_sinh_id INT;
        -- Update existing records to default batch (1) which we just created
        UPDATE nguyen_vong SET dot_tuyen_sinh_id = 1 WHERE dot_tuyen_sinh_id IS NULL;
    END IF;

    -- Add nguon_du_lieu
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'nguyen_vong' AND column_name = 'nguon_du_lieu') THEN
        ALTER TABLE nguyen_vong ADD COLUMN nguon_du_lieu VARCHAR(50) DEFAULT 'online'; -- online, bo_gddt
    END IF;

    -- 4. Update thi_sinh table (Candidates)? 
    -- Candidates generally persist across batches, but we might want to know if they came from 'bo_gddt' import.
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'thi_sinh' AND column_name = 'nguon_du_lieu') THEN
        ALTER TABLE thi_sinh ADD COLUMN nguon_du_lieu VARCHAR(50) DEFAULT 'online';
    END IF;

END $$;
