# Kế hoạch đồng bộ và chuẩn hoá dữ liệu hồ sơ năng khiếu

Kế hoạch này giúp cấu hình môi trường kiểm thử cục bộ (localhost) với cơ sở dữ liệu NTK và triển khai một tiến trình chuẩn hoá dữ liệu giữa hai đợt tuyển sinh: **"Bổ sung hồ sơ năng khiếu"** và **"Ghi danh sớm"** theo 3 trường hợp nghiệp vụ được yêu cầu.

## User Review Required

> [!IMPORTANT]
> Tiến trình này sẽ thay đổi và xoá một số bản ghi trong các bảng dữ liệu `ho_so_xet_tuyen`, `nguyen_vong`, `diem_nang_khieu` và `diem_chung_chi` trên cơ sở dữ liệu để đạt trạng thái chuẩn hoá (chỉ còn đợt "Ghi danh sớm" chứa hồ sơ gốc). Cần đảm bảo cơ sở dữ liệu được sao lưu trước khi thực hiện trên môi trường production.

> [!WARNING]
> Bản ghi điểm năng khiếu và chứng chỉ có ràng buộc duy nhất (`UNIQUE`) theo cặp `(so_cccd, ma_mon, dot_tuyen_sinh_id)`. Khi chuyển đợt hoặc gộp bản ghi từ đợt này sang đợt khác, nếu đã tồn tại bản ghi điểm cho môn đó ở đợt đích, hệ thống sẽ ưu tiên giữ lại điểm ở đợt đích (hoặc giữ điểm cao nhất) và xoá bản ghi điểm ở đợt nguồn để tránh lỗi trùng lặp dữ liệu.

## Open Questions

> [!NOTE]
> 1. Khi di chuyển nguyện vọng ở **Trường hợp 2**, chúng ta sẽ di chuyển **tất cả** nguyện vọng từ đợt "Bổ sung hồ sơ năng khiếu" sang đợt "Ghi danh sớm" (xếp xuống cuối), hay **chỉ di chuyển các nguyện vọng ngành năng khiếu**?
>    *Đề xuất*: Di chuyển toàn bộ nguyện vọng của thí sinh từ đợt nguồn để đảm bảo chuẩn hoá dữ liệu, sau đó loại bỏ bản ghi hồ sơ ở đợt nguồn.
> 2. Các mã ngành năng khiếu được xác định cứng gồm: `'7140201'`, `'7140206'`, `'7140221'`, `'7140222'` (Giáo dục mầm non, Giáo dục thể chất, Sư phạm âm nhạc, Sư phạm mỹ thuật). Bạn có cần bổ sung thêm mã ngành nào khác không?

## Proposed Changes

### Database Configuration

#### [KEEP] [.env](file:///d:/xampp/htdocs/TS/.env)
- Sử dụng cấu hình kết nối CSDL hiện tại trỏ tới host Supabase (`aws-1-ap-northeast-2.pooler.supabase.com`), đảm bảo máy chủ chạy script có kết nối internet và phân giải tên miền ổn định.

---

### Scripts & Utilities

#### [NEW] [standardize_talent_records.php](file:///d:/xampp/htdocs/TS/scripts/standardize_talent_records.php)
- Viết script CLI PHP để thực hiện đối chiếu và chuẩn hoá:
  - **Bước 1**: Kết nối DB bằng PDO, thiết lập role `admin` để bypass RLS (Row Level Security).
  - **Bước 2**: Truy vấn ID của đợt "Bổ sung hồ sơ năng khiếu" (Đợt A) và "Ghi danh sớm" (Đợt B).
  - **Bước 3**: Duyệt qua từng hồ sơ trong Đợt A:
    - **Trường hợp 1**: Thí sinh chưa có hồ sơ trong Đợt B.
      - Cập nhật `dot_tuyen_sinh_id = Đợt B` cho hồ sơ (`ho_so_xet_tuyen`).
      - Cập nhật `dot_tuyen_sinh_id = Đợt B` cho tất cả nguyện vọng (`nguyen_vong`) thuộc hồ sơ đó.
      - Chuyển toàn bộ điểm năng khiếu (`diem_nang_khieu`) và điểm chứng chỉ (`diem_chung_chi`) của thí sinh này từ Đợt A sang Đợt B.
    - **Trường hợp 2**: Thí sinh đã có hồ sơ trong Đợt B nhưng chưa đăng ký nguyện vọng ngành năng khiếu nào trong Đợt B.
      - Lấy thứ tự nguyện vọng lớn nhất hiện tại của thí sinh này trong Đợt B (giả sử là $N$).
      - Chuyển các nguyện vọng từ Đợt A sang Đợt B, gán `ho_so_id = Đợt B ID` và cập nhật `thu_tu_nguyen_vong` bắt đầu từ $N + 1$.
      - Di chuyển hoặc đồng bộ điểm năng khiếu và chứng chỉ từ Đợt A sang Đợt B (kiểm tra tránh trùng khóa).
      - Xoá hồ sơ của thí sinh trong Đợt A.
    - **Trường hợp 3**: Thí sinh đã có hồ sơ trong Đợt B và cũng đã đăng ký nguyện vọng ngành năng khiếu trong Đợt B.
      - Đồng bộ điểm năng khiếu và chứng chỉ từ Đợt A sang Đợt B (kiểm tra tránh trùng khóa).
      - Xoá hồ sơ của thí sinh trong Đợt A (các nguyện vọng của Đợt A tự động bị xoá theo cơ chế CASCADE).
  - **Bước 4**: Ghi nhật ký (Log) chi tiết số lượng hồ sơ được xử lý trong từng trường hợp.

## Verification Plan

### Automated Tests
- Chạy thử nghiệm script ở chế độ DRY-RUN (chỉ mô phỏng, không commit transaction) để kiểm tra tính đúng đắn trước khi thay đổi dữ liệu thật:
  ```bash
  d:\xampp\php\php.exe scripts/standardize_talent_records.php --dry-run
  ```
- Sau đó chạy thực tế:
  ```bash
  d:\xampp\php\php.exe scripts/standardize_talent_records.php
  ```

### Manual Verification
- Truy vấn trực tiếp database để kiểm tra sau khi chạy script:
  - Đảm bảo số lượng hồ sơ trong đợt "Bổ sung hồ sơ năng khiếu" bằng 0 (hoặc đã được chuyển/xoá hết).
  - Kiểm tra ngẫu nhiên các thí sinh ở cả 3 trường hợp để xác nhận hồ sơ và nguyện vọng của họ đã được cập nhật chính xác sang đợt "Ghi danh sớm".
