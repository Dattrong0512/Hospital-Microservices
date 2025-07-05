<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/UDPT-QLBN/public/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/UDPT-QLBN/public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <title><?= $data['pageTitle'] ?? 'Hệ thống Quản lý Bệnh viện ABC' ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>
    
</head>
<body>
    <?php require_once "./mvc/views/layouts/header.php"; ?>

    <!-- Bố cục chính -->
    <div class="main-container">
        <div class="sidebar-container">
            <?php require_once "./mvc/views/layouts/leftpage.php"; ?>
        </div>

        <!-- Nội dung chính -->
        <div class="content-container">
            <?php
            if (isset($data["Page"])) {
                require_once "./mvc/views/pages/" . $data["Page"] . ".php";
            }
            ?>
            
            <?php require_once "./mvc/views/layouts/footer.php"; ?>
        </div>
    </div>

    <script src="/UDPT-QLBN/public/js/bootstrap.bundle.min.js"></script>
</body>
</html>