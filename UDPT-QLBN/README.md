# UDPT-QLBN - PHP Frontend Microservices

Ứng dụng PHP này là frontend quản lý bệnh viện, chỉ gọi API các microservices backend (không cần database nội bộ). Bạn có thể chạy bằng XAMPP hoặc Docker.

## 1. Chạy bằng XAMPP (local)

- Copy toàn bộ thư mục `UDPT-QLBN` vào `htdocs` của XAMPP.
- Khởi động Apache từ XAMPP Control Panel.
- Truy cập: [http://localhost/UDPT-QLBN](http://localhost/UDPT-QLBN)

## 2. Chạy bằng Docker

### Yêu cầu

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) đã cài trên máy.

### Các bước

1. **Build image:**

   ```sh
   docker build -t udpt-qlbn-fe .
   ```

2. **Chạy container trên cổng 8081:**

   ```sh
   docker run -d -p 8081:80 --name udpt-qlbn-fe udpt-qlbn-fe
   ```

3. **Truy cập ứng dụng:**

   - Mở trình duyệt: [http://localhost:8081/UDPT-QLBN](http://localhost:8081/UDPT-QLBN)

---
