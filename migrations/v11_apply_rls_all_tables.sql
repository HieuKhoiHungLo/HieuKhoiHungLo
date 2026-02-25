-- =========================================================================
-- MIGRATION: BẬT RLS (ROW-LEVEL SECURITY) CHO TOÀN BỘ CƠ SỞ DỮ LIỆU
-- Ý NGHĨA: Chống lại lỗ hổng rò rỉ dữ liệu qua PostgREST (Supabase API)
-- MÔ HÌNH: Backend-Driven (Tất cả logic qua PHP, chặn API trực tiếp)
-- =========================================================================

-- -------------------------------------------------------------------------
-- NHÓM 1: CÁC BẢNG CÔNG KHAI (PUBLIC READ-ONLY)
-- (Ai cũng có quyền xem SELECT, nhưng tuyệt đối không được INSERT/UPDATE/DELETE)
-- -------------------------------------------------------------------------

-- 1.1. Bảng Tin tức & Thiết lập
ALTER TABLE "public"."posts" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read posts" ON "public"."posts" FOR SELECT USING (true);

ALTER TABLE "public"."settings" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read settings" ON "public"."settings" FOR SELECT USING (true);

ALTER TABLE "public"."cau_hinh" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read cau_hinh" ON "public"."cau_hinh" FOR SELECT USING (true);

-- 1.2. Bảng Danh mục Tuyển sinh & Cấu hình điểm
ALTER TABLE "public"."dm_nganh" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read dm_nganh" ON "public"."dm_nganh" FOR SELECT USING (true);

ALTER TABLE "public"."dm_nganh_to_hop" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read dm_nganh_to_hop" ON "public"."dm_nganh_to_hop" FOR SELECT USING (true);

ALTER TABLE "public"."dot_tuyen_sinh" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read dot_tuyen_sinh" ON "public"."dot_tuyen_sinh" FOR SELECT USING (true);

ALTER TABLE "public"."admission_methods" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read admission_methods" ON "public"."admission_methods" FOR SELECT USING (true);

ALTER TABLE "public"."major_subject_weights" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read major_subject_weights" ON "public"."major_subject_weights" FOR SELECT USING (true);

ALTER TABLE "public"."admission_rules" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read admission_rules" ON "public"."admission_rules" FOR SELECT USING (true);

-- 1.3. Bảng Danh mục Chung (Tỉnh, Huyện, Trường, Tổ hợp...)
ALTER TABLE "public"."dm_tinh" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read dm_tinh" ON "public"."dm_tinh" FOR SELECT USING (true);

ALTER TABLE "public"."dm_xa" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read dm_xa" ON "public"."dm_xa" FOR SELECT USING (true);

ALTER TABLE "public"."dm_truong_thpt" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read dm_truong_thpt" ON "public"."dm_truong_thpt" FOR SELECT USING (true);

ALTER TABLE "public"."dm_khu_vuc" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read dm_khu_vuc" ON "public"."dm_khu_vuc" FOR SELECT USING (true);

ALTER TABLE "public"."dm_doi_tuong" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read dm_doi_tuong" ON "public"."dm_doi_tuong" FOR SELECT USING (true);

ALTER TABLE "public"."dm_mon" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read dm_mon" ON "public"."dm_mon" FOR SELECT USING (true);

ALTER TABLE "public"."dm_to_hop" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read dm_to_hop" ON "public"."dm_to_hop" FOR SELECT USING (true);

ALTER TABLE "public"."cau_hinh_chung_chi" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read cau_hinh_chung_chi" ON "public"."cau_hinh_chung_chi" FOR SELECT USING (true);

ALTER TABLE "public"."config_vung_tuyen_sinh" ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow public read config_vung_tuyen_sinh" ON "public"."config_vung_tuyen_sinh" FOR SELECT USING (true);

-- -------------------------------------------------------------------------
-- NHÓM 2: CÁC BẢNG DỮ LIỆU NHẠY CẢM (STRICT PRIVATE)
-- (Bật RLS nhưng KHÔNG TẠO POLICY => Chặn 100% PostgREST API)
-- LƯU Ý: PHP Backend (Superuser) vẫn hoạt động bình thường!
-- -------------------------------------------------------------------------

-- 2.1. Dữ liệu cá nhân Thí sinh
ALTER TABLE "public"."chung_chi_thi_sinh" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."diem_nang_khieu" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."diem_nang_khieu_ngoai" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."diem_thi_thpt" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."diem_chi_tiet" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."admission_scores" ENABLE ROW LEVEL SECURITY;
-- Mặc định thi_sinh, ho_so_xet_tuyen, nguyen_vong có thể đã cấu hình, nhưng bật phòng hờ
ALTER TABLE "public"."thi_sinh" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."ho_so_xet_tuyen" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."nguyen_vong" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."ket_qua_hoc_tap" ENABLE ROW LEVEL SECURITY;

-- 2.2. Dữ liệu Quản trị & Hệ thống
ALTER TABLE "public"."email_templates" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."email_queue" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."audit_logs" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."login_attempts" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."log_import" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."quan_tri_vien" ENABLE ROW LEVEL SECURITY;

-- 2.3. Bảng Token nhạy cảm & Báo động mật khẩu
ALTER TABLE "public"."password_resets" ENABLE ROW LEVEL SECURITY;

-- 2.4. Bảng thông báo (Notification)
ALTER TABLE "public"."notifications" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "public"."notification_reads" ENABLE ROW LEVEL SECURITY;

-- =========================================================================
-- KẾT THÚC CẬP NHẬT RLS
-- Toàn bộ 34+ bảng đã được vá hoàn toàn lỗ hổng do Security Advisor báo cáo
-- =========================================================================
