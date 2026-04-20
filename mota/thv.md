# TÀI LIỆU ĐẶC TẢ HỆ THỐNG XÉT TUYỂN VÀ LỌC ẢO (VIRTUAL ADMISSION SYSTEM)

Tài liệu này mô tả chi tiết toàn bộ chức năng, quy trình, giao diện và giải thuật tính toán của Hệ thống Xét tuyển và Lọc ảo nội bộ. Mục đích của tài liệu là giúp một hệ thống AI hoặc một đội ngũ phát triển khác có đủ thông tin để xây dựng lại (rebuild) hoặc thiết kế một hệ thống tương tự.

---

## 1. TỔNG QUAN HỆ THỐNG (SYSTEM OVERVIEW)

Hệ thống Xét tuyển và Lọc ảo là lõi công nghệ giúp trường Đại học thực hiện việc tính toán điểm số cho hàng vạn thí sinh với nhiều phương thức xét tuyển khác nhau (THPT, Học bạ, Năng khiếu, Chứng chỉ), và sau đó chạy thuật toán phân bổ thí sinh trúng tuyển vào các ngành dựa trên điểm chuẩn và chỉ tiêu (Lọc ảo dây chuyền).

**Core Tech Stack hiện tại:**
- **Backend:** PHP thuần (Custom MVC Framework) để đảm bảo hiệu năng cao nhất.
- **Database:** PostgreSQL (hiện đang dùng qua Supabase).
- **Frontend:** HTML/CSS/JS thuần, sử dụng TailwindCSS (Utility-first CSS), Alpine.js (Lightweight reactivity), và jQuery DataTables (với Server-Side Processing).
- **Architecture:** Monolithic, tối ưu hóa xử lý Batch (hàng loạt) để tính điểm không bị timeout.
- **Cloud Storage:** Google Drive API (lưu trữ ảnh hồ sơ, backup).
- **Email:** SMTP socket-based (không dùng thư viện ngoài), hỗ trợ Queue.

---

## 2. KIẾN TRÚC HỆ THỐNG (SYSTEM ARCHITECTURE)

### 2.1. Mô hình MVC Custom

Hệ thống tuân thủ mô hình MVC với cấu trúc phân tầng rõ rệt:

```
app/
├── Core/           # Framework core (Router, Database, Cache, Validator, App)
├── Controllers/    # 41 Controllers xử lý nghiệp vụ
├── Models/         # 22 Models tương tác trực tiếp với DB
├── Repositories/   # Repository Pattern (lớp trung gian giữa Controller và Model)
├── Services/       # 21 Business Services (tính điểm, lọc ảo, import, export, email...)
├── Middleware/      # Auth, Security, RateLimit
├── Helpers/        # Utility functions
└── Constants/      # Hằng số hệ thống
```

### 2.2. Routing & Middleware Pipeline

- **Router** (`Core/Router.php`): Hỗ trợ `GET/POST`, nhóm route (`group`), và gắn middleware.
- **Middleware Pipeline:**
  1. `SecurityMiddleware::secureSession()` → Cấu hình session cookie bảo mật.
  2. `SecurityMiddleware::setSecurityHeaders()` → CSP, X-Frame-Options, HSTS, XSS Protection.
  3. `SecurityMiddleware::checkSessionTimeout(30)` → Auto-logout sau 30 phút không hoạt động.
  4. `RateLimitMiddleware` → Giới hạn request (VD: 30 req/phút cho trang login).
  5. `AuthMiddleware` → Kiểm tra đăng nhập admin (session-based).

### 2.3. Caching Strategy

- **In-Memory Cache** (`Core/Cache.php`): TTL-based cache sử dụng biến PHP static. Hỗ trợ `remember($key, $ttl, $callback)` để tránh query lặp lại trong cùng một request.
- **Session Cache**: Lưu dữ liệu admin (user object) vào `$_SESSION` để tránh query DB mỗi pageload.
- **Query Cache**: Các thống kê nặng (stats, demographics, overview) được cache 10-600 giây tùy loại.

### 2.4. Database Connection

- **Singleton Pattern** (`Core/Database.php`): Đảm bảo chỉ 1 PDO connection per request.
- **Auto-reconnect**: Tự động reconnect nếu PostgreSQL timeout.
- **SSL Support**: Hỗ trợ `DB_SSLMODE=require` cho Supabase.

---

## 3. QUY TRÌNH NGHIỆP VỤ (BUSINESS WORKFLOW)

Hệ thống hoạt động theo 5 bước chính của một đợt tuyển sinh:

1. **Khởi tạo dữ liệu danh mục (Setup):** BGH và quản trị viên thiết lập Đợt tuyển sinh, Danh mục Ngành (kèm chỉ tiêu), Tổ hợp môn xét tuyển, Mức điểm ưu tiên (Khu vực, Đối tượng), và Bảng quy đổi Chứng chỉ ngoại ngữ.
2. **Tiếp nhận dữ liệu (Ingestion):** Nhập hồ sơ cá nhân thí sinh, dữ liệu Nguyện vọng (thứ tự ưu tiên), Điểm kỳ thi THPT, Điểm Học bạ (lớp 10, 11, 12), Điểm thi Năng khiếu, Chứng chỉ tiếng Anh (IELTS, TOEIC...). *Dữ liệu này được làm sạch và lưu vào các bảng chuẩn hóa.*
3. **Tính điểm hàng loạt (Batch Scoring):** Hệ thống duyệt qua tất cả Nguyện vọng của từng thí sinh, tìm ra **Tổ hợp tối ưu nhất** và **Phương thức xét tuyển tối ưu nhất** dựa trên dữ liệu điểm hiện có.
4. **Lọc ảo nội bộ (Virtual Filtering):** BGH điều chỉnh mức điểm chuẩn dự kiến cho các ngành. Thuật toán Lọc ảo sẽ cho thí sinh "trượt" qua các nguyện vọng từ trên xuống dưới. Thí sinh đỗ nguyện vọng nào cao nhất thì các nguyện vọng sau tự động bị xóa bỏ. Kết quả trả về số lượng trúng tuyển thực tế để đối chiếu với chỉ tiêu.
5. **Công bố & Báo cáo (Reporting):** Chốt danh sách, xuất file Excel, và cập nhật trạng thái trúng tuyển.

