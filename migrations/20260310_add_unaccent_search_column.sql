-- Migration file: optimize Vietnamese name search
-- Tối ưu hóa tìm kiếm tiếng Việt không dấu: giảm thiểu 56 hàm replace()
CREATE EXTENSION IF NOT EXISTS unaccent;
CREATE EXTENSION IF NOT EXISTS pg_trgm;

ALTER TABLE thi_sinh 
ADD COLUMN ho_va_ten_search TEXT GENERATED ALWAYS AS (lower(unaccent(ho_va_ten))) STORED;

CREATE INDEX idx_thi_sinh_search ON thi_sinh USING gin (ho_va_ten_search gin_trgm_ops);
