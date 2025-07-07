<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký bác sĩ - Hệ thống Quản lý Bệnh viện</title>
    <link rel="stylesheet" href="/UDPT-QLBN/public/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px 0;
       }

        .register-form {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
        }

        .register-header {
            background: linear-gradient(135deg, #4a5568, #2d3748);
            color: white;
            padding: 24px;
            text-align: center;
        }

        .register-header h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .register-body {
            padding: 32px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            background-color: #f8fafc;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .text-danger {
            color: #ef4444;
        }

        .text-muted {
            color: #6b7280;
            font-size: 12px;
            margin-top: 4px;
        }

        .password-input {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #6b7280;
            user-select: none;
        }

        .toggle-password:hover {
            color: #374151;
        }

        .register-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 20px;
        }

        .register-button:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .register-button:active {
            transform: translateY(0);
        }

        .register-footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .register-footer p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .register-footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }

        .alert-danger {
            background-color: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
            border: 1px solid #3b82f6;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .register-container {
                padding: 10px;
            }
            
            .register-form {
                margin: 10px;
            }
            
            .register-body {
                padding: 20px;
            }
            
            .col-md-4, .col-md-6 {
                margin-bottom: 0;
            }
        }

        /* Grid System for Form Layout */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .col-md-4, .col-md-6 {
            padding: 0 10px;
            flex: 1;
            min-width: 0;
        }

        .col-md-4 {
            flex: 0 0 33.333333%;
        }

        .col-md-6 {
            flex: 0 0 50%;
        }

        @media (max-width: 768px) {
            .col-md-4, .col-md-6 {
                flex: 0 0 100%;
                margin-bottom: 0;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-form">
            <!-- Header -->
            <div class="register-header">
                <h3>Đăng ký bác sĩ</h3>
            </div>

            <!-- Body -->
            <div class="register-body">
                <?php if (isset($data['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($data['error']) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($data['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($data['success']) ?>
                        <?php if (isset($data['show_login_link'])): ?>
                            <div class="mt-3 text-center">
                                <a href="/UDPT-QLBN/Auth/login" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-1"></i>Đăng nhập ngay
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <form action="/UDPT-QLBN/Auth/registerDoctor" method="POST" id="registerForm">
                        <!-- Row 1: Họ tên, Giới tính, Ngày sinh -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fullname">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" id="fullname" name="fullname" class="form-control" required
                                           placeholder="Nguyễn Văn A"
                                           value="<?= isset($data['form_data']['fullname']) ? htmlspecialchars($data['form_data']['fullname']) : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="gender">Giới tính <span class="text-danger">*</span></label>
                                    <select id="gender" name="gender" class="form-control" required>
                                        <option value="">Chọn giới tính</option>
                                        <option value="Nam" <?= (isset($data['form_data']['gender']) && $data['form_data']['gender'] === 'Nam') ? 'selected' : '' ?>>Nam</option>
                                        <option value="Nữ" <?= (isset($data['form_data']['gender']) && $data['form_data']['gender'] === 'Nữ') ? 'selected' : '' ?>>Nữ</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="birth_date">Ngày sinh <span class="text-danger">*</span></label>
                                    <input type="date" id="birth_date" name="birth_date" class="form-control" required
                                           value="<?= isset($data['form_data']['birth_date']) ? htmlspecialchars($data['form_data']['birth_date']) : '' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: CCCD, Số điện thoại -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="identity_card">CCCD/CMND <span class="text-danger">*</span></label>
                                    <input type="text" id="identity_card" name="identity_card" class="form-control" required
                                           placeholder="Nhập số CCCD/CMND"
                                           pattern="[0-9]{9,12}"
                                           value="<?= isset($data['form_data']['identity_card']) ? htmlspecialchars($data['form_data']['identity_card']) : '' ?>">
                                    <small class="text-muted">9-12 chữ số</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone_number">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="tel" id="phone_number" name="phone_number" class="form-control" required
                                           placeholder="Nhập số điện thoại"
                                           pattern="[0-9]{10,11}"
                                           value="<?= isset($data['form_data']['phone_number']) ? htmlspecialchars($data['form_data']['phone_number']) : '' ?>">
                                    <small class="text-muted">10-11 chữ số</small>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Email, Khoa -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email <span class="text-danger">*</span></label>
                                    <input type="email" id="email" name="email" class="form-control" required
                                           placeholder="Nhập email"
                                           value="<?= isset($data['form_data']['email']) ? htmlspecialchars($data['form_data']['email']) : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="department">Khoa <span class="text-danger">*</span></label>
                                    <select id="department" name="department" class="form-control" required>
                                        <option value="">Chọn khoa</option>
                                        <?php
                                        $departments = ['Khoa Nội', 'Khoa Ngoại', 'Khoa Nhi', 'Khoa Hồi sức cấp cứu', 'Chấn thương chỉnh hình'];
                                        foreach ($departments as $dept):
                                        ?>
                                            <option value="<?= $dept ?>" <?= (isset($data['form_data']['department']) && $data['form_data']['department'] === $dept) ? 'selected' : '' ?>>
                                                <?= $dept ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Row 4: Tên đăng nhập -->
                        <div class="form-group">
                            <label for="username">Tên đăng nhập <span class="text-danger">*</span></label>
                            <input type="text" id="username" name="username" class="form-control" required
                                   placeholder="Nhập tên đăng nhập"
                                   pattern="[a-zA-Z0-9_]{3,20}"
                                   value="<?= isset($data['form_data']['username']) ? htmlspecialchars($data['form_data']['username']) : '' ?>">
                            <small class="text-muted">3-20 ký tự, chỉ chữ cái, số và dấu gạch dưới</small>
                        </div>

                        <!-- Row 5: Mật khẩu, Xác nhận mật khẩu -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Mật khẩu <span class="text-danger">*</span></label>
                                    <div class="password-input">
                                        <input type="password" id="password" name="password" class="form-control" required
                                               placeholder="Nhập mật khẩu"
                                               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}">
                                        <span class="toggle-password" onclick="togglePasswordVisibility('password')">👁️</span>
                                    </div>
                                    <small class="text-muted">Tối thiểu 8 ký tự, có chữ hoa, chữ thường và số</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="confirm_password">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                    <div class="password-input">
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                                               placeholder="Nhập lại mật khẩu">
                                        <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">👁️</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="register-button">Đăng ký</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="register-footer">
                <p>Đã có tài khoản? <a href="/UDPT-QLBN/Auth/login">Đăng nhập</a></p>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = passwordInput.nextElementSibling;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }

        // Form validation
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Mật khẩu xác nhận không khớp!');
                return false;
            }

            // Validate password strength
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/;
            if (!passwordRegex.test(password)) {
                e.preventDefault();
                alert('Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số!');
                return false;
            }
        });

        // Add smooth focus transitions
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>
?>