
# EduManage — Laravel 13 + Vue 3

Hệ thống học tập trực tuyến, quản lý lớp học, điểm số và điểm danh bằng QR realtime.

## 1. Công nghệ và cấu trúc thư mục

| Thành phần | Công nghệ | Thư mục | Cổng mặc định |
|---|---|---|---:|
| Backend API | Laravel, Sanctum, Reverb | `BE/` | `8000` |
| Frontend SPA | Vue 3, Vite, Vue Router, Pinia | `FE/` | `5173` |
| Cơ sở dữ liệu | MySQL | — | `3306` |
| WebSocket realtime | Laravel Reverb | `BE/` | `8080` |

> Lưu ý: luôn ưu tiên phiên bản trong `BE/composer.json`. Nếu checkout đúng branch đang dùng tài liệu này, hãy chuẩn bị PHP theo yêu cầu của file đó. Repo hiện tại đang khai báo PHP `^8.3`; vì vậy PHP 8.3 trở lên là lựa chọn an toàn khi chạy `composer install`.

```text
.
├── BE/    # Laravel API
└── FE/    # Vue 3 frontend
```

## 2. Cài công cụ cần thiết

Người mới cần cài:

- Git
- PHP và Composer
- Node.js LTS, kèm npm
- MySQL hoặc XAMPP (bật dịch vụ MySQL)

Kiểm tra sau khi cài:

```bash
php -v
composer -V
node -v
npm -v
```

## 3. Tải mã nguồn

Thay URL bằng URL repository thực tế của nhóm:

```bash
git clone <URL_REPOSITORY>
cd <TEN_THU_MUC_DU_AN>
```

## 4. Cài và cấu hình Backend Laravel

### Bước 4.1 — Cài package PHP

```bash
cd BE
composer install
```

Nếu Composer báo thiếu PHP extension, bật các extension tương ứng trong `php.ini`, thường gồm `openssl`, `pdo_mysql`, `mbstring`, `fileinfo` và `zip`.

### Bước 4.2 — Tạo file môi trường

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

macOS/Linux:

```bash
cp .env.example .env
```

Mở `BE/.env` và cấu hình tối thiểu:

```dotenv
APP_NAME=EduPortal
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=be_chuyennganh
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=sync
```

Không commit file `.env` lên Git vì file này có thể chứa mật khẩu và khóa riêng.

### Bước 4.3 — Tạo khóa ứng dụng

```bash
php artisan key:generate
```

Sau lệnh này, `APP_KEY` trong `BE/.env` phải được điền tự động.

### Bước 4.4 — Tạo database MySQL

Tạo database `be_chuyennganh` bằng phpMyAdmin hoặc MySQL console:

```sql
CREATE DATABASE be_chuyennganh
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Đảm bảo tên database, tài khoản và mật khẩu trong `BE/.env` trùng với MySQL trên máy.

### Bước 4.5 — Chạy migration

Chạy migration lần đầu:

```bash
php artisan migrate
```

Nếu cần dữ liệu mẫu và tài khoản demo:

```bash
php artisan migrate --seed
```

Chỉ dùng `migrate:fresh --seed` khi được nhóm đồng ý, vì lệnh này xóa toàn bộ bảng và dữ liệu hiện có.

## 5. Cài và cấu hình Frontend Vue 3

Mở terminal mới, từ thư mục gốc dự án:

```bash
cd FE
npm install
```

Tạo `FE/.env` nếu chưa có:

```dotenv
VITE_API_URL=http://127.0.0.1:8000/api/
VITE_REVERB_APP_KEY=<trùng với REVERB_APP_KEY trong BE/.env>
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
```

Sau khi thay đổi `FE/.env`, phải khởi động lại Vite.

## 6. Chạy dự án

Cần mở 3 terminal và giữ cả 3 terminal đang chạy.

### Terminal 1 — Laravel API

```bash
cd BE
php artisan serve --port=8000
```

Backend chạy tại http://localhost:8000.

### Terminal 2 — Reverb WebSocket

```bash
cd BE
php artisan reverb:start --port=8080
```

Terminal này cần thiết cho thông báo realtime, chat và cập nhật điểm danh.

### Terminal 3 — Vue 3

```bash
cd FE
npm run dev
```

Mở frontend tại http://localhost:5173.

Có thể dùng `START.bat` ở thư mục gốc để mở nhanh các server trên Windows.

## 7. Một số lệnh thường dùng

```bash
# Xóa cache cấu hình Laravel
php artisan optimize:clear