---

## 4. CƠ SỞ DỮ LIỆU CỐT LÕI (CORE DATABASE ENTITIES)

Hệ thống được thiết kế theo cấu trúc chuẩn hóa cao để lưu trữ khối lượng lớn dữ liệu:

### 4.1. Bảng dữ liệu chính

| Bảng | Mô tả | Khóa chính |
|------|--------|------------|
| `thi_sinh` | Thông tin gốc thí sinh (CCCD, họ tên, ngày sinh, liên hệ, địa chỉ, ảnh chân dung, trường THPT...) | `so_cccd` |
| `ho_so_xet_tuyen` | Hồ sơ xét tuyển theo đợt (trạng thái duyệt, ghi chú, người duyệt) | `id` |
| `nguyen_vong` | Nguyện vọng đăng ký (mã ngành, thứ tự NV, tổ hợp, điểm xét tuyển, trạng thái trúng tuyển) | `id` |
| `ket_qua_hoc_tap` | Điểm tổng kết cuối năm 11 môn × 3 lớp (10, 11, 12) — HK1, HK2, cả năm | `id` |
| `diem_thi_thpt` | Điểm 9 môn thi tốt nghiệp THPT | `so_cccd` |
| `diem_nang_khieu` | Điểm thi năng khiếu tại trường | `id` |
| `diem_nang_khieu_ngoai` | Điểm năng khiếu do trường khác cấp | `id` |
| `chung_chi_thi_sinh` | Chứng chỉ ngoại ngữ gốc (IELTS, TOEIC...) | `id` |
| `diem_chung_chi_ngoai_ngu` | Điểm quy đổi chứng chỉ (sau khi áp dụng bảng mapping) | `id` |

### 4.2. Bảng danh mục (Master Data)

| Bảng | Mô tả |
|------|--------|
| `dot_tuyen_sinh` | Đợt tuyển sinh (mã đợt, tên đợt, năm, ngày bắt đầu/kết thúc, trạng thái active) |
| `dm_nganh` | Danh mục ngành đào tạo (mã ngành, tên, chi tiêu, nhóm ngành, cờ năng khiếu/chứng chỉ) |
| `dm_to_hop` | Tổ hợp môn xét tuyển (A00, A01, D01...) |
| `nganh_to_hop` | Mapping N-N giữa ngành và tổ hợp |
| `dm_tinh`, `dm_huyen`, `dm_xa` | Địa giới hành chính 3 cấp |
| `dm_truong_thpt` | Danh mục trường THPT cả nước |
| `dm_khu_vuc` | Khu vực ưu tiên (KV1, KV2, KV2-NT, KV3) |
| `dm_doi_tuong` | Đối tượng ưu tiên (ĐT01-ĐT07) |
| `cau_hinh_chung_chi` | Bảng mapping quy đổi chứng chỉ (VD: IELTS 6.5 → 10.0 điểm Tiếng Anh) |

### 4.3. Bảng Snapshot & Kết quả

| Bảng | Mô tả |
|------|--------|
| `v_calc_summary` | **SSOT** — Bảng snapshot lưu kết quả ĐÃ TÍNH (điểm xét tuyển, tổ hợp tối ưu, phương thức tối ưu, chi tiết điểm JSON). Frontend chỉ đọc bảng này. |
| `nguyen_vong.diem_xet_tuyen` | Điểm xét tuyển cuối cùng sau tính toán |
| `nguyen_vong.trang_thai_trung_tuyen` | Boolean đánh dấu trúng tuyển sau lọc ảo |

### 4.4. Bảng hệ thống

| Bảng | Mô tả |
|------|--------|
| `quan_tri_vien` | Tài khoản admin (username, password Argon2ID, role, 2FA secret) |
| `roles`, `role_permissions` | RBAC — Phân quyền theo vai trò |
| `audit_logs` | Nhật ký hoạt động admin (action, entity, IP, user-agent) |
| `login_attempts` | Theo dõi đăng nhập thất bại |
| `online_tracking` | Theo dõi người dùng online real-time |
| `email_queue` | Hàng đợi email chờ gửi |
| `email_templates` | Mẫu email (tài khoản, kết quả duyệt hồ sơ...) |
| `settings` | Cấu hình hệ thống (SMTP, trang chủ, video...) |
| `notifications` | Thông báo đẩy đến thí sinh |
| `posts` | Bài viết/tin tức tuyển sinh |

---

## 5. GIẢI THUẬT TÍNH ĐIỂM (THE SCORING ENGINE)

Đây là "trái tim" của hệ thống (File `ScoreCalculationService.php` — 47KB). Thuật toán quét qua từng nguyện vọng và tính điểm theo các phân hệ sau:

### 5.1. Cơ chế Xét tổ hợp tối ưu
Một ngành có thể xét tuyển bằng nhiều phương thức (PT100 - Điểm thi THPT, PT200 - Điểm Học bạ) và nhiều tổ hợp (VD: A00, A01, D01...).
- Thuật toán sẽ lặp qua **TẤT CẢ** các tổ hợp được phép của chuyên ngành đó.
- Lặp qua 2 phương thức PT100 và PT200.
- Chọn ra cặp Tổ hợp + Phương thức mang lại **tổng điểm cao nhất** cho thí sinh.

