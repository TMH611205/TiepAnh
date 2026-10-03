# Xe điện Tiệp Anh

Website bán hàng của **Hộ kinh doanh Tiệp Anh**: giới thiệu xe máy điện, xe đạp điện, xe điện học sinh, pin và phụ kiện; khách đặt hàng trực tuyến; chủ cửa hàng quản lý đơn, kho và sản phẩm trong trang quản trị.

- Địa chỉ: Số nhà 08, đường Thượng Trụ, xã Can Lộc, tỉnh Hà Tĩnh
- Điện thoại: 0975303993
- Facebook: https://www.facebook.com/minhhoang.tran.5076

Công nghệ: PHP thuần + MySQL/MariaDB (chạy trên XAMPP), Bootstrap 5, JavaScript thuần, phông chữ Be Vietnam Pro (tự lưu trong `assets/fonts/`, chạy được khi không có mạng). Composer chỉ dùng cho tính năng **Báo cáo AI** (tùy chọn); không cần Node.

---

## 1. Cài đặt và chạy

1. Chép thư mục dự án vào `C:\xampp\htdocs\TiepAnh`.
2. Mở XAMPP, bật **Apache** và **MySQL**.
3. Trong phpMyAdmin, tạo CSDL `tiepanh` (bảng mã `utf8mb4_unicode_ci`), rồi import lần lượt:
   - `database/tiepanh.sql` (cấu trúc bảng)
   - `database/catalog_seed.sql` (danh mục và sản phẩm mẫu)
   - `database/migrations/001_purchases.sql` (nhập hàng, chứng từ mua hàng, giá vốn)
   - `database/migrations/002_product_details.sql` (cột nội dung chi tiết sản phẩm)
   - `database/migrations/003_brand_products.sql` (5 mẫu xe thật của KUMATSU và SONCO kèm nội dung chi tiết)
   - `database/demo_users.sql` (tài khoản đăng nhập dùng thử, xem mục 3)

   > Import bằng phpMyAdmin (tab Import, bảng mã utf-8). Nếu import bằng dòng lệnh, thêm `--default-character-set=utf8mb4` để tiếng Việt không bị lỗi font.

4. Sao chép `.env.example` thành `.env` và chỉnh nếu cần (thông tin DB, `APP_ENV`).
5. (Tùy chọn, để bật Báo cáo AI) cài Composer rồi chạy `composer install` trong thư mục dự án và điền `ANTHROPIC_API_KEY` vào `.env` (xem mục 5). Bỏ qua bước này thì Báo cáo AI vẫn chạy ở chế độ nhận diện từ khóa.
6. Đăng nhập trang quản trị bằng tài khoản ở mục 3 (hoặc tự tạo tài khoản riêng bằng lệnh ở mục đó).
7. Truy cập:
   - Website: <http://localhost/TiepAnh/public/index.php>
   - Quản trị: <http://localhost/TiepAnh/admin/login.php>

Địa chỉ website tự nhận theo cách bạn truy cập, đổi cổng hay tên miền không cần sửa mã. Chỉ đặt `APP_URL` trong `.env` khi chạy sau proxy.

---

## 2. Tính năng cho khách hàng

