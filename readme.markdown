# Hướng Dẫn Chạy Dịch Vụ IdentityServices và KongGateway

Hướng dẫn này sẽ giúp bạn chạy hai dịch vụ **IdentityServices** và **KongGateway** trên máy tính bằng Docker.

## Chuẩn Bị
- Đảm bảo đã cài đặt **Docker** và **Docker Compose** trên máy.
- Đảm bảo bạn có quyền truy cập vào các thư mục `IdentityServices` và `KongGateway` trong thư mục `Hospital-Microservices`.

---

## Bước 1: Chạy Dịch Vụ IdentityServices

1. **Di chuyển đến thư mục `IdentityServices`:**
   - Mở terminal (Command Prompt trên Windows hoặc Git Bash nếu đã cài).
   - Chuyển đến thư mục chứa dịch vụ IdentityServices:
     ```
     cd Hospital-Microservices/IdentityServices/IdentityServices
     ```

2. **Build Docker image cho IdentityServices:**
   - Chạy lệnh sau để build image Docker với tên `identityservices-api`:
     ```
     docker build -t identityservices-api .
     ```

3. **Chạy container IdentityServices:**
   - Chạy lệnh sau để khởi động container từ image vừa build:
     ```
     docker run -d -p 8080:8080 -e ASPNETCORE_ENVIRONMENT=Development --name identityservices-container identityservices-api
     ```
   - Giải thích:
     - `-d`: Chạy container ở chế độ nền (detached mode).
     - `-p 8080:8080`: Ánh xạ port 8080 của máy host với port 8080 của container.
     - `-e ASPNETCORE_ENVIRONMENT=Development`: Đặt biến môi trường để chạy ở chế độ Development.
     - `--name identityservices-container`: Đặt tên container là `identityservices-container`.
     - `identityservices-api`: Tên image đã build ở bước trước.

4. **Kiểm tra dịch vụ:**
   - Sau khi chạy lệnh trên, dịch vụ IdentityServices sẽ chạy trên `http://localhost:8080/swagger/index.html`. Bạn có thể mở trình duyệt và truy cập địa chỉ này để kiểm tra.

---

## Bước 2: Chạy Dịch Vụ KongGateway

1. **Di chuyển đến thư mục `KongGateway`:**
   - Trong terminal, chuyển đến thư mục chứa dịch vụ KongGateway:
     ```
     cd Hospital-Microservices/KongGateway
     ```

2. **Chạy file `setup.bat`:**
   - Chạy file `setup.bat` để tự động khôi phục volume và khởi động dịch vụ KongGateway:
     ```
     setup.bat
     ```
   - **Lưu ý:** Nếu bạn đang dùng Command Prompt trên Windows, lệnh trên sẽ hoạt động trực tiếp. Nếu gặp lỗi, hãy đảm bảo bạn đã cài đặt Docker và Docker Compose.

3. **Kiểm tra dịch vụ:**
   - File `setup.bat` sẽ tự động khôi phục volume và khởi động các container. Sau khi chạy xong, bạn có thể truy cập Kong Gateway tại `http://localhost:8000` hoặc vào xem trang của **Admin** ở `http://localhost:8002`.

---

## Giải Thích Các API

Dịch vụ IdentityServices cung cấp các endpoint API sau đây, có thể truy cập tại `http://localhost:8080`:

- **GET /Hello**
  - Đây là API test dùng để kiểm tra xem dịch vụ có hoạt động với identity không, vì chỉ khi người dùng đăng nhập và có accesstoken thì mới call đến api này đượcđược. Khi gọi endpoint này, nó sẽ trả về một phản hồi đơn giản, thường là "Hello" hoặc một thông điệp tương tự.

- **POST /api/{version}/account/register/user/doctor**
  - Endpoint này được sử dụng để đăng ký một người dùng với vai trò "Doctor". Yêu cầu gửi dữ liệu người dùng qua body (thường là JSON) để tạo tài khoản mới.

- **POST /api/{version}/account/register/user/staff**
  - Endpoint tương tự như trên, nhưng dành cho vai trò "SStaff". Dữ liệu người dùng cũng được gửi qua body để đăng ký.

- **POST /api/{version}/account/login**
  - Endpoint này cho phép người dùng đăng nhập bằng thông tin tài khoản (username/password). Yêu cầu gửi dữ liệu đăng nhập qua body và trả về access token và refreshtoken nếu thành công.

- **POST /api/{version}/account/refreshToken**
  - Endpoint dùng để làm mới (refresh) token xác thực khi access token cũ hết hạn. Yêu cầu gửi refresh token cũ qua body để nhận access-token và refresh-token mới.

- **{version}**: Là phiên bản của API (ví dụ: v1, v2), được thay thế trong URL tùy theo cấu hình. Hiện tại là v0

# Hướng Dẫn Chạy Dịch Vụ AppointmentService, DoctorService, MedicineService, PrescriptionService, PatientService, StaffService

Hướng dẫn này sẽ giúp bạn chạy các dịch vụ AppointmentService, DoctorService, MedicineService, PrescriptionService, PatientService, StaffService
1. **Di chuyển đến thư mục chứa file 'docker-compose.yml':**
     ```
     cd Hospital-Microservices
     ```

2. **Build Docker image cho toàn bộ Service:**
   - Chạy lệnh sau để build image Docker:
     ```
     docker-compose up --build
     ```
   - Nếu muốn build docker cho từng service, thì chạy câu lệnh:
     ```
     docker-compose up service_name
     ```
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
     docker-compose up -d
     
     ```
4. **Kiểm tra dịch vụ:**
   - Sau khi chạy lệnh trên, dịch vụ NotificatinService sẽ chạy trên `http://localhost:5088/swagger/index.html`. Bạn có thể mở trình duyệt và truy cập địa chỉ này để kiểm tra.

---