### 5.2. Xử lý Chứng chỉ Ngoại ngữ & Năng khiếu
Khi tính điểm 3 môn trong một tổ hợp:
- **Chứng chỉ ngoại ngữ (VD môn Tiếng Anh):** Thuật toán tìm điểm thi thực tế (THPT hoặc Học bạ), sau đó tìm điểm Quy đổi từ chứng chỉ IELTS. Điểm môn học = `MAX(Diểm_Thực_Tế, Điểm_Quy_Đổi)`.
- **Năng khiếu:** Nếu tổ hợp có môn đặc thù (V00), hệ thống ưu tiên lấy điểm từ DB của trường (`diem_nang_khieu`), nếu không có mới lấy điểm nộp từ trường ngoài (`diem_nang_khieu_ngoai`). Môn năng khiếu thường không có điểm gốc từ THPT/Học bạ.

### 5.3. Quy đổi Hệ số Học bạ
Đối với PT200 (Học bạ lớp 12), tổng 3 môn xét tuyển truyền thống sẽ bị nhân với một **Hệ số học bạ** (thường là `0.95`, được cấu hình trong DB) nhằm chống lạm phát điểm.
*Formula: Total_Raw = (M1 + M2 + M3) * 0.95*

### 5.4. Giải thuật Điểm Ưu tiên (Khu vực & Đối tượng)
Tuân thủ nghiêm ngặt quy chế hiện hành của Bộ GD&ĐT:
- **Ưu tiên Đối tượng (ĐT01 - ĐT07):** Luôn được áp dụng dựa trên danh mục gốc.
- **Ưu tiên Khu vực (KV1, KV2, KV2-NT, KV3):** 
  *Chính sách: Chỉ có hiệu lực trong năm tốt nghiệp THPT và 1 năm kế tiếp.*
  *Algorithm:* `if (Current_Year - Nam_Tot_Nghiep > 1)` -> Điểm ưu tiên khu vực = `0`.
- **Giảm trừ điểm ưu tiên cho thí sinh xuất sắc:** 
  Nếu tổng điểm 3 môn (Total_Raw) >= `22.5` điểm, điểm ưu tiên không cộng thẳng mà phải nội suy giảm dần.
  *Formula:* `Priority_Converted = ((30 - Total_Raw) / 7.5) * Total_Priority_Raw`.

### 5.5. Cơ chế Dirty Checking & Chunk Processing
Để tính điểm cho 15.000+ thí sinh không làm sập server:
- Giao diện (JS) cắt file danh sách CCCD thành nhiều mảng nhỏ (Chunks, size = 500) và gọi API đệ quy.
- **Dirty Checking:** Thuật toán băm (MD5 Hash) toàn bộ dữ liệu thô của thí sinh (THPT+HB+Priority). Nếu hash không đổi so với lần tính trước -> `Skip` để tiết kiệm CPU. Trừ khi có cờ bẻ hệ thống `force_recalculate=true` (Tính lại toàn bộ).

---

## 6. THUẬT TOÁN LỌC ẢO DÂY CHUYỀN (VIRTUAL FILTER ALGORITHM)

Giải thuật giả lập quá trình xét tuyển thực tế để quyết định ai trúng tuyển ngành nào. (File `VirtualFilterService.php`).

**Nguyên tắc gốc:** "Thí sinh trúng tuyển vào nguyện vọng cao nhất có thể. Khi đã trúng tuyển 1 nguyện vọng, các nguyện vọng xếp sau mặc định bị hủy bỏ."

**Luồng chạy:**
1. Hội đồng tuyển sinh nhập **Điểm chuẩn dự kiến** cho mỗi chuyên ngành.
2. Thuật toán gom toàn bộ danh sách nguyện vọng, **sắp xếp theo CCCD và Thứ tự ưu tiên (1 -> N)**.
3. Duyệt qua từng thí sinh. Với mỗi nguyện vọng:
   - Nếu `Điểm xét tuyển >= Điểm chuẩn của ngành đó`, thí sinh được đánh dấu là **ĐỖ (Trúng tuyển)** nguyện vọng này.
   - Ngay lập tức, thuật toán nhảy sang thí sinh tiếp theo (Bỏ qua các nguyện vọng kém ưu tiên hơn của thí sinh hiện tại).
4. Hệ thống đếm tổng số lượng thí sinh "ĐỖ" cho mỗi ngành và trả về thống kê để Hội đồng điều chỉnh điểm chuẩn lên/xuống cho đến khi Khớp Chỉ Tiêu.

---

## 7. QUẢN LÝ HỒ SƠ THÍ SINH (CANDIDATE MANAGEMENT)

### 7.1. Quy trình đăng ký thí sinh

Thí sinh đăng ký qua giao diện public theo 5 bước tuần tự:

| Bước | Controller | Mô tả |
|------|-----------|--------|
| Step 1 | `ProfileController` | Thông tin cá nhân: họ tên, CCCD, ngày sinh, giới tính, dân tộc, quê quán, ảnh chân dung, giấy tờ tùy thân |
| Step 2 | `AcademicController` | Nhập kết quả học tập lớp 10, 11, 12 (11 môn × 3 kỳ × 3 lớp) |
| Step 3 | `AcademicController` | Upload chứng chỉ ngoại ngữ (IELTS, TOEIC, VSTEP...) và minh chứng |
| Step 4 | *tự động* | Hệ thống tự xác nhận đủ dữ liệu bắt buộc |
| Step 5 | `ApplicationController` | Đăng ký nguyện vọng (chọn ngành, tổ hợp, thứ tự ưu tiên) → Nộp hồ sơ |

### 7.2. Trạng thái hồ sơ (UserStatus)

Hồ sơ đi qua các trạng thái được định nghĩa trong `Core/UserStatus.php`:

```
Chờ duyệt → Đã duyệt
           → Từ chối
           → Yêu cầu sửa (thí sinh cần bổ sung) → Chờ duyệt (sau khi sửa)
```

### 7.3. Chức năng xét duyệt hồ sơ (Review Management)