| Tính năng                            | Mô tả                                                                                                                                                                                                                                                                          |
| ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Trang chủ                            | Ảnh xe nổi bật (bấm vào để xem chi tiết), giới thiệu thương hiệu Tiệp Anh, danh mục, sản phẩm nổi bật                                                                                                                                                                          |
| Sản phẩm                             | Lọc theo danh mục, khoảng giá, quãng đường, ưu đãi; tìm kiếm theo tên                                                                                                                                                                                                          |
| Chi tiết sản phẩm                    | Ảnh, giá (giá khuyến mãi hiển thị cùng giá gốc gạch ngang), thông số nổi bật. Với xe có dữ liệu chi tiết: bảng phiên bản/giá/màu, pin và sạc, thông số đầy đủ (accordion), tính năng nổi bật, bảo hành và hậu mãi, 5 câu hỏi thường gặp. Mục thiếu dữ liệu ghi `[CẦN BỔ SUNG]` |
| Giỏ hàng                             | Thêm nhanh từ danh sách, đổi số lượng, xóa; lưu trong phiên truy cập                                                                                                                                                                                                           |
| Đặt hàng                             | Giỏ hàng → thông tin giao hàng → chọn thanh toán (**COD** hoặc **chuyển khoản**) → xác nhận; tồn kho được kiểm tra và trừ an toàn khi đặt                                                                                                                                      |
| Trợ lý tư vấn                        | Nút chat ở góc màn hình: gợi ý xe theo nhu cầu (đi học, đường dài, tốc độ, quãng đường, ngân sách), so sánh 2-3 xe. Trả lời dựa trên dữ liệu sản phẩm trong CSDL, không gọi dịch vụ AI bên ngoài                                                                               |
| Ưu đãi, Tin tức, Giới thiệu, Liên hệ | Có bản đồ và nút gọi nhanh, nút Zalo/Facebook                                                                                                                                                                                                                                  |
| Trải nghiệm                          | Ưu tiên điện thoại, animation khi cuộn (tắt tự động khi máy bật giảm chuyển động)                                                                                                                                                                                              |

Khách hàng **không cần đăng ký tài khoản** để mua hàng.

---

## 3. Tài khoản và vai trò

| Vai trò                     | Đăng nhập                                     | Quyền                                                                                 |
| --------------------------- | --------------------------------------------- | ------------------------------------------------------------------------------------- |
| **Khách hàng**              | Không có tài khoản                            | Xem sản phẩm, dùng giỏ hàng, đặt hàng, chat tư vấn                                    |
| **Nhân viên** (`staff`)     | `staff@tiepanh.local` / `TA-0936c21bb4-stf`   | Vào trang quản trị: xem tổng quan, xử lý đơn hàng, sản phẩm, kho, khách hàng, hóa đơn |
| **Quản trị viên** (`admin`) | `admin@tiepanh.local` / `TA-d03bd16e39c9-adm` | Như nhân viên; dành cho chủ cửa hàng                                                  |

> **Hiện nhân viên và quản trị viên có quyền như nhau.** Hai vai trò được tách sẵn trong CSDL để sau này giới hạn quyền.
>
> **Đây là tài khoản dùng thử.** Ai import `database/demo_users.sql` đều có hai tài khoản này, nên **không dùng cho website chạy thật**. Trước khi đưa lên mạng: đặt mật khẩu mới bằng lệnh dưới đây, rồi xóa `database/demo_users.sql` và các dòng tài khoản trong README.

Tạo hoặc đặt lại tài khoản (chỉ chạy được bằng dòng lệnh; mật khẩu tối thiểu 10 ký tự; email đã có thì cập nhật mật khẩu):

```bash
C:\xampp\php\php.exe database/create_admin.php email@vidu.com "Họ tên" "MậtKhẩuMới" admin
C:\xampp\php\php.exe database/create_admin.php nv@vidu.com "Họ tên" "MậtKhẩuMới" staff
```

Bảo vệ đăng nhập: khóa 10 phút sau 5 lần nhập sai, tự đăng xuất sau 2 giờ không thao tác, tài khoản bị khóa (`status = blocked`) mất quyền ngay.

---

## 4. Hướng dẫn trang quản trị

Vào <http://localhost/TiepAnh/admin/login.php>, đăng nhập bằng một tài khoản ở mục 3. Trên điện thoại, mở menu bằng nút ☰ góc trên bên trái.

Mọi bảng danh sách và báo cáo đều có nút **Xuất Excel** (file `.xlsx`, giữ nguyên bộ lọc đang chọn).

**Dashboard thống kê** (trang chủ admin): chọn nhanh 7 ngày / 30 ngày / 90 ngày / 12 tháng hoặc chọn từ ngày – đến ngày. Hiển thị doanh thu, số đơn và đơn hủy, lãi gộp ước tính, chi nhập hàng, đơn chờ xác nhận, sắp hết hàng, tồn kho và giá trị tồn; biểu đồ doanh thu theo ngày (hoặc tháng khi chọn khoảng dài), top 5 sản phẩm bán chạy, đơn theo trạng thái. Nút Xuất Excel tạo file nhiều sheet.

