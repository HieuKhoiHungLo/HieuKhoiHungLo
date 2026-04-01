# TÀI LIỆU ĐẶC TẢ HỆ THỐNG XÉT TUYỂN VÀ LỌC ẢO (VIRTUAL ADMISSION SYSTEM)

Tài liệu này mô tả chi tiết toàn bộ chức năng, quy trình, giao diện và giải thuật tính toán của Hệ thống Xét tuyển và Lọc ảo nội bộ. Mục đích của tài liệu là giúp một hệ thống AI hoặc một đội ngũ phát triển khác có đủ thông tin để xây dựng ulang (rebuild) hoặc thiết kế một hệ thống tương tự.

---

## 1. TỔNG QUAN HỆ THỐNG (SYSTEM OVERVIEW)

Hệ thống Xét tuyển và Lọc ảo là lõi công nghệ giúp trường Đại học thực hiện việc tính toán điểm số cho hàng vạn thí sinh với nhiều phương thức xét tuyển khác nhau (THPT, Học bạ, Năng khiếu, Chứng chỉ), và sau đó chạy thuật toán phân bổ thí sinh trúng tuyển vào các ngành dựa trên điểm chuẩn và chỉ tiêu (Lọc ảo dây chuyền).

**Core Tech Stack hiện tại:**
- **Backend:** PHP thuần (Custom MVC Framework) để đảm bảo hiệu năng cao nhất.
- **Database:** PostgreSQL (hiện đang dùng qua Supabase).
- **Frontend:** HTML/CSS/JS thuần, sử dụng TailwindCSS (Utility-first CSS), Alpine.js (Lightweight reactivity), và jQuery DataTables (với Server-Side Processing).
- **Architecture:** Monolithic, tối ưu hóa xử lý Batch (hàng loạt) để tính điểm không bị timeout.

---

## 2. QUY TRÌNH NGHIỆP VỤ (BUSINESS WORKFLOW)

Hệ thống hoạt động theo 5 bước chính của một đợt tuyển sinh:

1. **Khởi tạo dữ liệu danh mục (Setup):** BGH và quản trị viên thiết lập Đợt tuyển sinh, Danh mục Ngành (kèm chỉ tiêu), Tổ hợp môn xét tuyển, Mức điểm ưu tiên (Khu vực, Đối tượng), và Bảng quy đổi Chứng chỉ ngoại ngữ.
2. **Tiếp nhận dữ liệu (Ingestion):** Nhập hồ sơ cá nhân thí sinh, dữ liệu Nguyện vọng (thứ tự ưu tiên), Điểm kỳ thi THPT, Điểm Học bạ (lớp 10, 11, 12), Điểm thi Năng khiếu, Chứng chỉ tiếng Anh (IELTS, TOEIC...). *Dữ liệu này được làm sạch và lưu vào các bảng chuẩn hóa.*
3. **Tính điểm hàng loạt (Batch Scoring):** Hệ thống duyệt qua tất cả Nguyện vọng của từng thí sinh, tìm ra **Tổ hợp tối ưu nhất** và **Phương thức xét tuyển tối ưu nhất** dựa trên dữ liệu điểm hiện có.
4. **Lọc ảo nội bộ (Virtual Filtering):** BGH điều chỉnh mức điểm chuẩn dự kiến cho các ngành. Thuật toán Lọc ảo sẽ cho thí sinh "trượt" qua các nguyện vọng từ trên xuống dưới. Thí sinh đỗ nguyện vọng nào cao nhất thì các nguyện vọng sau tự động bị xóa bỏ. Kết quả trả về số lượng trúng tuyển thực tế để đối chiếu với chỉ tiêu.
5. **Công bố & Báo cáo (Reporting):** Chốt danh sách, xuất file Excel, và cập nhật trạng thái trúng tuyển.

---

## 3. CƠ SỞ DỮ LIỆU CỐT LÕI (CORE DATABASE ENTITIES)

Hệ thống được thiết kế theo cấu trúc chuẩn hóa cao để lưu trữ khối lượng lớn dữ liệu:

