<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($data['pageTitle']) ? $data['pageTitle'] : 'Phân hệ bác sĩ'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/UDPT-QLBN/public/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/UDPT-QLBN/public/css/style.css">
    <link rel="stylesheet" href="/UDPT-QLBN/public/css/doctor.css">
    
    <style>
        /* Thêm CSS để đảm bảo fullscreen với lề hai bên */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            background-color: #f5f5f5;
        }
        .wrapper {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        .main-container {
            flex: 1;
            width: 100%;
            max-width: 1800px;
            margin: 0 auto;
            padding: 0 30px;
            background-color: white;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        .content {
            padding: 0;
            width: 100%;
        }
        .hospital-header {
            width: 100%;
            max-width: 100%;
            padding: 0 20px;
            margin-bottom: 20px; /* Thêm margin dưới header */
        }
        .hospital-footer {
            margin-top: auto;
            width: 100%;
            max-width: 1800px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 30px;
        }
        
        /* Thêm padding-top cho phần nội dung chính để tránh bị header che */
        .main-container {
            padding-top: 30px;
        }
        
        /* Fix cho phần "Phân hệ bác sĩ" trong Dashboard */
        .content .bg-white.shadow-sm.mb-4 {
            margin-top: 20px;
        }
    </style>
    
    <!-- jQuery và Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="/UDPT-QLBN/public/js/bootstrap.bundle.min.js"></script>
</head>
<body class="doctor-layout">
    <div class="wrapper">
        <!-- Header -->
        <?php require_once "./mvc/views/layouts/header.php"; ?>

        <!-- Main Content -->
        <div class="main-container">
            <div class="content">
                <?php 
                // Debug path
                error_log("[DOCTORLO] Loading view: " . ($data["Page"] ?? 'not set'));
                
                // Kiểm tra và đảm bảo đúng định dạng đường dẫn
                $viewPath = "./mvc/views/" . ($data["Page"] ?? '') . ".php";
                if (file_exists($viewPath)) {
                    require_once $viewPath;
                } else {
                    echo "<div class='alert alert-danger'>View không tồn tại: " . htmlspecialchars($viewPath) . "</div>";
                    error_log("[DOCTORLO] View file not found: " . $viewPath);
                }
                ?>
            </div>
        </div>

        <!-- Footer -->
        <?php require_once "./mvc/views/layouts/footer.php"; ?>
    </div>
</body>
</html>