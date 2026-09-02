---
name: TS Design System
description: Impeccable Design System for University Admissions & Enrollment Operations. Prioritizes data legibility, swift administrative workflows, and polished candidate experience.
colors:
  # Brand anchors
  brand-primary: "oklch(38% 0.14 252)"       # Deep Academic Navy (#1e3a8a)
  brand-accent: "oklch(62% 0.19 45)"         # Crimson Academic Gold/Red (#c2410c)
  brand-surface: "oklch(98% 0.005 250)"      # Soft Crisp Slate Background (#f8fafc)

  # Neutrals
  neutral-900: "oklch(20% 0.02 250)"        # Main text / Headlines (#0f172a)
  neutral-700: "oklch(40% 0.02 250)"        # Secondary body text (#334155)
  neutral-500: "oklch(60% 0.015 250)"       # Muted captions / table headers (#64748b)
  neutral-200: "oklch(90% 0.008 250)"       # Hairline borders / dividers (#e2e8f0)
  neutral-100: "oklch(95% 0.005 250)"       # Card background / input fill (#f1f5f9)

  # Status Ramps (Tuyển sinh)
  status-success: "oklch(55% 0.16 145)"     # Đã trúng tuyển / Đã duyệt (#16a34a)
  status-success-bg: "oklch(95% 0.04 145)"
  status-warning: "oklch(68% 0.15 75)"      # Cần bổ sung / Đang lọc ảo (#d97706)
  status-warning-bg: "oklch(96% 0.05 75)"
  status-danger: "oklch(52% 0.19 25)"       # Không đạt / Huỷ hồ sơ (#dc2626)
  status-danger-bg: "oklch(95% 0.04 25)"
  status-info: "oklch(58% 0.14 230)"        # Chờ tiếp nhận / Chưa xử lý (#0284c7)
  status-info-bg: "oklch(95% 0.03 230)"

typography:
  fontFamily:
    sans: "'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
    heading: "'Inter', system-ui, sans-serif"
    tabular: "'JetBrains Mono', 'SFMono-Regular', Consolas, monospace"
---

# TS Design System & UI Craft Guidelines

## 1. Triết lý Thiết kế (Design Philosophy)
1. **Dữ liệu là trung tâm (Data-First Clarity)**:
   - Thông tin điểm số, số CCCD, mã ngành, thứ tự nguyện vọng phải luôn hiển thị sắc nét, sử dụng số liệu cách dòng chuẩn (`tabular-nums`).
2. **Tối ưu tốc độ thao tác (Operate Speed)**:
   - Các màn hình duyệt hồ sơ và lọc ảo (`admin/enrollment/process.php`) phải giảm thiểu số lần click chuột, hỗ trợ phím tắt bàn phím và phản hồi tức thời (< 100ms).
3. **Thẩm mỹ chuẩn mực, không rập khuôn (Impeccable Craft)**:
   - Loại bỏ hoàn toàn AI Slop: Không dùng shadow mờ nhạt, không dùng gradient cầu vồng, không dùng thẻ lồng thẻ (nested cards), không bo tròn quá đà.

---

## 2. Quy chuẩn Bảng Dữ liệu & Xử lý Tuyển sinh (Data Grid & Tables)
- **Table Header**: `font-weight: 600`, `font-size: 0.8125rem` (13px), `text-transform: uppercase`, `letter-spacing: 0.05em`, cố định (`sticky top-0`).
- **Table Rows**:
  - Chiều cao dòng chuẩn: `48px` (thoải mái) hoặc `36px` (chế độ compact cho cán bộ duyệt nhiều hồ sơ).
  - Hover state rõ ràng: `background: oklch(96% 0.008 250)`.
  - Phân cách bằng đường kẻ mảnh `1px solid var(--neutral-200)`.
- **Badges trạng thái**: Padding `4px 8px`, `border-radius: 4px`, kết hợp icon nhận diện trạng thái.

---

## 3. Trạng thái Form & Tương tác Thí sinh (Forms & Candidate Journey)
- **Roadmap / Lộ trình hồ sơ**:
  - Thể hiện trực quan 4 bước: *Tiếp nhận hồ sơ -> Thẩm định minh chứng -> Xét tuyển & Lọc ảo -> Nhập học chính thức*.
- **Inline Validation**:
  - Thông báo lỗi ngay tại input bị sai (ví dụ: Số CCCD không hợp lệ, điểm tổng kết < 0 hoặc > 10).
  - Nút Submit tự động chuyển sang trạng thái Loading (Disabled + Spinner) để tránh duplicate request.
- **Empty States**:
  - Luôn có thông điệp hướng dẫn rõ ràng kèm nút hành động (ví dụ: "Chưa có hồ sơ nào cần duyệt hôm nay -> Xem danh sách đã hoàn tất").

---

## 4. Bảng kiểm duyệt chất lượng (Craft Floor Verification)
- [ ] Mọi màu chữ trên nền đều đạt chuẩn tương phản tối thiểu **4.5:1** (WCAG AA).
- [ ] Các thành phần tương tác (Button, Input, Checkbox) có hiệu ứng `focus-visible` rõ ràng.
- [ ] Không có layout shift (CLS) khi tải ảnh minh chứng hoặc bảng dữ liệu.
- [ ] Tương thích toàn diện trên thiết bị di động (Thí sinh thao tác trên Smartphone) và màn hình Desktop (Cán bộ làm việc).