# Xem trạng thái migration
php artisan migrate:status

# Chạy test backend
php artisan test

# Build frontend để kiểm tra lỗi biên dịch
cd FE
npm run build
```

## 8. Quy ước nhánh Git của nhóm

Mỗi thành viên chỉ được có **một nhánh cá nhân** và sử dụng nhánh đó từ đầu đến cuối dự án.

Quy định bắt buộc:

1. Không tạo thêm nhánh mới cho từng chức năng hoặc từng commit.
2. Không xóa nhánh cá nhân.
3. Không commit trực tiếp lên `main`.
4. Mọi thay đổi của thành viên phải thực hiện trên đúng nhánh cá nhân đã đăng ký.
5. Tên nhánh nên thống nhất theo mẫu `dev/<ten-thanh-vien>`, ví dụ `dev/nguyen-van-a`.

### Tạo nhánh cá nhân — chỉ thực hiện một lần

```bash
git switch main
git pull origin main
git switch -c dev/<ten-thanh-vien>
git push -u origin dev/<ten-thanh-vien>
```

Sau khi đã tạo nhánh, không chạy lại `git switch -c`. Những lần sau chỉ chuyển sang nhánh đã có:

```bash
git switch dev/<ten-thanh-vien>
```

### Cập nhật code mới từ `main`

Thực hiện trên nhánh cá nhân:

```bash
git switch dev/<ten-thanh-vien>
git fetch origin
git merge origin/main
```

Nếu có conflict, sửa các file được đánh dấu, sau đó:

```bash
git add .
git commit -m "resolve merge conflict"
```

## 9. Commit và mở Pull Request

### Bước 9.1 — Commit trên nhánh cá nhân

```bash
git status
git add .
git commit -m "feat: mo ta ngan gon thay doi"
git push origin dev/<ten-thanh-vien>
```

Nên dùng commit message ngắn, rõ mục đích, ví dụ:

- `feat: add attendance notification`
- `fix: validate rotating qr token`
- `docs: update setup guide`

### Bước 9.2 — Tạo Pull Request trên GitHub

1. Mở repository trên GitHub.
2. Chọn **Compare & pull request** hoặc vào **Pull requests → New pull request**.
3. Chọn:
   - **base:** `main`
   - **compare:** `dev/<ten-thanh-vien>`
4. Đặt tiêu đề mô tả đúng thay đổi.
5. Trong phần mô tả, ghi:
   - Đã thay đổi gì.
   - Vì sao cần thay đổi.
   - Đã kiểm tra bằng lệnh nào, ví dụ `php artisan test`, `npm run build`.
   - Ảnh màn hình nếu thay đổi giao diện.
6. Chọn reviewer và bấm **Create pull request**.

Ví dụ mô tả PR:

```text
## Thay đổi
- Thêm thông báo khi phiên điểm danh được mở.
- Cập nhật giao diện chuông thông báo trên Vue.

## Kiểm tra
- php artisan test
- npm run build

## Lưu ý
- Không thay đổi cấu trúc dữ liệu ngoài migration đã nêu.
```

### Bước 9.3 — Sau khi mở PR

- Tiếp tục commit trên **chính nhánh cá nhân**, PR sẽ tự cập nhật.
- Nếu có conflict, merge `origin/main` vào chính nhánh đó rồi push lại.
- Chỉ maintainer hoặc người được phân quyền merge PR vào `main` sau khi đã review.
- Không tạo nhánh thay thế và không xóa nhánh cá nhân sau khi merge.

## 10. Lỗi thường gặp

| Lỗi | Cách xử lý |
|---|---|
| `APP_KEY is missing` | Chạy `php artisan key:generate` trong `BE/`. |
| `Class ... not found` | Chạy `composer install` trong `BE/`. |
| `Cannot connect to MySQL` | Bật MySQL và kiểm tra `DB_*` trong `BE/.env`. |
| Frontend gọi sai API | Kiểm tra `VITE_API_URL` trong `FE/.env`, rồi restart `npm run dev`. |
| Không có realtime | Chạy thêm `php artisan reverb:start --port=8080` và kiểm tra `VITE_REVERB_*`. |
| Port đã được sử dụng | Đổi port server hoặc tắt tiến trình đang chiếm port. |
=======
# 

