# PRODUCT.md - Hệ Thống Tuyển Sinh (TS)

## 1. Tầm nhìn & Mục tiêu Sản phẩm (Product Vision)
Hệ thống **Tuyển sinh (TS)** là nền tảng quản trị và hỗ trợ xét tuyển đại học/cao đẳng toàn diện, đáp ứng đồng thời hai mặt trận:
1. **Cổng thông tin & Nộp hồ sơ Thí sinh (Candidate Portal)**: Trực quan, minh bạch, giảm thiểu sai sót khi nhập điểm và nộp hồ sơ trực tuyến.
2. **Hệ thống Quản trị & Điều hành Tuyển sinh (Admissions Administration & Ops)**: Hiệu suất cao, xử lý hàng chục nghìn hồ sơ, lọc ảo, xét tuyển tự động, phân tích dữ liệu và báo cáo theo thời gian thực.

---

## 2. Đối tượng Người dùng & Chế độ Tương tác (User Personas & Modes)

| Đối tượng | Vai trò chính | Chế độ giao diện (Mode) | Trọng tâm trải nghiệm |
|---|---|---|---|
| **Thí sinh & Phụ huynh** | Đăng ký xét tuyển, nộp minh chứng học bạ, tra cứu kết quả trúng tuyển | `Persuade` / `Operate` (Onboarding) | Dễ hiểu, quy trình từng bước rõ ràng, thông báo trạng thái tức thời |
| **Cán bộ Tuyển sinh** | Duyệt hồ sơ, xác minh chứng từ, nhập điểm, chăm sóc thí sinh | `Operate` (High Density) | Thao tác bàn phím nhanh, bảng dữ liệu cô đọng, duyệt batch |
| **Hội đồng & Quản trị viên** | Quản lý đề án tuyển sinh, chạy thuật toán lọc ảo, công bố điểm chuẩn | `Operate` (Mission-Critical) | Dữ liệu chính xác 100%, cảnh báo rủi ro chỉ tiêu, xuất báo cáo Bộ GD&ĐT |

---

## 3. Kiến trúc Chức năng Cốt lõi (Core Capabilities)

### 3.1. Quản lý Thí sinh & Hồ sơ (`ThiSinhRepository`, `ApplicationController`)
- Tiếp nhận hồ sơ qua form trực tuyến và import Excel số lượng lớn (`admin/import/index.php`).
- Phân loại thí sinh: Tự do, Học sinh THPT, Xét tuyển sớm, Xét điểm thi THPTQG.
- Quản lý minh chứng đính kèm (Ảnh CCCD, học bạ, chứng chỉ ngoại ngữ IELTS/TOEFL).

### 3.2. Quy trình Xét tuyển & Lọc ảo (`AdmissionController`, `EnrollmentController`)
- Tự động tính điểm xét tuyển theo các tổ hợp môn và phương thức xét tuyển.
- Thuật toán lọc ảo nhiều đợt, cân bằng chỉ tiêu giữa các ngành/chuyên ngành (`admin/enrollment/process.php`).
- Quản lý trạng thái: *Đang xử lý -> Đủ điều kiện trúng tuyển -> Đã xác nhận nhập học -> Bỏ nhập học*.

### 3.3. Báo cáo Thống kê & Đồng bộ Dữ liệu
- Báo cáo phân bố điểm, tỷ lệ hồ sơ hợp lệ / không hợp lệ.
- Đồng bộ dữ liệu với cổng tuyển sinh của Bộ Giáo dục & Đào tạo.

---

## 4. Tiêu chuẩn Kỹ thuật & Ràng buộc (Technical Constraints)
- **Backend**: PHP 8.x MVC Architecture + Repository Pattern.
- **Frontend**: HTML5 Semantic, Tailwind CSS v3/v4 & Modern Vanilla CSS, Alpine.js / Vanilla JS.
- **Hiệu năng**: Render bảng dữ liệu > 1,000 dòng mượt mà, tối ưu chỉ số LCP & CLS.
- **Bảo mật**: CSRF Protection trên toàn bộ POST requests, mã hóa dữ liệu nhạy cảm (CCCD, SĐT).