Trang **Review Management** (`/admin/review-management`) là trung tâm điều hành cho chuyên viên tuyển sinh:

**Bộ lọc thông minh:**
- Lọc theo: Đợt tuyển sinh, Khóa tuyển sinh, Trạng thái hồ sơ, Học bạ đủ/thiếu
- Lọc theo cột: SĐT, Ngày sinh, Tỉnh/Thành, Trường THPT, Ngành NV1, Giới tính, Dân tộc, Khu vực, Đối tượng, Năm tốt nghiệp, Email, Ghi chú
- Tìm kiếm toàn cục: theo tên, CCCD, email

**Các chức năng hàng loạt (Bulk Actions):**

| Chức năng | Mô tả | Kỹ thuật |
|-----------|--------|----------|
| **Duyệt theo file** | Upload file Excel chứa danh sách CCCD → Duyệt hàng loạt | `PhpSpreadsheet::Xlsx` reader với `setReadDataOnly(true)`, batch UPDATE, grouped note updates |
| **Duyệt tất cả** | Duyệt toàn bộ hồ sơ "Chờ duyệt" trong đợt | Single SQL UPDATE |
| **Hủy duyệt tất cả** | Reset toàn bộ về "Chờ duyệt", xóa người duyệt | Transaction: UPDATE `ho_so_xet_tuyen` + sync `nguyen_vong` |
| **Cập nhật học bạ** | Upload file Excel điểm học bạ → Cập nhật hàng loạt | Parse 19 cột/dòng, validate lớp 10/11/12, upsert per candidate |
| **Thùng rác** | Xem và khôi phục hồ sơ đã xóa mềm | Soft delete (`deleted_at`) |

**Tối ưu hiệu năng "Duyệt theo file":**
1. Sử dụng `\PhpOffice\PhpSpreadsheet\Reader\Xlsx` thay vì `IOFactory::load` → bỏ qua auto-detect format.
2. Bật `setReadDataOnly(true)` → bỏ qua style, format, formula → giảm 70-80% RAM/thời gian.
3. Gộp ghi chú theo nhóm (`noteGroups`): Nếu 500 thí sinh có cùng ghi chú "Đạt", chỉ cần 1 câu SQL thay vì 500.
4. Sử dụng `UserStatus::APPROVED` constant thay vì hardcode chuỗi tiếng Việt → tránh lỗi encoding UTF-8.
5. Đồng bộ trạng thái sang bảng `nguyen_vong` trong cùng transaction.

### 7.4. Xem chi tiết hồ sơ (Review Detail)

Trang `/admin/review` hiển thị toàn bộ thông tin thí sinh qua **Lazy-loaded Tabs**:

| Tab | Nội dung | Kỹ thuật |
|-----|----------|----------|
| Thông tin cá nhân | Ảnh chân dung, CCCD, họ tên, liên hệ, địa chỉ | Eager load (hiển thị ngay) |
| Học bạ | Ma trận điểm 11 môn × 3 lớp × 3 kỳ | AJAX lazy load |
| Chứng chỉ | Chứng chỉ ngoại ngữ, minh chứng | AJAX lazy load |
| Điểm THPT | 9 môn thi tốt nghiệp | AJAX lazy load |
| Nguyện vọng | Danh sách NV kèm tên ngành từ `dm_nganh` | AJAX lazy load |

**Kỹ thuật "Batch Tab Loading":** Thay vì 4 AJAX request riêng lẻ, hệ thống gộp thành 1 request duy nhất (`/admin/review/batch-tabs`) trả về HTML của tất cả tab cùng lúc.

**Review Bundle:** Method `getReviewBundle($cccd)` thực hiện 1 single query với JSON aggregation:
```sql
SELECT t.*, 
  (SELECT json_agg(...) FROM ket_qua_hoc_tap) as _academic_json,
  (SELECT json_agg(...) FROM nguyen_vong) as _choices_json,
  (SELECT json_agg(...) FROM chung_chi_thi_sinh) as _certs_json,
  (SELECT row_to_json(...) FROM diem_thi_thpt) as _diemthi_json
FROM thi_sinh t ...
```
→ 1 query thay vì 5 queries riêng lẻ.

### 7.5. Quản lý thí sinh nâng cao

| Chức năng | Route | Mô tả |
|-----------|-------|--------|
| Sửa hồ sơ | `/admin/candidates/edit` | Admin chỉnh sửa trực tiếp thông tin thí sinh |
| Đổi mật khẩu | `/admin/candidates/change-password` | Reset password + gửi email thông báo |
| Chuyển đợt | `/admin/candidates/transfer` | Chuyển thí sinh sang đợt tuyển sinh khác |
| Xóa mềm | `/admin/candidates/delete` | Soft delete + khôi phục |
| Xóa cứng | `/admin/candidates/force-delete` | Xóa vĩnh viễn tất cả bảng liên quan (8 bảng) trong transaction |
| Bulk email | `/admin/candidates/bulk-action` (type: email) | Gửi email hàng loạt cho nhóm thí sinh được chọn |

---

## 8. HỆ THỐNG IMPORT DỮ LIỆU (DATA IMPORT)

### 8.1. Import Service (`ImportService.php`)

Hệ thống hỗ trợ import hàng loạt từ file Excel cho nhiều loại dữ liệu:

| Loại Import | Mô tả | File chính |
|-------------|--------|-----------|
| Thí sinh | Import thông tin gốc: CCCD, họ tên, ngày sinh, liên hệ, trường THPT... | `parseCandidates()` |
| Điểm THPT | Import 9+ môn thi từ file Bộ GD&ĐT | `parseThptScores()` |
| Nguyện vọng | Import danh sách nguyện vọng đăng ký | `parseWishes()` |
| Điểm năng khiếu | Import điểm thi năng khiếu | `parseAptitudeScores()` |