- `thi_sinh`: Thông tin gốc (so_cccd (PK), ho_va_ten, nam_tot_nghiep, khu_vuc_uu_tien, doi_tuong_uu_tien).
- `nguyen_vong`: NV đăng ký (id, so_cccd, ma_nganh, thu_tu_nguyen_vong, trang_thai).
- `diem_thi_thpt`: Lưu 9 môn thi tốt nghiệp THPT.
- `ket_qua_hoc_tap`: Lưu điểm tổng kết cuối năm các môn của lớp 10, 11, 12.
- `diem_nang_khieu`: Lưu điểm môn thi Năng khiếu (tại cơ sở).
- `diem_nang_khieu_ngoai`: Lưu điểm môn thi năng khiếu (của trường khác cấp).
- `diem_chung_chi_ngoai_ngu`: Lưu chứng chỉ gốc của thí sinh (VD: IELTS 6.5).
- `cau_hinh_chung_chi`: Bảng mapping quy đổi (VD: IELTS 6.5 -> 10.0 môn Tiếng Anh).
- `v_calc_summary` (Snapshot Table): Bảng cực kỳ quan trọng lưu trữ kết quả ĐÃ TÍNH TOÁN (diem_xet_tuyen, to_hop_toi_uu, phuong_thuc_toi_uu, chi_tiet_diem JSON). Giúp UI load dữ liệu cực nhanh thay vì tính on-the-fly.

---

## 4. GIẢI THUẬT TÍNH ĐIỂM (THE SCORING ENGINE)

Đây là "trái tim" của hệ thống (File `ScoreCalculationService.php`). Thuật toán quét qua từng nguyện vọng và tính điểm theo các phân hệ sau:

### 4.1. Cơ chế Xét tổ hợp tối ưu
Một ngành có thể xét tuyển bằng nhiều phương thức (PT100 - Điểm thi THPT, PT200 - Điểm Học bạ) và nhiều tổ hợp (VD: A00, A01, D01...).
- Thuật toán sẽ lặp qua **TẤT CẢ** các tổ hợp được phép của chuyên ngành đó.
- Lặp qua 2 phương thức PT100 và PT200.
- Chọn ra cặp Tổ hợp + Phương thức mang lại **tổng điểm cao nhất** cho thí sinh.

### 4.2. Xử lý Chứng chỉ Ngoại ngữ & Năng khiếu
Khi tính điểm 3 môn trong một tổ hợp:
- **Chứng chỉ ngoại ngữ (VD môn Tiếng Anh):** Thuật toán tìm điểm thi thực tế (THPT hoặc Học bạ), sau đó tìm điểm Quy đổi từ chứng chỉ IELTS. Điểm môn học = `MAX(Diểm_Thực_Tế, Điểm_Quy_Đổi)`.
- **Năng khiếu:** Nếu tổ hợp có môn đặc thù (V00), hệ thống ưu tiên lấy điểm từ DB của trường (`diem_nang_khieu`), nếu không có mới lấy điểm nộp từ trường ngoài (`diem_nang_khieu_ngoai`). Môn năng khiếu thường không có điểm gốc từ THPT/Học bạ.

### 4.3. Quy đổi Hệ số Học bạ
Đối với PT200 (Học bạ lớp 12), tổng 3 môn xét tuyển truyền thống sẽ bị nhân với một **Hệ số học bạ** (thường là `0.95`, được cấu hình trong DB) nhằm chống lạm phát điểm.
*Formula: Total_Raw = (M1 + M2 + M3) * 0.95*

### 4.4. Giải thuật Điểm Ưu tiên (Khu vực & Đối tượng)
Tuân thủ nghiêm ngặt quy chế hiện hành của Bộ GD&ĐT:
- **Ưu tiên Đối tượng (ĐT01 - ĐT07):** Luôn được áp dụng dựa trên danh mục gốc.
- **Ưu tiên Khu vực (KV1, KV2, KV2-NT, KV3):** 
  *Chính sách: Chỉ có hiệu lực trong năm tốt nghiệp THPT và 1 năm kế tiếp.*
  *Algorithm:* `if (Current_Year - Nam_Tot_Nghiep > 1)` -> Điểm ưu tiên khu vực = `0`.
