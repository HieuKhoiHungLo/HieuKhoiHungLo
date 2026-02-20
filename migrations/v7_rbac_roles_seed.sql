-- ============================================================
-- Migration: Seed 3 Admin Roles for RBAC Sidebar
-- Run this script on your Supabase PostgreSQL database.
-- ============================================================

-- 1. Create roles table if not exists
CREATE TABLE IF NOT EXISTS public.roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    permissions JSONB NOT NULL DEFAULT '[]',
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. Grant access to roles table (if using Supabase RLS)
ALTER TABLE public.roles ENABLE ROW LEVEL SECURITY;
CREATE POLICY IF NOT EXISTS "Allow authenticated read roles"
    ON public.roles FOR SELECT USING (true);

-- 3. Upsert the 3 roles
-- Role 1: Quản trị hệ thống (Super Admin) — toàn quyền
INSERT INTO public.roles (id, name, display_name, permissions)
VALUES (
    1,
    'super_admin',
    'Quản trị hệ thống',
    '["all"]'
)
ON CONFLICT (id) DO UPDATE
    SET name         = EXCLUDED.name,
        display_name = EXCLUDED.display_name,
        permissions  = EXCLUDED.permissions;

-- Role 2: Cán bộ xét tuyển — xét duyệt hồ sơ + tin tức
INSERT INTO public.roles (id, name, display_name, permissions)
VALUES (
    2,
    'admission_officer',
    'Cán bộ xét tuyển',
    '["dashboard","stats","candidate.view","candidate.edit","candidate.bulk","report.view","report.export","aptitude.view","posts.view","posts.edit"]'
)
ON CONFLICT (id) DO UPDATE
    SET name         = EXCLUDED.name,
        display_name = EXCLUDED.display_name,
        permissions  = EXCLUDED.permissions;

-- Role 3: Lãnh đạo — chỉ xem dashboard + thống kê
INSERT INTO public.roles (id, name, display_name, permissions)
VALUES (
    3,
    'leadership',
    'Lãnh đạo',
    '["dashboard","stats"]'
)
ON CONFLICT (id) DO UPDATE
    SET name         = EXCLUDED.name,
        display_name = EXCLUDED.display_name,
        permissions  = EXCLUDED.permissions;

-- 4. Make sure quan_tri_vien has role_id column
ALTER TABLE public.quan_tri_vien
    ADD COLUMN IF NOT EXISTS role_id INT REFERENCES public.roles(id);

-- 5. (Optional) Assign role 1 to admin user (id=1) if not already set
UPDATE public.quan_tri_vien SET role_id = 1 WHERE id = 1 AND role_id IS NULL;

-- Verify
SELECT id, name, display_name FROM public.roles ORDER BY id;