**Kỹ thuật xử lý:**
- **Column Filter** (`ColumnFilter`): Giới hạn chỉ đọc N cột đầu tiên → tiết kiệm RAM cho file lớn.
- **Dual Format Support**: Hỗ trợ cả `.xlsx` (PhpSpreadsheet) lẫn `.xls` (SimpleXLS parser tự viết — 53KB).
- **Progress Tracking**: Ghi file JSON `import_progress_{token}.json` → Frontend poll AJAX để hiển thị % tiến trình.
- **Batch Upsert**: Sử dụng `INSERT ... ON CONFLICT DO UPDATE` (PostgreSQL) để thêm mới hoặc cập nhật nếu đã tồn tại.
- **CCCD Normalization**: Chuẩn hóa CCCD (xử lý scientific notation `1.2E+11`, padding leading zeros, strip ký tự không phải số).

### 8.2. Import Repository (`ImportRepository.php`)

Lớp Repository thực hiện bulk operations trực tiếp:
- `upsertBatchCandidates()`: Bulk upsert thí sinh.
- `upsertBatchThptScores()`: Bulk upsert điểm THPT.
- `upsertBatchWishes()`: Bulk upsert nguyện vọng.

---

## 9. HỆ THỐNG XUẤT DỮ LIỆU & BÁO CÁO (EXPORT & REPORTING)

### 9.1. Export Service (`ExportService.php`)

Hệ thống xuất dữ liệu ra 2 định dạng:

**Định dạng:**
- **Excel XML SpreadsheetML** (`.xls`): Xuất bằng XML thuần, có định dạng header, border, font. Đảm bảo CCCD không bị mất leading zeros bằng `ss:Type="String"`.
- **CSV UTF-8 BOM**: Thêm BOM header `0xEF 0xBB 0xBF` để Excel tự nhận diện UTF-8.

**Các loại báo cáo:**

| Báo cáo | Mô tả |
|---------|--------|
| Danh sách thí sinh | Thông tin cá nhân + trạng thái hồ sơ |
| Danh sách trúng tuyển theo ngành | Chỉ thí sinh có `trang_thai_trung_tuyen = TRUE` |
| Dữ liệu xét tuyển toàn bộ | NV + điểm + tổ hợp + trạng thái |
| Danh sách chứng chỉ | Thí sinh có chứng chỉ ngoại ngữ |
| Danh sách thi năng khiếu | Thí sinh đăng ký ngành Sư phạm đặc thù |

### 9.2. Xuất dữ liệu theo chuẩn Bộ GD&ĐT (MOET Format)

Ba báo cáo MOET quan trọng:
1. **MOET Thông tin thí sinh** (`exportMoetInfoCsv`): 40+ cột theo mẫu PHẦN MỀM XÉT TUYỂN CHUNG.
2. **MOET Nguyện vọng** (`exportMoetWishesCsv`): Mã trường THV, mã xét tuyển, mã phương thức, mã tổ hợp.
3. **MOET Học bạ** (`exportMoetTranscriptsCsv`): Điểm chi tiết HK1/HK2/CN cho 13 môn.

### 9.3. Report Controller

| Route | Chức năng |
|-------|-----------|
| `/admin/reports` | Trang báo cáo tổng hợp |
| `/admin/reports/export-candidates` | Xuất danh sách thí sinh |
| `/admin/reports/export-admitted` | Xuất danh sách trúng tuyển |
| `/admin/reports/export-moet-*` | Xuất theo chuẩn Bộ |

---

## 10. HỆ THỐNG BẢO MẬT (SECURITY)

### 10.1. Authentication

**Thí sinh:**
- Đăng nhập bằng CCCD + mật khẩu.
- Hỗ trợ "Nhớ đăng nhập" (Remember Me) qua token lưu cookie.
- Quên mật khẩu qua email (OTP + link reset).

**Admin:**
- Đăng nhập bằng username + password.
- Hỗ trợ **2FA (Two-Factor Authentication)** qua Google Authenticator.
- Backup codes (8 mã dùng một lần) phòng mất thiết bị.

### 10.2. Xác thực 2 yếu tố (2FA)

File `TwoFactorService.php` triển khai TOTP theo chuẩn RFC 6238:

```
Flow: Admin bật 2FA → Scan QR Code → Nhập mã xác nhận → Từ giờ mỗi lần login phải nhập mã 6 số
```

**Kỹ thuật:**
- Thuật toán: HMAC-SHA1 với time step 30 giây.
- Secret: Base32 encoded, 16 ký tự.
- Verification window: ±1 time step (chấp nhận mã trước/sau 30 giây).
- Backup codes: 8 mã hex 8 ký tự, mỗi mã dùng 1 lần rồi xóa.

### 10.3. Password Hashing

```php
password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,  // 64MB
    'time_cost' => 4,
    'threads' => 3
]);
```

### 10.4. CSRF Protection

- Mọi form POST đều chứa `_csrf_token` (64 hex chars từ `random_bytes(32)`).
- Validation dùng `hash_equals()` để chống timing attack.
- Hỗ trợ cả POST field và `X-CSRF-TOKEN` header cho AJAX.

### 10.5. Security Headers

```
Content-Security-Policy: default-src 'self'; script-src ...; style-src ...; font-src ...
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

### 10.6. Rate Limiting

- Login: Tối đa 5 lần thất bại / 15 phút → Khóa tạm (session-based, không dùng DB).
- API endpoints: 30 requests / phút (configurable per route group).

### 10.7. Session Security

- Cookie flags: `HttpOnly`, `SameSite=Lax`, `Secure` (khi HTTPS).
- Session timeout: 30 phút không hoạt động → Auto-logout.
- Session regeneration: `session_regenerate_id(true)` sau khi login thành công.

---

## 11. PHÂN QUYỀN (RBAC - Role-Based Access Control)

### 11.1. Cấu trúc phân quyền

```
Roles (vai trò)
  └── role_permissions (gắn quyền)
        └── permissions (hành động)
              VD: dashboard, review, candidate.edit, settings.edit, stats, reports, accounts
