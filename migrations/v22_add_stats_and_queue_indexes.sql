-- Migration v22: Add indexes to prevent Full Table Scans on page_views and email_queue
-- Optimized for Index-Only Scans on high-frequency tables.

CREATE INDEX IF NOT EXISTS idx_page_views_url_created 
    ON public.page_views(url, created_at);

CREATE INDEX IF NOT EXISTS idx_email_queue_status_sent 
    ON public.email_queue(status, sent_at);

CREATE INDEX IF NOT EXISTS idx_email_queue_status_created 
    ON public.email_queue(status, created_at);
