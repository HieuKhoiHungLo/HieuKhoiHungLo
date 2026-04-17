-- Migration v19: Fix security advisor warnings (search_path & missing RLS)
-- Created: 2026-04-16

-- 1. Fix mutable search_path warning for internal functions
-- Setting search_path explicitly prevents "search path hijacking" attacks.
-- This addresses the WARN: "Function public.update_updated_at_column has a role mutable search_path"
ALTER FUNCTION public.update_updated_at_column() SET search_path = public;

-- 2. Enable Row Level Security (RLS) and silence INFOS
-- For tables that should be 100% private from the public API (PostgREST),
-- we enable RLS but do not grant any public policies.
-- We add an explicit 'Deny All' policy to satisfy the Supabase Security Advisor.

-- 2.1. Certificate scores table (from v18)
ALTER TABLE "public"."diem_chung_chi" ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Deny public access to diem_chung_chi" ON "public"."diem_chung_chi";
CREATE POLICY "Deny public access to diem_chung_chi" ON "public"."diem_chung_chi" FOR ALL USING (false);

-- 2.2. Tracking table (from v15)
ALTER TABLE "public"."online_tracking" ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Deny public access to online_tracking" ON "public"."online_tracking";
CREATE POLICY "Deny public access to online_tracking" ON "public"."online_tracking" FOR ALL USING (false);

-- 2.3. Calculation summary table (from v16)
-- Address INFO: "Table public.v_calc_summary has RLS enabled, but no policies exist"
ALTER TABLE "public"."v_calc_summary" ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Deny public access to v_calc_summary" ON "public"."v_calc_summary";
CREATE POLICY "Deny public access to v_calc_summary" ON "public"."v_calc_summary" FOR ALL USING (false);

-- NOTE: The PHP Backend connects via service role/master credentials 
-- and is NOT restricted by these RLS policies.