```

### 11.2. Kiểm tra quyền

```php
// Trong mọi Controller method:
$this->checkPermission('review');  // Kiểm tra trước khi thực hiện
```

Controller `AdminAccountController` quản lý tạo/sửa/xóa tài khoản admin, gán vai trò.
Controller `RoleController` quản lý tạo/sửa vai trò và gắn danh sách quyền.

---

## 12. HỆ THỐNG EMAIL (EMAIL SERVICE)

### 12.1. SMTP Client tự viết

`MailerService.php` triển khai SMTP client **KHÔNG dùng thư viện ngoài** (PHPMailer, SwiftMailer):

```
Flow: fsockopen() → EHLO → STARTTLS → AUTH LOGIN → MAIL FROM → RCPT TO → DATA → QUIT
```

**Tính năng:**
- Hỗ trợ TLS (STARTTLS) và SSL (`ssl://`).
- UTF-8 encoding cho subject (Base64 `=?UTF-8?B?...?=`) và body.
- Fallback sang `mail()` nếu chưa cấu hình SMTP host.

### 12.2. Email Queue

Thay vì gửi email đồng bộ (chặn request), hệ thống đẩy vào hàng đợi:

```php
$mailer->enqueue($to, $subject, $body); // INSERT vào email_queue, status='pending'
```

Worker (cron job hoặc manual trigger) sẽ lấy email từ queue và gửi thực sự.

### 12.3. Email Templates

`EmailTemplateService.php` hỗ trợ:
- Template với placeholder: `{{ho_ten}}`, `{{ket_qua_chi_tiet}}`, `{{ghi_chu}}`.
- Auto-build review result HTML với danh sách trạng thái (ảnh chân dung, hồ sơ, ghi chú).
- Queue email từ template: `queueWithTemplate($email, 'application_reviewed', $data)`.

---

## 13. BACKUP & RESTORE

### 13.1. BackupService

`BackupService.php` thực hiện quy trình sao lưu tự động:

```
1. pg_dump → file .sql
2. gzip compress → file .sql.gz
3. Upload → Google Drive (thư mục "Backups")
4. Cleanup → Xóa file cũ > 7 ngày
```

**Tính năng nâng cao:**
- **Test Mode**: Tạo file mock để test pipeline mà không dump DB thật.
- **Dual Compression**: Ưu tiên `gzip` CLI, fallback sang `gzencode()` PHP.
- **Auto-cleanup**: Xóa backup local > 7 ngày.
- **Cloud Upload**: Upload lên Google Drive qua `FileUploader` (reuse logic upload ảnh hồ sơ).

### 13.2. Restore

```
1. Download .sql.gz từ danh sách
2. Decompress → .sql
3. DROP SCHEMA public CASCADE; CREATE SCHEMA public;
4. psql -f restore.sql
```

---

## 14. NHẬT KÝ HOẠT ĐỘNG (AUDIT LOG)

### 14.1. AuditService

Mọi hành động quan trọng của admin đều được ghi log:

```php
$this->auditService->log('RESET_PASSWORD', 'candidates', $cccd, $oldValue, $newValue);
```

**Dữ liệu ghi lại:**
- `admin_id`, `admin_name` — Ai thực hiện
- `action` — VD: `LOGIN`, `UPDATE_STATUS`, `BULK_APPROVE`, `RESET_PASSWORD`, `DELETE`
- `entity_type`, `entity_id` — Đối tượng bị tác động
- `old_value`, `new_value` — Giá trị trước/sau (JSON)
- `ip_address`, `user_agent` — Thông tin kỹ thuật
- `created_at` — Thời điểm

### 14.2. Login Attempt Tracking

Bảng `login_attempts` ghi lại mọi lần đăng nhập (thành công + thất bại), phục vụ:
- Phát hiện brute-force attack.
- Dashboard thống kê: "Số lần đăng nhập thất bại trong 24h".

### 14.3. Tự dọn dẹp

```php
$auditService->purgeOldRecords(20); // Xóa log > 20 ngày
$auditService->clearAll(); // Xóa toàn bộ (admin supreme only)
```

---

## 15. THEO DÕI TRỰC TUYẾN (ONLINE TRACKING)

### 15.1. Cơ chế

Mỗi request đi qua `SecurityMiddleware::trackOnlineActivity()`:

```php
$repo->trackActivity($sessionId, $userId, $adminId, $ip, $userAgent);
// → INSERT ... ON CONFLICT(session_id) DO UPDATE last_activity = NOW()
```

### 15.2. Dashboard Online Stats

API `/admin/stats/api` trả về real-time (never cached):
- Số thí sinh online (last 15 min).
- Số admin online.
- Tổng sessions.

---

## 16. TRANG CHỦ PUBLIC & TIỆN ÍCH

### 16.1. Landing Page (`home.php`)

- Video giới thiệu (YouTube embed, configurable từ admin).
- Thống kê nổi bật: Số ngành, Chỉ tiêu, Tỷ lệ việc làm.
- Thông báo tuyển sinh (announcement banner).
- Danh sách ngành đào tạo (load từ `dm_nganh`).
- Tin tức tuyển sinh (load từ `posts`).

### 16.2. Máy tính điểm (Score Calculator)

`CalculatorController.php` + `calculator.php` — Công cụ public cho thí sinh:
- Nhập điểm học bạ hoặc điểm THPT.
- Chọn khu vực/đối tượng ưu tiên.
- Hệ thống tự động tính điểm xét tuyển và gợi ý ngành phù hợp.

### 16.3. Thông báo đẩy

`NotificationController.php`: Admin tạo thông báo → Hiển thị cho thí sinh khi đăng nhập.
Thí sinh đánh dấu đã đọc qua bảng `notification_reads`.

