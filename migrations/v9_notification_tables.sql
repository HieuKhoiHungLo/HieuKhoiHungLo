-- v9: Ensure notification tables exist
-- notifications table (admin creates these)
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'info',       -- info, warning, success, important
    target_type VARCHAR(20) DEFAULT 'all', -- all, individual, session
    target_id VARCHAR(50),                 -- CCCD or session ID depending on target_type
    created_by INTEGER REFERENCES quan_tri_vien(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

-- notification_reads table (tracks which user read which notification)
CREATE TABLE IF NOT EXISTS notification_reads (
    id SERIAL PRIMARY KEY,
    notification_id INTEGER NOT NULL REFERENCES notifications(id) ON DELETE CASCADE,
    user_cccd VARCHAR(20) NOT NULL,
    read_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(notification_id, user_cccd)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_notifications_target ON notifications(target_type, target_id);
CREATE INDEX IF NOT EXISTS idx_notification_reads_user ON notification_reads(user_cccd);
CREATE INDEX IF NOT EXISTS idx_notification_reads_combo ON notification_reads(notification_id, user_cccd);
