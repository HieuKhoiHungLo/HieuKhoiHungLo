-- migrations/v15_create_online_tracking.sql

CREATE TABLE IF NOT EXISTS public.online_tracking (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT NULL,        -- Candidate ID (thi_sinh.id)
    admin_id BIGINT NULL,       -- Admin ID (quan_tri_vien.id)
    ip_address VARCHAR(45) NULL,
    last_activity TIMESTAMPTZ DEFAULT NOW(),
    user_agent TEXT NULL
);

-- Index for fast cleanup and counting
CREATE INDEX IF NOT EXISTS idx_online_activity ON public.online_tracking(last_activity);

-- Add foreign keys if possible (optional but good for integrity)
-- ALTER TABLE public.online_tracking ADD CONSTRAINT fk_online_user FOREIGN KEY (user_id) REFERENCES public.thi_sinh(id) ON DELETE CASCADE;
-- ALTER TABLE public.online_tracking ADD CONSTRAINT fk_online_admin FOREIGN KEY (admin_id) REFERENCES public.quan_tri_vien(id) ON DELETE CASCADE;