- **Giảm trừ điểm ưu tiên cho thí sinh xuất sắc:** 
  Nếu tổng điểm 3 môn (Total_Raw) >= `22.5` điểm, điểm ưu tiên không cộng thẳng mà phải nội suy giảm dần.
  *Formula:* `Priority_Converted = ((30 - Total_Raw) / 7.5) * Total_Priority_Raw`.

### 4.5. Cơ chế Dirty Checking & Chunk Processing
Để tính điểm cho 15.000+ thí sinh không làm sập server:
- Giao diện (JS) cắt file danh sách CCCD thành nhiều mảng nhỏ (Chunks, size = 500) và gọi API đệ quy.
- **Dirty Checking:** Thuật toán băm (MD5 Hash) toàn bộ dữ liệu thô của thí sinh (THPT+HB+Priority). Nếu hash không đổi so với lần tính trước -> `Skip` để tiết kiệm CPU. Trừ khi có cờ bẻ hệ thống `force_recalculate=true` (Tính lại toàn bộ).

---

## 5. THUẬT TOÁN LỌC ẢO DÂY CHUYỀN (VIRTUAL FILTER ALGORITHM)

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

## 6. GIAO DIỆN VÀ TRẢI NGHIỆM TƯƠNG TÁC (UI/UX DESIGN)

**1. Màn hình Bảng lưới thí sinh (Virtual Admission Dashboard)**
- Một DataTables cực rộng (Frozen Columns cho cột Tên và CCCD) chứa ma trận điểm: Điểm của 4 tổ hợp PT100, Điểm của 4 tổ hợp PT200, Điểm 3 môn thành phần chi tiết, Điểm quy đổi, Điểm Xét Tuyển cuối cùng và Trạng thái Trúng tuyển.
- Mọi dữ liệu fetch thông qua AJAX **Server-Side Processing (SSP)**, cho phép lọc, tìm kiếm và phân trang trực tiếp từ PostgresQL trên tập 15,000 hồ sơ chỉ trong <1s.

**2. Premium Loading Experience (UX)**
- Tính toán 15,000 thí sinh mất vài phút. Giao diện sử dụng 1 Modal Loading tràn màn hình (Blur background).
- Hiển thị thanh tiến trình (Progress Bar) % hoàn thành tương xứng với số Chunk AJAX đã xử lý.
- Dòng trạng thái (Status) hiển thị các thông báo tự động đảo vòng (VD: "Đang tính tổ hợp...", "Đang tính điểm quy đổi...") để giảm cảm giác chờ đợi của User.

**3. Khối điều khiển Trung tâm**
- Tích hợp các nút: Đồng bộ Dữ liệu -> Tính lại điểm số (Smart & Toàn bộ) -> Chạy Lọc ảo -> Xuất Excel, theo đúng tuần tự ngầm định của 1 chuyên viên tuyển sinh.

---

## 7. YÊU CẦU DÀNH CHO AI IMPLEMENTATION
Nếu 1 AI/Agent khác cần xây dựng hệ thống tương tự, cần chú ý 3 Key Constraints (Ràng buộc lõi):
1. **Performance over Code elegance:** Khi đụng đến bảng kết quả tuyển sinh, sử dụng Bulk INSERT/UPDATE (`INSERT ... ON CONFLICT` hoặc tạo `TEMPORARY TABLE`) thay vì loop lưu từng record.
2. **Deterministic Scores:** Hàm tính điểm phải luôn xử lý được Input động. Thí sinh có thể có hoặc chưa có dữ liệu ở bất kỳ bước nào (có điểm hóa, không điểm lý, có chứng chỉ nhưng không có học bạ).
3. **Data Integrity:** Bảng `v_calc_summary` đóng vai trò là SSOT (Single Source of Truth) kết nối giữa Backend tính toán cực nhọc và Frontend hiển thị siêu nhẹ. Không bao giờ query Join dữ liệu thô ra Frontend.
