-- Tài khoản quản trị DÙNG THỬ (xem mật khẩu trong README.md, mục 3).
-- Import sau tiepanh.sql. Chạy lại nhiều lần an toàn: email đã có thì đặt lại mật khẩu/quyền.
-- Trước khi đưa lên mạng: bỏ file này và tạo tài khoản thật bằng database/create_admin.php.

SET NAMES utf8mb4;

INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `status`) VALUES
  ('Quản trị viên', 'admin@tiepanh.local', '$2y$10$MT1cvuMhA3iaLKwmv7lDseO/mTCk/svkvCqlH.IF7z64NHsmXrRnm', 'admin', 'active'),
  ('Nhân viên Tiệp Anh', 'staff@tiepanh.local', '$2y$10$rmKnih6bJUr4noh3FK/OIOHKPWrcSQeBdzFJNRylf6gYst24zLKiy', 'staff', 'active')
ON DUPLICATE KEY UPDATE
  `full_name` = VALUES(`full_name`),
  `password_hash` = VALUES(`password_hash`),
  `role` = VALUES(`role`),
  `status` = 'active';
