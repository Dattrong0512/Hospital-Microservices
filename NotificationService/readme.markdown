# Hướng Dẫn Chạy Dịch Vụ NotificationService

1. **Di chuyển đến thư mục `NotificatinService`:**
   - Mở terminal (Command Prompt trên Windows).

2. **Build Docker image cho NotificatinService:**
   - Chạy lệnh sau để build image Docker với tên `notification-service`:
     ```
     docker build -t notification-service:latest .  
     ```

3. **Chạy container NotificatinService:**
   - Chạy lệnh sau để khởi động container từ image vừa build:
     ```
     docker run -d -p 5088:80 -e ASPNETCORE_ENVIRONMENT=Development --name my-notification-service otification-service:latest
     ```
   - Giải thích:
     - `-d`: Chạy container ở chế độ nền (detached mode).
     - `-p 5088:80`: cổng 5088 sẽ được chuyển hướng tới cổng 80 bên trong container.  truy cập Swagger UI qua http://localhost:5088
     - `-e ASPNETCORE_ENVIRONMENT=Development`: Đặt biến môi trường để chạy ở chế độ Development.
     - `--name my-notification-service`: Đặt tên container là `my-notification-service`.
     - `notification-service:latest`: Docker sẽ tìm và sử dụng image notification-service có tag latest trong kho lưu trữ

4. **Kiểm tra dịch vụ:**
   - Sau khi chạy lệnh trên, dịch vụ NotificatinService sẽ chạy trên `http://localhost:5088/swagger/index.html`. Bạn có thể mở trình duyệt và truy cập địa chỉ này để kiểm tra.

---
