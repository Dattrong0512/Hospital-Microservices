# Sử dụng hình ảnh PHP-FPM
FROM php:8.1-fpm

# Cài đặt các tiện ích mở rộng
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Cài đặt Nginx và supervisord
RUN apt-get update && apt-get install -y nginx supervisor

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Sao chép toàn bộ ứng dụng (bao gồm UDPT-QLBN)
COPY . /var/www/html/

# Sao chép cấu hình Nginx
COPY nginx.conf /etc/nginx/sites-available/default

# Cấu hình supervisord để quản lý PHP-FPM và Nginx
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Đặt quyền
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose cổng
EXPOSE 8081

# Khởi động supervisord
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]