**Đơn hàng**

- Tìm theo mã đơn, tên hoặc số điện thoại; lọc theo trạng thái.
- Bấm mã đơn để xem chi tiết và đổi **trạng thái đơn** (chờ xác nhận → đã xác nhận → đang chuẩn bị → đang giao → hoàn tất) và **trạng thái thanh toán**.
- **Hủy đơn sẽ hoàn lại tồn kho và không thể mở lại.** Nếu khách đổi ý, hãy tạo đơn mới.

**Chứng từ bán hàng**: mỗi đơn là một _phiếu bán hàng_; lọc theo ngày, trạng thái, tìm theo mã/khách. Mở phiếu để **in** (có chỗ ký tên) hoặc xuất Excel. Doanh thu không tính đơn đã hủy.

**Chứng từ mua hàng** (phiếu nhập kho)

- **Lập phiếu nhập**: nhập nhà cung cấp, thêm nhiều dòng (sản phẩm, số lượng, đơn giá nhập). Lưu phiếu sẽ **cộng tồn kho** và cập nhật **giá vốn bình quân gia quyền** của sản phẩm.
- Mở phiếu để in hoặc xuất Excel. **Hủy phiếu** trừ lại tồn kho (chỉ hủy được khi tồn hiện tại còn đủ số đã nhập). Giá vốn bình quân không được tính lại khi hủy.

**Sản phẩm**

- Thêm, sửa sản phẩm: tên, danh mục, giá, giá khuyến mãi, tồn kho, thông số, ảnh (JPG/PNG/WebP, tối đa 3MB).
- Giá khuyến mãi phải **nhỏ hơn giá gốc** mới được áp dụng; để trống nếu không giảm giá. Giá gốc bằng 0 sẽ hiển thị "Liên hệ".
- Nút **Ẩn** gỡ sản phẩm khỏi website nhưng giữ lại dữ liệu để đơn cũ không bị ảnh hưởng. Muốn bán lại, sửa trạng thái về "Đang bán".

**Tồn kho**: tổng số lượng, giá trị tồn theo giá vốn và giá bán, lãi tiềm năng; lọc "sắp hết" / "đã hết"; đặt lại số lượng ngay trong bảng (trạng thái "Đang bán / Hết hàng" tự đồng bộ). Bấm tên sản phẩm để xem **thẻ kho** (các lần nhập và xuất). Sản phẩm chưa từng nhập hàng có giá vốn 0.

**Khách hàng**: danh sách khách đã mua, số đơn và tổng tiền (không tính đơn hủy).

**Hóa đơn**: xem các hóa đơn nháp được tạo cùng đơn hàng. Chưa phát hành hóa đơn điện tử.

**Báo cáo AI**: gõ câu hỏi tiếng Việt như _"Top 5 xe bán chạy tháng này"_, _"Doanh thu 7 ngày qua theo từng ngày"_, _"Xe nào sắp hết hàng?"_, _"Ước tính lợi nhuận tháng trước"_ (hoặc bấm câu gợi ý). Hệ thống chọn đúng báo cáo, tự hiểu khoảng thời gian, lập bảng và cho xuất Excel.

- Có `ANTHROPIC_API_KEY`: dùng AI Claude để hiểu câu hỏi. **Chỉ câu hỏi của bạn được gửi đi**, không gửi dữ liệu khách hàng, đơn hàng hay tồn kho. AI chỉ _chọn_ một báo cáo trong danh mục có sẵn (`models/Report.php`), không tự viết câu truy vấn nên không đọc được dữ liệu ngoài danh mục. Giới hạn 20 lượt mỗi giờ mỗi phiên.
- Không có khóa, hết lượt hoặc AI lỗi: tự chuyển sang nhận diện từ khóa tiếng Việt chạy trên máy chủ, vẫn lập được báo cáo.
- Danh mục báo cáo: doanh thu theo ngày/tháng, sản phẩm bán chạy, doanh thu theo danh mục, đơn theo trạng thái, khách mua nhiều, chi nhập hàng theo nhà cung cấp, ước tính lợi nhuận gộp, sản phẩm sắp hết, giá trị tồn kho.
- Lãi gộp là **ước tính**: số lượng bán × giá vốn _hiện tại_ của sản phẩm.

