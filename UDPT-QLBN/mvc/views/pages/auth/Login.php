<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản lý Bệnh viện ABC</title>
    <link rel="stylesheet" href="/UDPT-QLBN/public/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/UDPT-QLBN/public/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-form">
            <div class="login-header">
                <h3>Đăng nhập</h3>
            </div>
            <div class="login-body">
                <?php if (isset($data['error'])): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($data['error']) ?>
                    </div>
                <?php endif; ?>
                
                <form action="/UDPT-QLBN/Auth/authenticate" method="POST">
                    <?php if (isset($data['redirect'])): ?>
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($data['redirect']) ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" id="username" name="username" required 
                            placeholder="Nhập tên tài khoản"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu">
                            <span class="toggle-password" onclick="togglePasswordVisibility()">👁️</span>
                        </div>
                        <div class="forgot-password">
                            <a href="/Auth/forgotPassword">Quên mật khẩu?</a>
                        </div>
                    </div>
                    
                    <button type="submit" class="login-button">Đăng nhập</button>
                </form>
            </div>
            <div class="login-footer">
                <p>Đăng ký tài khoản bác sĩ ? <a href="/UDPT-QLBN/Auth/register">Đăng ký</a></p>
            </div>
        </div>
    </div>

    <script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
        } else {
            passwordInput.type = 'password';
        }
    }
    </script>
</body>
</html>