-- Create table if not exists
CREATE TABLE IF NOT EXISTS public.quan_tri_vien (
    id SERIAL PRIMARY KEY,
    ten_dang_nhap VARCHAR(50) UNIQUE NOT NULL,
    mat_khau VARCHAR(255) NOT NULL,
    ho_ten VARCHAR(100) NOT NULL,
    vai_tro VARCHAR(20) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin if not exists (password: admin123)
-- Hash generated via PHP password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO public.quan_tri_vien (ten_dang_nhap, mat_khau, ho_ten, vai_tro)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM public.quan_tri_vien WHERE ten_dang_nhap = 'admin');