---

## 17. GIAO DIỆN VÀ TRẢI NGHIỆM TƯƠNG TÁC (UI/UX DESIGN)

### 17.1. Admin Dashboard

Trang tổng quan (`/admin/dashboard`) với:
- **Biểu đồ đăng ký theo ngày** (line chart): Fetch qua AJAX, hỗ trợ filter theo năm/đợt.
- **Thống kê trạng thái** (donut chart): Chờ duyệt, Đã duyệt, Từ chối, Yêu cầu sửa.
- **Top ngành đăng ký** (bar chart): Top 30 ngành nhiều NV1 nhất.
- **Thống kê nhân khẩu**: Giới tính, Khu vực, Đối tượng (combined query giảm 2 roundtrip DB).
- **Hoạt động gần đây**: 5 thí sinh đăng ký mới nhất.
- **Auto-fallback**: Nếu đợt hiện tại không có dữ liệu, tự chuyển sang đợt gần nhất có dữ liệu.

### 17.2. Màn hình Bảng lưới thí sinh (Virtual Admission Dashboard)

- Một DataTables cực rộng (Frozen Columns cho cột Tên và CCCD) chứa ma trận điểm: Điểm của 4 tổ hợp PT100, Điểm của 4 tổ hợp PT200, Điểm 3 môn thành phần chi tiết, Điểm quy đổi, Điểm Xét Tuyển cuối cùng và Trạng thái Trúng tuyển.
- Mọi dữ liệu fetch thông qua AJAX **Server-Side Processing (SSP)**, cho phép lọc, tìm kiếm và phân trang trực tiếp từ PostgresQL trên tập 15,000 hồ sơ chỉ trong <1s.

### 17.3. Premium Loading Experience (UX)

- Tính toán 15,000 thí sinh mất vài phút. Giao diện sử dụng 1 Modal Loading tràn màn hình (Blur background).
- Hiển thị thanh tiến trình (Progress Bar) % hoàn thành tương xứng với số Chunk AJAX đã xử lý.
- Dòng trạng thái (Status) hiển thị các thông báo tự động đảo vòng (VD: "Đang tính tổ hợp...", "Đang tính điểm quy đổi...") để giảm cảm giác chờ đợi của User.

### 17.4. Khối điều khiển Trung tâm

- Tích hợp các nút: Đồng bộ Dữ liệu -> Tính lại điểm số (Smart & Toàn bộ) -> Chạy Lọc ảo -> Xuất Excel, theo đúng tuần tự ngầm định của 1 chuyên viên tuyển sinh.

---

## 18. QUẢN TRỊ HỆ THỐNG (SYSTEM ADMINISTRATION)

### 18.1. Danh mục quản trị

| Module | Controller | Chức năng |
|--------|-----------|-----------|
| Đợt tuyển sinh | `SessionController` | CRUD đợt tuyển sinh, đánh dấu active |
| Ngành đào tạo | `MajorController` | CRUD ngành, gắn chỉ tiêu, cờ năng khiếu |
| Tổ hợp xét tuyển | `CombinationController` | CRUD tổ hợp, gắn mapping ngành-tổ hợp |
| Trường THPT | `SchoolController` | Import/quản lý danh mục trường cả nước |
| Khu vực & Đối tượng | `ZoneController` | Quản lý danh mục ưu tiên + điểm cộng |
| Quy tắc chứng chỉ | `CertificateRuleController` | Bảng mapping quy đổi IELTS/TOEIC → điểm |
| Cấu hình hệ thống | `SettingController` | SMTP, trang chủ, video, thông báo |
| Tài khoản admin | `AdminAccountController` | CRUD admin, phân vai trò |
| Vai trò & quyền | `RoleController` | CRUD roles, gắn permissions |
| Nhật ký hoạt động | `AuditController` | Xem audit logs, login attempts |
| Backup/Restore | `BackupController` | Sao lưu/khôi phục database |
| Profile admin | `AdminProfileController` | Admin tự sửa thông tin, đổi mật khẩu |

### 18.2. Quản lý điểm chuyên biệt

| Module | Controller | Mô tả |
|--------|-----------|--------|
| Điểm năng khiếu | `AptitudeScoreController` | CRUD điểm thi năng khiếu nội bộ |
| Điểm chứng chỉ | `CertificateScoreController` | CRUD điểm quy đổi chứng chỉ |
| Cấu hình tính điểm | `ScoringSettingsController` | Hệ số học bạ, ngưỡng điểm... |

---

## 19. ĐẶC ĐIỂM KỸ THUẬT QUAN TRỌNG

### 19.1. File Upload & Cloud Storage

`Core/FileUploader.php` (10KB) hỗ trợ:
- **Local storage**: Lưu file vào `public/uploads/`.
- **Google Drive**: Upload qua Google Drive API v3. Hỗ trợ create folder, upload file, get link.
- **Dual mode**: Cấu hình `UPLOAD_DRIVER=local|google` trong `.env`.
- **Image rotation**: Admin có thể xoay ảnh chân dung 90° (cả local và Google Drive files).

### 19.2. Validation Service

`ValidationService.php`: Validate dữ liệu thí sinh trước khi cho nộp hồ sơ:
- Kiểm tra đủ thông tin bắt buộc (họ tên, CCCD, ngày sinh...).
- Kiểm tra đủ 6 kỳ học bạ (lớp 10, 11, 12 × HK1, HK2).
- Kiểm tra có ít nhất 1 nguyện vọng.
- Kiểm tra ảnh chân dung đã upload.

### 19.3. Workflow Service

`WorkflowService.php`: Quản lý luồng trạng thái hồ sơ:
- Xác định hồ sơ ở bước nào (1-5).
- Kiểm tra điều kiện chuyển bước.
- Tự động redirect nếu chưa hoàn thành bước trước.

### 19.4. Rule Engine

