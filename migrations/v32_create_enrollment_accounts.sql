-- Migration v32: Create enrollment officer accounts for each desk
-- Run on Supabase PostgreSQL database

-- 1. Ensure role enrollment_officer (ID=4) exists
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

SELECT setval('roles_id_seq', GREATEST((SELECT MAX(id) FROM roles), 4));

-- 2. Create 12 desk accounts with default password 'hvu2026'
-- Password hash for 'hvu2026' using PASSWORD_DEFAULT (bcrypt)
DO $$
DECLARE
    pwd_hash TEXT;
    accts TEXT[][] := ARRAY[
        ['ban1',  'Bàn 1 - HT Trung tâm'],
        ['ban2',  'Bàn 2 - HT Trung tâm'],
        ['ban3',  'Bàn 3 - HT Trung tâm'],
        ['ban4',  'Bàn 4 - Giảng đường D'],
        ['ban5',  'Bàn 5 - Giảng đường D'],
        ['ban6',  'Bàn 6 - Giảng đường E'],
        ['ban7',  'Bàn 7 - Giảng đường E'],
        ['ban8',  'Bàn 8 - Góc VH Hàn Quốc'],
        ['ban9',  'Bàn 9 - Góc VH Hàn Quốc'],
        ['ban10', 'Bàn 10 - HT Tầng 3'],
        ['ban11', 'Bàn 11 - HT Tầng 3'],
        ['ban12', 'Bàn 12 - HT Tầng 3']
    ];
    i INT;
BEGIN
    -- We'll use a PHP-generated bcrypt hash placeholder
    -- The actual hash will be set by the PHP script below
    FOR i IN 1..array_length(accts, 1) LOOP
        INSERT INTO public.quan_tri_vien (ten_dang_nhap, mat_khau, ho_ten, role_id, is_active, permissions)
        VALUES (
            accts[i][1],
            '$PLACEHOLDER$',
            accts[i][2],
            4,
            true,
            '["enrollment.process"]'
        )
        ON CONFLICT DO NOTHING;
    END LOOP;
END $$;
