# SocialX

SocialX là ứng dụng mạng xã hội gồm:

- **Backend:** Laravel 12, PHP 8.4, Sanctum
- **Frontend:** Vue 3, TypeScript, Vite
- **Database:** MySQL 8.4
- **Cache/queue:** Redis 7
- **Môi trường chạy:** Docker Compose

## Yêu cầu môi trường

Cài đặt các công cụ sau trên máy:

- Git
- Docker Desktop đang chạy
- Docker Compose v2 (được tích hợp trong Docker Desktop)

Không cần cài PHP, Composer, Node.js hoặc MySQL trực tiếp trên máy để chạy project bằng Docker.

## Cài đặt lần đầu

### 1. Clone source code

```bash
git clone <repository-url> socialx
cd socialx
```

### 2. Tạo file môi trường

Nếu file `.env` chưa tồn tại, chạy:

```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

Các file `.env` chứa cấu hình riêng của máy và không được commit lên Git.

Cấu hình Docker mặc định:

```text
Backend API: http://localhost:18000
Frontend:    http://localhost:15173
MySQL:       localhost:13306
Redis:       localhost:16379
```

### 3. Build và khởi động container

```bash
docker compose up -d --build
```

Kiểm tra trạng thái các service:

```bash
docker compose ps
```

Backend chỉ được khởi động sau khi MySQL và Redis healthy.

### 4. Khởi tạo Laravel

Chạy các lệnh sau lần đầu tiên:

```bash
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate --seed --force
docker compose exec backend php artisan storage:link
docker compose exec backend php artisan optimize:clear
```

`migrate --seed` tạo các bảng database và dữ liệu role/permission mặc định.

## Truy cập ứng dụng

- Frontend: [http://localhost:15173](http://localhost:15173)
- API health check: [http://localhost:18000/api/health](http://localhost:18000/api/health)

Kết quả health check mong đợi:

```json
{
  "status": "ok",
  "application": "SocialX API"
}
```

## API cơ bản

Base URL:

```text
http://localhost:18000/api
```

Với request API, nên gửi các header:

```http
Accept: application/json
Content-Type: application/json
```

Các endpoint yêu cầu đăng nhập cần thêm:

```http
Authorization: Bearer <sanctum-token>
```

## Kết nối database bằng DBeaver

Tạo kết nối **MySQL** với thông tin:

```text
Host:     localhost
Port:     13306
Database: socialx
Username: socialx
Password: socialx_password
```

Hoặc dùng tài khoản root:

```text
Username: root
Password: root_password
```

Trong DBeaver dùng `localhost`, không dùng hostname `mysql`. Hostname `mysql` chỉ dùng giữa các container trong Docker network.

## Các lệnh thường dùng

```bash
# Khởi động project
docker compose up -d

# Dừng container nhưng giữ lại database và Redis volume
docker compose down

# Xem trạng thái service
docker compose ps

# Xem log
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f mysql
docker compose logs -f redis

# Xóa cache Laravel
docker compose exec backend php artisan optimize:clear

# Chạy migration mới
docker compose exec backend php artisan migrate

# Chạy seeder
docker compose exec backend php artisan db:seed

# Chạy test backend
docker compose exec backend php artisan test

# Kiểm tra và build frontend
docker compose exec frontend npm run type-check
docker compose exec frontend npm run build
```

## Reset database khi phát triển

Lệnh sau sẽ xóa toàn bộ dữ liệu trong database rồi tạo lại từ đầu:

```bash
docker compose exec backend php artisan migrate:fresh --seed
```

Chỉ dùng khi muốn reset dữ liệu local. Không chạy trên database có dữ liệu cần giữ.

`docker compose down -v` cũng sẽ xóa các Docker volume, bao gồm dữ liệu MySQL và Redis. Chỉ sử dụng khi thực sự muốn xóa toàn bộ dữ liệu local.

## Xử lý lỗi thường gặp

### Redis `Connection refused`

Trong `backend/.env`, khi chạy bằng Docker phải dùng:

```env
REDIS_HOST=redis
REDIS_PORT=6379
```

Sau khi sửa `.env`:

```bash
docker compose exec backend php artisan optimize:clear
docker compose restart backend
```

### API validation trả HTML hoặc `200 OK`

Kiểm tra request có header sau:

```http
Accept: application/json
```

Nếu thiếu header này, Laravel có thể redirect về trang web và Postman hiển thị kết quả cuối là `200 OK`.

### Frontend báo không tìm thấy package

Docker tự cài package khi build image. Nếu IDE chạy TypeScript trên máy host, cài thêm dependency tại thư mục frontend:

```bash
cd frontend
npm install
```

### Container chưa chạy

```bash
docker compose up -d --build
docker compose ps
```

## Cấu trúc thư mục

```text
socialx/
├── backend/              # Laravel API
├── frontend/             # Vue frontend
├── docker/
│   └── php/              # PHP configuration
├── docs/                 # Tài liệu project
└── docker-compose.yml    # Cấu hình các service
```
