-- Migration: Add "Nhập học" Role & Update Menu Permissions
-- Run on Supabase PostgreSQL database

-- 1. Insert "Nhập học" role (ID 4 or auto-serial)
INSERT INTO public.roles (id, name, display_name, permissions)
VALUES (
    4,
    'enrollment_officer',
    'Nhập học',
    '["enrollment.process"]'
)
ON CONFLICT (id) DO UPDATE
    SET name         = EXCLUDED.name,
        display_name = EXCLUDED.display_name,
        permissions  = EXCLUDED.permissions;

-- Reset sequence if needed
SELECT setval('roles_id_seq', (SELECT MAX(id) FROM roles));

-- 2. Update permission_required for "Xử lý Nhập học" menu item to "enrollment.process"
UPDATE public.menus 
SET permission_required = 'enrollment.process' 
WHERE url = '/admin/enrollment/process';