`RuleEngine.php`: Dynamic rule evaluation cho chứng chỉ:
- Đánh giá chứng chỉ ngoại ngữ theo bảng quy tắc.
- Tra cứu điểm quy đổi dựa trên loại chứng chỉ + điểm/xếp loại.

### 19.5. Password Reset Flow

`PasswordResetService.php`:
```
Thí sinh quên mật khẩu → Nhập email + CCCD → Hệ thống verify → Gửi email OTP/link reset → Đặt mật khẩu mới
```

---

## 20. YÊU CẦU DÀNH CHO AI IMPLEMENTATION

Nếu 1 AI/Agent khác cần xây dựng hệ thống tương tự, cần chú ý 6 Key Constraints (Ràng buộc lõi):

1. **Performance over Code elegance:** Khi đụng đến bảng kết quả tuyển sinh, sử dụng Bulk INSERT/UPDATE (`INSERT ... ON CONFLICT` hoặc tạo `TEMPORARY TABLE`) thay vì loop lưu từng record.

2. **Deterministic Scores:** Hàm tính điểm phải luôn xử lý được Input động. Thí sinh có thể có hoặc chưa có dữ liệu ở bất kỳ bước nào (có điểm hóa, không điểm lý, có chứng chỉ nhưng không có học bạ).

3. **Data Integrity:** Bảng `v_calc_summary` đóng vai trò là SSOT (Single Source of Truth) kết nối giữa Backend tính toán cực nhọc và Frontend hiển thị siêu nhẹ. Không bao giờ query Join dữ liệu thô ra Frontend.

4. **Encoding Safety:** Luôn sử dụng hằng số (`UserStatus::APPROVED`) hoặc prepared statements thay vì hardcode chuỗi tiếng Việt trong SQL. Lỗi encoding UTF-8 (VD: `Ä áº£ duyá»‡t` thay vì `Đã duyệt`) là lỗi nghiêm trọng có thể phá vỡ toàn bộ trạng thái dữ liệu.

5. **Session-Aware Queries:** Mọi query lấy dữ liệu nguyện vọng/hồ sơ PHẢI lọc theo `dot_tuyen_sinh_id` (mã đợt tuyển sinh). Nếu không, dữ liệu từ các đợt cũ sẽ nhiễu vào đợt hiện tại.

6. **Security-First:** CSRF token cho mọi form POST, Argon2ID hashing, 2FA cho admin, CSP headers, rate limiting, audit logging. Không thỏa hiệp bảo mật dù hệ thống nội bộ.

---

## 21. DANH SÁCH CONTROLLERS VÀ CHỨC NĂNG

| # | Controller | Số dòng | Chức năng chính |
|---|-----------|---------|----------------|
| 1 | `CandidateController` | 1766 | Quản lý thí sinh, bulk actions, duyệt hồ sơ |
| 2 | `AdminController` | 907 | Dashboard, review detail, stats API |
| 3 | `ProfileController` | 900+ | Đăng ký thí sinh (step 1), upload ảnh |
| 4 | `MasterDataController` | 800+ | CRUD tất cả danh mục |
| 5 | `AdmissionController` | 700+ | Quản lý xét tuyển, điểm chuẩn |
| 6 | `ApplicationController` | 600+ | Đăng ký nguyện vọng (step 5), nộp hồ sơ |
| 7 | `AuthController` | 500+ | Login (admin + thí sinh), logout, 2FA verify |
| 8 | `AcademicController` | 400+ | Nhập học bạ (step 2-3) |
| 9 | `VirtualAdmissionController` | 400+ | Dashboard xét tuyển ảo |
| 10 | `ReportController` | 350+ | Xuất báo cáo Excel/CSV |
| 11 | `VirtualFilterController` | 250+ | Chạy thuật toán lọc ảo |
| 12 | `ImportController` | 200+ | Import dữ liệu từ file |

---

## 22. DANH SÁCH SERVICES VÀ CHỨC NĂNG

| # | Service | Kích thước | Chức năng |
|---|---------|-----------|-----------|
| 1 | `ScoreCalculationService` | 47KB | **Core** — Giải thuật tính điểm xét tuyển |
| 2 | `ExportService` | 31KB | Xuất Excel/CSV (7 loại báo cáo + MOET format) |
| 3 | `ImportService` | 21KB | Import dữ liệu Excel (4 loại) |
| 4 | `ScoreCalculator` | 13KB | Helper tính điểm riêng lẻ (cho Calculator public) |
| 5 | `VirtualFilterService` | 11KB | Thuật toán lọc ảo dây chuyền |
| 6 | `BackupService` | 8.6KB | Sao lưu/khôi phục PostgreSQL + Google Drive |
| 7 | `MailerService` | 8.5KB | SMTP client thuần (TLS/SSL, queue) |
| 8 | `TwoFactorService` | 6.7KB | TOTP 2FA (RFC 6238) |
| 9 | `PermissionService` | 6.3KB | Quản lý RBAC permissions |
| 10 | `AuditService` | 6.1KB | Ghi nhật ký hoạt động |
| 11 | `PasswordResetService` | 5.8KB | Quên mật khẩu + OTP |
| 12 | `AdmissionService` | 5.6KB | Nghiệp vụ tuyển sinh |
| 13 | `PdfService` | 5.4KB | Xuất PDF giấy báo nhập học |
| 14 | `ValidationService` | 5.2KB | Validate dữ liệu hồ sơ |
| 15 | `WorkflowService` | 4.4KB | Quản lý luồng đăng ký 5 bước |
| 16 | `EmailTemplateService` | 4.2KB | Templates email + placeholders |
| 17 | `RuleEngine` | 4KB | Dynamic rule evaluation cho chứng chỉ |

---

*Tài liệu cập nhật lần cuối: 19/04/2026. Phiên bản hệ thống: v13-OPTIMIZED.*
