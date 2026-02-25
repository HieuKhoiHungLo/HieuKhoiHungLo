-- Bật tính năng Row-Level Security
ALTER TABLE "public"."admission_benchmarks" ENABLE ROW LEVEL SECURITY;

-- Cho phép tất cả các Client (cả anon - chưa đăng nhập, và authenticated - đã đăng nhập) đều có thể SELECT
CREATE POLICY "Allow public read-access" 
ON "public"."admission_benchmarks" 
FOR SELECT 
USING (true);
