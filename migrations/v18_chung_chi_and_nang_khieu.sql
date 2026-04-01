DROP TABLE IF EXISTS diem_nang_khieu_ngoai CASCADE;
DROP TABLE IF EXISTS diem_nang_khieu CASCADE;

CREATE TABLE IF NOT EXISTS diem_nang_khieu (
    id SERIAL PRIMARY KEY,
    so_cccd VARCHAR(20) NOT NULL,
    sbd VARCHAR(20) NOT NULL,
    ma_mon VARCHAR(50) NOT NULL,
    diem DECIMAL(10,2) NOT NULL,
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(so_cccd, ma_mon)
);

CREATE TABLE IF NOT EXISTS diem_chung_chi (
    id SERIAL PRIMARY KEY,
    so_cccd VARCHAR(20) NOT NULL,
    ma_mon VARCHAR(50) NOT NULL,
    diem DECIMAL(10,2) NOT NULL,
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(so_cccd, ma_mon)
);

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

DROP TRIGGER IF EXISTS trg_diem_nang_khieu_updated_at ON diem_nang_khieu;
CREATE TRIGGER trg_diem_nang_khieu_updated_at
BEFORE UPDATE ON diem_nang_khieu
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS trg_diem_chung_chi_updated_at ON diem_chung_chi;
CREATE TRIGGER trg_diem_chung_chi_updated_at
BEFORE UPDATE ON diem_chung_chi
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();
