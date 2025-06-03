@echo off

   :: Kiểm tra xem Docker và Docker Compose có được cài đặt không
   where docker >nul 2>&1
   if %ERRORLEVEL% neq 0 (
       echo Docker không được cài đặt. Vui lòng cài đặt Docker trước khi chạy script này.
       exit /b 1
   )

   where docker-compose >nul 2>&1
   if %ERRORLEVEL% neq 0 (
       echo Docker Compose không được cài đặt. Vui lòng cài đặt Docker Compose trước khi chạy script này.
       exit /b 1
   )

   :: Tạo thư mục postgres-data nếu chưa tồn tại
   if not exist postgres-data mkdir postgres-data

   :: Khôi phục dữ liệu volume từ file nén
   echo Đang khôi phục dữ liệu volume...
   tar -zxvf postgres_data_backup.tar.gz

   :: Khởi động các container với Docker Compose
   echo Đang khởi động các container...
   docker-compose up -d

   echo Project đã được khởi động thành công! Truy cập Kong Gateway tại http://localhost:8000