---

## 5. Cấu hình `.env`

| Biến                                                  | Ý nghĩa                                                                                                |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `APP_ENV`                                             | `development`: hiện lỗi chi tiết. `production`: ẩn lỗi, ghi vào `storage/logs/php-error.log`           |
| `APP_URL`                                             | Để trống để tự nhận. Chỉ điền khi chạy sau proxy (ví dụ `https://ten-mien.vn`)                         |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Kết nối CSDL                                                                                           |
| `ANTHROPIC_API_KEY`                                   | (Tùy chọn) khóa API để bật AI cho Báo cáo AI, lấy tại console.anthropic.com. Để trống = chế độ từ khóa |
| `AI_MODEL`                                            | (Tùy chọn) mô hình Claude dùng cho Báo cáo AI, mặc định `claude-opus-5-5`                              |

Không commit file `.env`.

---

## 6. Đưa lên máy chủ thật (checklist)

- [ ] Đặt `APP_ENV=production`.
- [ ] Chạy `composer install --no-dev` nếu dùng Báo cáo AI (thư mục `vendor/` không nằm trong git).
- [ ] Tạo người dùng MySQL riêng với mật khẩu mạnh (không dùng `root` không mật khẩu), điền vào `.env`.
- [ ] Đặt mật khẩu mới cho admin và nhân viên (mục 3), xóa tài khoản dùng thử khỏi README.
- [ ] Bật HTTPS (cookie đăng nhập tự thêm cờ `secure`).
- [ ] Bảo đảm Apache bật `mod_rewrite` và cho phép `.htaccess` (`AllowOverride All`): file này chặn truy cập trực tiếp vào `config/`, `models/`, `database/`, `storage/`, `vendor/`, `.env`; `uploads/.htaccess` cấm chạy PHP trong thư mục ảnh.
- [ ] Điền thông tin còn thiếu trong `config/app.php`: Zalo, giờ mở cửa, link Google Maps, logo, slogan (hiện đang là `TODO`).
- [ ] Thay dữ liệu xe mẫu bằng giá, thông số và ảnh thật qua trang quản trị.

---

## 7. Cấu trúc thư mục

```
public/        Các trang cho khách (index, products, cart, checkout...)
admin/         Trang quản trị (login, đơn hàng, sản phẩm, kho...)
api/           assistant.php: API trợ lý tư vấn
models/        Product, Order, User, Purchase, Pricing (quy tắc giá), Report (danh mục thống kê), AiReport, Text
middleware/    Auth, Xlsx (xuất Excel), hàm dùng chung cho trang admin
config/        app.php, database.php, env.php
views/         Header/footer của website và admin
assets/        css, js (main, reveal), ảnh giao diện
uploads/       Ảnh sản phẩm
database/      Schema, dữ liệu mẫu, migrations/, create_admin.php
storage/       Log và hóa đơn sinh ra khi chạy
docs/          Screenshot đối chiếu giao diện
.claude/rules/ Quy tắc làm việc cho Claude Code
```

---

## 8. Chưa có / giới hạn hiện tại

- Chưa có tài khoản đăng nhập cho khách hàng.
- Chưa thanh toán online (chỉ COD và chuyển khoản); chưa phát hành hóa đơn điện tử.
- Chưa có trang tự đổi mật khẩu; dùng lệnh ở mục 3.
- Không có trang "Ưu đãi" riêng: `promotions.php` chuyển hướng sang `products.php?promotion=1`.
- Chưa có bộ kiểm thử tự động.
- Báo cáo AI: đã kiểm tra luồng gọi API tới mức xác thực (khóa sai bị từ chối và hệ thống tự chuyển sang chế độ từ khóa); chưa thử với khóa thật nên chưa xác nhận được câu trả lời thật của mô hình.
- Chưa có công nợ nhà cung cấp, trả hàng nhập, hay phát hành hóa đơn điện tử.
