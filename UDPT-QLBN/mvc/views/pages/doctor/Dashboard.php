<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
    /* Styling tổng quát */
    .stats-card {
        transition: all 0.2s ease;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    
    .stats-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        margin-right: 16px;
    }
    
    /* Badge styling */
    .badge-success {
        background-color: #d1e7dd;
        color: #0f5132;
        border: none;
    }
    
    .badge-warning {
        background-color: #fff3cd;
        color: #856404;
        border: none;
    }
    
    .badge-danger {
        background-color: #f8d7da;
        color: #842029;
        border: none;
    }
    
    .badge-outline-success {
        background-color: transparent;
        color: #198754;
        border: 1px solid #198754;
    }
    
    .badge-outline-secondary {
        background-color: transparent;
        color: #6c757d;
        border: 1px solid #6c757d;
    }
    
    /* Tab styling */
    .nav-tabs .nav-link.active {
        font-weight: 500;
        color: #0d6efd;
        border-bottom: 2px solid #0d6efd;
        background: transparent;
    }
    
    .nav-tabs .nav-link {
        border: none;
        padding: 0.75rem 1rem;
        color: #495057;
    }
    
    .prescription-empty {
        padding: 40px 20px;
        text-align: center;
        color: #6c757d;
    }
    
    .prescription-empty i {
        font-size: 3.5rem;
        opacity: 0.4;
        margin-bottom: 1rem;
    }
    
    .prescription-item {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        transition: all 0.2s ease;
    }
    
    .prescription-item:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .patient-info-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    
    .patient-info-value {
        font-weight: 500;
    }
    
    /* Modal styling */
    .modal-xl {
        max-width: 1140px;
    }
    
    .form-group label {
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    
    /* Custom button styles */
    .btn-add-medicine {
        width: 36px;
        height: 36px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    
    .table td {
        vertical-align: middle;
    }

    /* Thêm vào phần <style> của Dashboard.php */
    .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .dashboard-section {
        margin-bottom: 30px;
    }

    /* Tab styling */
    .nav-tabs {
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 20px;
    }

    /* Table responsive với border radius */
    .table-responsive {
        border-radius: 10px;
        overflow: hidden;
    }

    /* Bảng có khoảng cách tốt hơn */
    .table th, .table td {
        padding: 15px;
    }

    /* Card có margin tốt hơn */
    .card {
        margin-bottom: 25px;
    }

    /* Phân hệ title có padding tốt hơn */
    .bg-white.shadow-sm.mb-4 {
        padding: 20px 0;
        margin-bottom: 25px !important;
    }
    .bg-white.shadow-sm.mb-4 {
        padding: 20px 0;
        margin-bottom: 30px !important;
        margin-top: 0; /* Đảm bảo không có margin phía trên */
    }
    
    /* Thêm khoảng cách giữa các phần */
    .container-fluid.pb-4 {
        padding-top: 10px;
        padding-bottom: 30px !important;
    }
    
    /* Fix khoảng cách phía trên cho card */
    .row.mb-4 {
        margin-top: 10px;
    }

    #prescriptionContainer {
        position: relative;
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 460px;
    }
    
    #prescriptionItems {
        max-height: 670px;
        overflow-y: auto;
        padding-right: 5px;
    }
    
    /* Điều chỉnh style cho item thuốc để tốt hơn trong không gian có scroll */
    .prescription-item {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        transition: all 0.2s ease;
        background-color: #fff;
    }
    
    /* Tạo hiệu ứng shadow khi hover lên item thuốc */
    .prescription-item:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    /* Đảm bảo rằng #emptyPrescription không bị scroll */
    #emptyPrescription {
         position: relative;
        padding: 40px 20px;
        text-align: center;
        color: #6c757d;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100%;
        flex: 1;
    }
    
    /* Cố định card đơn thuốc có chiều cao nhất định */
    .prescription-card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .prescription-card .card-body {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    /* Thanh cuộn có style đẹp hơn */
    #prescriptionItems::-webkit-scrollbar {
        width: 6px;
    }
    
    #prescriptionItems::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    #prescriptionItems::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 10px;
    }
    
    #prescriptionItems::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    #filterOptions .form-control {
        height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 0.75rem;
    }
    
    #filterOptions label {
        margin-bottom: 0.35rem;
        display: block;
    }
    
    /* Căn chỉnh nút trong bộ lọc */
    #filterOptions .col-md-4.d-flex {
        padding-bottom: 0.35rem;
    }
    
    /* Đảm bảo các input date có độ rộng đồng nhất */
    #filterOptions input[type="date"].form-control {
        min-width: 100%;
    }
    
    /* Đảm bảo select có độ rộng đồng nhất */
    #filterOptions select.form-control {
        min-width: 100%;
    }
    
    /* Căn chỉnh kích thước nút để cân đối */
    #filterOptions .btn {
        padding: 0.375rem 0.75rem;
    }
    
    /* Loading spinner */
    .loading-spinner {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 2rem;
    }
    
    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }

    #examModal .modal-dialog {
        max-width: 1200px; /* Tăng width */
        width: 95%; /* Responsive width */
    }

    #examModal .modal-body {
        max-height: 80vh; /* Giới hạn chiều cao */
        overflow-y: auto; /* Scroll khi cần */
        padding: 1.5rem; /* Padding đều */
    }

    /* ✅ FIX: Row layout không wrap */
    #examModalContent.row {
        margin: 0; /* Loại bỏ margin âm của Bootstrap row */
        min-height: 600px; /* Đảm bảo chiều cao tối thiểu */
    }

    #examModalContent .col-lg-7,
    #examModalContent .col-lg-5 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
        display: flex;
        flex-direction: column;
    }

    /* ✅ FIX: Đảm bảo cột phải không xuống dưới */
    @media (min-width: 992px) {
        #examModalContent .col-lg-7 {
            flex: 0 0 58.333333%; /* 7/12 = 58.33% */
            max-width: 58.333333%;
        }
        
        #examModalContent .col-lg-5 {
            flex: 0 0 41.666667%; /* 5/12 = 41.67% */
            max-width: 41.666667%;
        }
    }

    /* ✅ FIX: Card đơn thuốc có chiều cao cố định */
    #examModal .col-lg-5 .card {
        height: 100%;
        min-height: 580px; /* Chiều cao tối thiểu */
        display: flex;
        flex-direction: column;
    }

    #examModal .col-lg-5 .card-body {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding: 1rem;
    }

    /* ✅ FIX: Prescription container layout */
    #prescriptionContainer {
        position: relative;
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 450px;
    }

    #emptyPrescription {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 2rem;
        text-align: center;
        color: #6c757d;
    }

    #prescriptionItems {
        flex: 1;
        overflow-y: auto;
        max-height: 500px; /* Giới hạn chiều cao để có scroll */
        padding-right: 8px; /* Space cho scrollbar */
    }

    /* ✅ FIX: Responsive cho màn hình nhỏ */
    @media (max-width: 991.98px) {
        #examModal .modal-dialog {
            max-width: 95%;
            margin: 1rem;
        }
        
        #examModal .modal-body {
            max-height: 85vh;
            padding: 1rem;
        }
        
        #examModalContent .col-lg-7,
        #examModalContent .col-lg-5 {
            margin-bottom: 1rem;
        }
        
        #examModal .col-lg-5 .card {
            min-height: 400px;
        }
    }

    /* ✅ FIX: Đảm bảo medicine selection modal không bị ảnh hưởng */
    #medicineSelectionModal .modal-dialog {
        max-width: 900px;
    }

    /* ✅ FIX: Scrollbar styling cho prescription items */
    #prescriptionItems::-webkit-scrollbar {
        width: 6px;
    }

    #prescriptionItems::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    #prescriptionItems::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 10px;
    }

    #prescriptionItems::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* ✅ FIX: Medicine item styling */
    #examModal .prescription-item {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background-color: #f8f9fa;
        transition: all 0.2s ease;
    }

    #examModal .prescription-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    #examModal .medicine-header {
        background-color: #e9ecef;
        margin: -15px -15px 15px -15px;
        padding: 10px 15px;
        border-radius: 8px 8px 0 0;
        border-bottom: 1px solid #dee2e6;
    }

    #examModal .medicine-info {
        background-color: #e7f3ff;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 0.875rem;
        margin-bottom: 15px;
    }

    #examModal .medicine-info .medicine-name {
        font-weight: 600;
        color: #0d6efd;
    }

    #examModal .medicine-info .medicine-stock {
        color: #6c757d;
    }

    #examModal .form-label {
        font-weight: 600;
        color: #495057;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }

    #examModal .col-lg-7 .card:last-child .card-header {
        background-color: #f8f9fa !important; /* Màu xám nhạt */
        color: #495057 !important; /* Màu chữ xám đậm */
        border-bottom: 1px solid #dee2e6;
    }

    #examModal .col-lg-5 .card .card-header {
        background-color: #f8f9fa !important; /* Màu xám nhạt */
        color: #495057 !important; /* Màu chữ xám đậm */
        border-bottom: 1px solid #dee2e6;
    }

    .simple-medicine-form {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background-color: #f8f9fc;
        transition: all 0.3s ease;
    }

    .simple-medicine-form:hover {
        border-color: #0d6efd;
        background-color: #e7f3ff;
    }

    .simple-medicine-form .form-group {
        margin-bottom: 10px;
    }

    .simple-medicine-form .form-group:last-child {
        margin-bottom: 0;
    }

    .simple-medicine-form .form-label {
        font-weight: 600;
        color: #495057;
        font-size: 0.875rem;
        margin-bottom: 5px;
    }

    .simple-medicine-form .form-control {
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 0.875rem;
    }

    .simple-medicine-form .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }

    .simple-medicine-form .btn-group {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }

    /* ✅ UPDATED: Medicine item styling */
    .medicine-item {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 12px;
        background-color: #ffffff;
        transition: all 0.2s ease;
        position: relative;
    }

    .medicine-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-color: #0d6efd;
    }

    .medicine-item .medicine-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .medicine-item .medicine-name {
        font-weight: 600;
        color: #0d6efd;
        font-size: 0.95rem;
    }

    .medicine-item .medicine-details {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .medicine-item .medicine-note {
        font-size: 0.85rem;
        color: #495057;
        font-style: italic;
    }

    .medicine-item .btn-remove {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 24px;
        height: 24px;
        padding: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ✅ NEW: Animation cho form xuất hiện */
    .simple-medicine-form.new-form {
        animation: slideInForm 0.3s ease;
    }

    .medicine-form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }

    .medicine-form-counter {
        background-color: #0d6efd;
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* ✅ SỬA: Nút X căn phải */
    .medicine-form-header .btn-remove-form {
        margin-left: auto;
        flex-shrink: 0;
    }

    @keyframes slideInForm {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .medicine-suggestions {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-top: none !important;
    }

    .medicine-suggestion-item {
        transition: background-color 0.2s ease;
    }

    .medicine-suggestion-item:hover {
        background-color: #f8f9fa !important;
    }

    .medicine-suggestion-item:last-child {
        border-bottom: none !important;
    }

    /* ✅ NEW: Medicine info display */
    .medicine-info-display .alert {
        margin-bottom: 0.75rem;
        font-size: 0.875rem;
    }

    .medicine-selected-name {
        color: #0d6efd;
    }

    .medicine-selected-unit {
        font-size: 0.8rem;
    }

    .medicine-selected-stock {
        font-size: 0.75rem;
    }

    /* ✅ NEW: Input validation styling */
    .form-control.is-invalid {
        border-color: #dc3545;
        padding-right: calc(1.5em + 0.75rem);
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }

    /* ✅ UPDATED: Medicine form responsive */
    @media (max-width: 768px) {
        .medicine-suggestions {
            max-height: 150px;
        }
        
        .medicine-info-display .alert {
            padding: 0.5rem;
        }
    }

    #viewExamModal .table-bordered {
        border: 1px solid #dee2e6;
    }

    #viewExamModal .table-bordered th,
    #viewExamModal .table-bordered td {
        border: 1px solid #dee2e6;
        padding: 8px 12px;
        vertical-align: middle;
    }

    #viewExamModal .table-bordered th {
        background-color: #f8f9fa;
        font-weight: 600;
        font-size: 0.9rem;
    }

    #viewExamModal .table-bordered td {
        font-size: 0.875rem;
    }

    #viewExamModal .medicine-name {
        font-weight: 600;
        color: #0d6efd;
    }

    #viewExamModal .medicine-amount {
        font-weight: 600;
        color: #28a745;
        text-align: center;
    }

    #viewExamModal .medicine-note {
        color: #495057;
        font-style: italic;
    }

    /* ✅ SỬA: Responsive cho modal nhỏ */
    @media (max-width: 767.98px) {
        #viewExamModal .modal-dialog {
            max-width: 95%;
            margin: 0.5rem;
        }
        
        #viewExamModal .table-responsive {
            font-size: 0.8rem;
        }
        
        #viewExamModal .card-body {
            padding: 1rem;
        }
    }

    /* ✅ SỬA: Badge styling đơn giản */
    #viewPrescriptionStatus.badge-warning {
        background-color: #ffc107;
        color: #212529;
    }

    #viewPrescriptionStatus.badge-success {
        background-color: #28a745;
        color: white;
    }

    #viewPrescriptionStatus.badge-danger {
        background-color: #dc3545;
        color: white;
    }

    #viewExamModal .table-bordered th {
        background-color: #f8f9fa;
        font-weight: 600;
        font-size: 0.9rem;
        border: 1px solid #dee2e6;
        padding: 12px 8px;
        vertical-align: middle;
    }

    #viewExamModal .table-bordered td {
        border: 1px solid #dee2e6;
        padding: 10px 8px;
        vertical-align: middle;
        font-size: 0.875rem;
    }

    /* ✅ SỬA: Medicine ID styling */
    #viewExamModal .medicine-id {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.95rem;
    }

    /* ✅ SỬA: Medicine name styling */
    #viewExamModal .medicine-name {
        font-weight: 600;
        color: #0d6efd;
        font-size: 0.95rem;
    }

    /* ✅ SỬA: Unit styling */
    #viewExamModal .medicine-unit {
        font-weight: 500;
        color: #495057;
        font-size: 0.875rem;
    }

    /* ✅ SỬA: Amount styling */
    #viewExamModal .medicine-amount {
        font-weight: 600;
        color: #28a745;
        font-size: 1rem;
    }

    /* ✅ SỬA: Note styling */
    #viewExamModal .medicine-note {
        color: #495057;
        font-style: italic;
        font-size: 0.875rem;
    }

    /* ✅ SỬA: Đảm bảo cột không bị lệch */
    #viewExamModal .table-bordered th:nth-child(1),
    #viewExamModal .table-bordered td:nth-child(1) {
        width: 10%;
        text-align: center;
    }

    #viewExamModal .table-bordered th:nth-child(2),
    #viewExamModal .table-bordered td:nth-child(2) {
        width: 35%;
    }

    #viewExamModal .table-bordered th:nth-child(3),
    #viewExamModal .table-bordered td:nth-child(3) {
        width: 15%;
        text-align: center;
    }

    #viewExamModal .table-bordered th:nth-child(4),
    #viewExamModal .table-bordered td:nth-child(4) {
        width: 15%;
        text-align: center;
    }

    #viewExamModal .table-bordered th:nth-child(5),
    #viewExamModal .table-bordered td:nth-child(5) {
        width: 25%;
    }

    .patient-info-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
        font-weight: 500;
    }

    .patient-info-value {
        font-weight: 500;
        color: #495057;
    }

    #medicalHistoryView {
        border: 1px solid #e9ecef;
        background-color: #f8f9fa;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    #medicalHistoryView:hover {
        background-color: #e9ecef;
    }

    #medicalHistoryTextarea {
        resize: vertical;
        min-height: 80px;
        font-size: 0.9rem;
    }

    #medicalHistoryTextarea:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }

    /* ✅ SỬA: Responsive cho medical history */
    @media (max-width: 767.98px) {
        #medicalHistoryView {
            padding: 1rem !important;
        }
        
        #medicalHistoryTextarea {
            font-size: 0.85rem;
        }
    }
</style>

<!-- Header -->
<div class="bg-white shadow-sm mb-4">
</div>

<!-- Main Content -->
<div class="container-fluid pb-4">
    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="doctorTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active d-flex align-items-center" id="appointments-tab" data-toggle="tab" href="#appointments" role="tab" aria-controls="appointments" aria-selected="true">
                <i class="fas fa-calendar-check mr-2"></i>
                Lịch khám của tôi
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link d-flex align-items-center" id="medicines-tab" data-toggle="tab" href="#medicines" role="tab" aria-controls="medicines" aria-selected="false">
                <i class="fas fa-pills mr-2"></i>
                Kho thuốc
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="doctorTabsContent">
        <!-- Tab 1: Lịch khám -->
        <div class="tab-pane fade show active" id="appointments" role="tabpanel" aria-labelledby="appointments-tab">
            <!-- Bộ lọc lịch khám -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Bộ lọc lịch khám</h5>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="statusFilter" class="small font-weight-bold">Trạng thái</label>
                                        <select class="form-control" id="statusFilter">
                                            <option value="">Tất cả trạng thái</option>
                                            <option value="Chưa khám">Chưa khám</option>
                                            <option value="Đã khám">Đã khám</option>
                                            <option value="Đã hủy">Đã hủy</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="startDateFilter" class="small font-weight-bold">Từ ngày</label>
                                        <input type="date" class="form-control" id="startDateFilter">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="endDateFilter" class="small font-weight-bold">Đến ngày</label>
                                        <input type="date" class="form-control" id="endDateFilter">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end justify-content-end">
                            <button class="btn btn-secondary mr-2" id="resetFilterBtn">Đặt lại</button>
                            <button class="btn btn-primary" id="applyFilterBtn">Áp dụng</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bảng lịch khám -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Danh sách lịch khám</h5>
                </div>
                <div class="table-responsive">
                    <table class="table" id="appointmentsTable">
                        <thead>
                            <tr>
                                <th>Bệnh nhân</th>
                                <th>Ngày khám</th>
                                <th>Giờ khám</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="appointmentsTableBody">
                            <!-- Dữ liệu sẽ được load từ API -->
                        </tbody>
                    </table>
                    
                    <!-- Loading spinner -->
                    <div class="loading-spinner" id="appointmentsLoading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Đang tải...</span>
                        </div>
                    </div>
                    
                    <!-- Empty state -->
                    <div class="empty-state" id="appointmentsEmpty" style="display: none;">
                        <i class="fas fa-calendar-times"></i>
                        <h5>Không có lịch khám</h5>
                        <p class="text-muted">Không tìm thấy lịch khám nào phù hợp với điều kiện tìm kiếm.</p>
                    </div>
                </div>
                
                <!-- Phân trang -->
                <div class="card-footer bg-white">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0" id="appointmentsPagination">
                            <!-- Phân trang sẽ được render từ JavaScript -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Tab 2: Kho thuốc -->
        <div class="tab-pane fade" id="medicines" role="tabpanel" aria-labelledby="medicines-tab">
            <!-- Tìm kiếm thuốc -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-9 mb-3 mb-md-0">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchMedicine" placeholder="Tìm kiếm theo tên thuốc...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" id="searchMedicineBtn">
                                        Tìm kiếm
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 text-md-right">
                            <button class="btn btn-outline-primary" id="showNearExpiryBtn">
                                Sắp hết hạn
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bảng thuốc -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Danh sách thuốc có sẵn</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered" id="medicinesTable">
                        <thead>
                            <tr>
                                <th width="10%" class="text-center">Mã</th>
                                <th width="45%">Tên thuốc</th>
                                <th width="15%" class="text-center">Đơn vị</th>
                                <th width="15%" class="text-right">Giá (VNĐ)</th>
                                <th width="15%" class="text-center">Tồn kho</th>
                            </tr>
                        </thead>
                        <tbody id="medicinesTableBody">
                            <!-- Dữ liệu sẽ được load từ API -->
                        </tbody>
                    </table>
                    
                    <!-- Loading spinner -->
                    <div id="medicinesLoading" style="display: none; text-align: center; padding: 2rem;">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Đang tải...</span>
                        </div>
                        <p class="mt-2">Đang tải danh sách thuốc...</p>
                    </div>
                    
                    <!-- Empty state -->
                    <div id="medicinesEmpty" style="display: none; text-align: center; padding: 2rem;">
                        <h6>Không có thuốc</h6>
                        <p>Không tìm thấy thuốc nào phù hợp với điều kiện tìm kiếm.</p>
                    </div>
                </div>
                
                <!-- Phân trang -->
                <div class="card-footer">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0" id="medicinesPagination">
                            <!-- Phân trang sẽ được render từ JavaScript -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal xem chi tiết khám bệnh và đơn thuốc -->
<div class="modal fade" id="examModal" tabindex="-1" aria-labelledby="examModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl"> <!-- Giữ modal-xl -->
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="examModalLabel">
                    <i class="fas fa-stethoscope mr-2"></i>Khám bệnh - <span id="examPatientName">Bệnh nhân</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Loading state -->
                <div id="examModalLoading" class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                    <p class="mt-3 text-muted">Đang tải thông tin khám bệnh...</p>
                </div>

                <!-- Error state -->
                <div id="examModalError" class="text-center p-5" style="display: none;">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <h5>Không thể tải thông tin</h5>
                    <p class="text-muted" id="examModalErrorMessage">Đã xảy ra lỗi khi tải dữ liệu.</p>
                </div>

                <!-- ✅ UPDATED: Main content với d-flex và no-gutters -->
                <div id="examModalContent" class="row no-gutters d-flex" style="display: none;">
                    <!-- ✅ UPDATED: Cột trái với flex properties -->
                    <div class="col-lg-7 d-flex flex-column">
                        <!-- Thông tin bệnh nhân -->
                        <div class="card mb-3 flex-shrink-0">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-user mr-2"></i>Thông tin bệnh nhân
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="patient-info-label">Họ tên</div>
                                        <div class="patient-info-value" id="examPatientNameDetail">-</div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="patient-info-label">Ngày sinh</div>
                                        <div class="patient-info-value" id="examPatientDob">-</div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="patient-info-label">Giới tính</div>
                                        <div class="patient-info-value" id="examPatientGender">-</div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="patient-info-label">Số điện thoại</div>
                                        <div class="patient-info-value" id="examPatientPhone">-</div>
                                    </div>
                                    <div class="col-md-6 mb-0">
                                        <div class="patient-info-label">Ngày khám</div>
                                        <div class="patient-info-value" id="examAppointmentDate">-</div>
                                    </div>
                                    <div class="col-md-6 mb-0">
                                        <div class="patient-info-label">Giờ khám</div>
                                        <div class="patient-info-value" id="examAppointmentTime">-</div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="patient-info-label d-flex justify-content-between align-items-center">
                                            <span>Tiền sử bệnh</span>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="editMedicalHistoryBtn">
                                                <i class="fas fa-edit mr-1"></i>Chỉnh sửa
                                            </button>
                                        </div>
                                        
                                        <!-- ✅ View mode -->
                                        <div id="medicalHistoryView" class="mt-2 p-3 bg-light rounded">
                                            <div id="examPatientMedicalHistory" style="white-space: pre-wrap; min-height: 40px;">
                                                Đang tải thông tin tiền sử bệnh...
                                            </div>
                                        </div>
                                        
                                        <!-- ✅ Edit mode -->
                                        <div id="medicalHistoryEdit" class="mt-2" style="display: none;">
                                            <textarea class="form-control" id="medicalHistoryTextarea" 
                                                    rows="3" placeholder="Nhập tiền sử bệnh của bệnh nhân..."></textarea>
                                            <div class="mt-2 text-right">
                                                <button type="button" class="btn btn-sm btn-secondary mr-2" id="cancelMedicalHistoryBtn">
                                                    Hủy
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary" id="saveMedicalHistoryBtn">
                                                    <i class="fas fa-save mr-1"></i>Lưu
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ✅ UPDATED: Hồ sơ khám bệnh với flex-grow -->
                        <div class="card flex-grow-1">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-file-medical mr-2"></i>Hồ sơ khám bệnh
                                </h6>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div class="form-group flex-grow-1">
                                    <label for="examDescription">
                                        <i class="fas fa-notes-medical mr-1"></i>Mô tả khám bệnh 
                                        <small class="text-muted">(Triệu chứng, chẩn đoán, ghi chú)</small>
                                    </label>    
                                    <!-- ✅ SỬA: Giảm rows từ 8 xuống 6 và min-height từ 200px xuống 150px -->
                                    <textarea class="form-control" id="examDescription" rows="6" 
                                            style="resize: none; min-height: 150px; font-size: 14px;"></textarea>
                                </div>
                                
                                <div class="form-group mb-0 flex-shrink-0">
                                    <label for="prescriptionDays">
                                        <i class="fas fa-calendar-day mr-1"></i>Số ngày uống thuốc
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="prescriptionDays" 
                                            min="1" max="30" value="7" placeholder="Số ngày">
                                        <div class="input-group-append">
                                            <span class="input-group-text">ngày</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ✅ UPDATED: Cột phải với fixed width và height -->
                    <div class="col-lg-5 d-flex flex-column">
                        <div class="card h-100 d-flex flex-column">
                            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center flex-shrink-0">
                                <h6 class="mb-0">
                                    <i class="fas fa-prescription-bottle-alt mr-2"></i>Đơn thuốc
                                </h6>
                                <button type="button" class="btn btn-primary btn-sm" id="addMedicineBtn">
                                    <i class="fas fa-plus mr-1"></i> Thêm thuốc
                                </button>
                            </div>
                            <div class="card-body flex-grow-1 p-0 position-relative" id="prescriptionContainer">
                                <!-- ✅ UPDATED: Empty state -->
                                <div class="prescription-empty" id="emptyPrescription">
                                    <i class="fas fa-prescription-bottle fa-3x mb-3 text-muted"></i>
                                    <p class="mb-1 font-weight-medium">Chưa có thuốc trong đơn</p>
                                    <p class="small text-muted">Nhấn "Thêm thuốc" để bắt đầu kê đơn</p>
                                </div>
                                
                                <!-- ✅ NEW: Prescription items với proper scrolling -->
                                <div id="prescriptionItems" style="display: none; padding: 1rem; max-height: 450px; overflow-y: auto;">
                                    <!-- Các thuốc sẽ được thêm vào đây bằng JavaScript -->
                                </div>
                            </div>
                            
                            <!-- Footer hiển thị tổng số thuốc -->
                            <div class="card-footer bg-light flex-shrink-0" id="prescriptionSummary" style="display: none;">
                                <small class="text-muted">
                                    <i class="fas fa-pills mr-1"></i>
                                    Tổng số loại thuốc: <span id="totalMedicinesCount">0</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Hủy
                </button>
                <button type="button" class="btn btn-primary" id="saveExamBtn">
                    <i class="fas fa-save mr-1"></i>Lưu thông tin khám
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="medicineSelectionModal" tabindex="-1" aria-labelledby="medicineSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="medicineSelectionModalLabel">
                    <i class="fas fa-search mr-2"></i>Chọn thuốc
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Tìm kiếm thuốc -->
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" id="medicineSearchInput" 
                               placeholder="Tìm kiếm tên thuốc...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="searchMedicineInModalBtn">
                                Tìm kiếm
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading thuốc -->
                <div id="medicineSelectionLoading" class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                    <p class="mt-2 text-muted">Đang tải danh sách thuốc...</p>
                </div>

                <!-- Danh sách thuốc -->
                <div id="medicineSelectionList" style="display: none;">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover">
                            <thead class="thead-light sticky-top">
                                <tr>
                                    <th>Tên thuốc</th>
                                    <th>Đơn vị</th>
                                    <th>Tồn kho</th>
                                    <th>Hạn SD</th>
                                    <th width="80">Chọn</th>
                                </tr>
                            </thead>
                            <tbody id="medicineSelectionTableBody">
                                <!-- Danh sách thuốc sẽ được render ở đây -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empty state -->
                <div id="medicineSelectionEmpty" class="text-center p-4" style="display: none;">
                    <i class="fas fa-search text-muted fa-3x mb-3"></i>
                    <h6 class="text-muted">Không tìm thấy thuốc</h6>
                    <p class="text-muted small">Thử tìm kiếm với từ khóa khác</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewExamModal" tabindex="-1" aria-labelledby="viewExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- ✅ SỬA: modal-lg thay vì modal-xl -->
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewExamModalLabel">
                    <i class="fas fa-eye mr-2"></i>Chi tiết lịch khám - <span id="viewExamPatientName">Bệnh nhân</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Loading state -->
                <div id="viewExamModalLoading" class="text-center p-4">
                    <div class="spinner-border text-info" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                    <p class="mt-2 text-muted">Đang tải thông tin...</p>
                </div>

                <!-- Error state -->
                <div id="viewExamModalError" class="text-center p-4" style="display: none;">
                    <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                    <h6>Không thể tải thông tin</h6>
                    <p class="text-muted small" id="viewExamModalErrorMessage">Đã xảy ra lỗi khi tải dữ liệu.</p>
                    <button type="button" class="btn btn-sm btn-primary" id="retryLoadViewExam">
                        <i class="fas fa-redo mr-1"></i>Thử lại
                    </button>
                </div>

                <!-- ✅ SỬA: Main content đơn giản, một cột -->
                <div id="viewExamModalContent" style="display: none;">
                    <!-- Thông tin bệnh nhân -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-user mr-2"></i>Thông tin bệnh nhân
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- ✅ SỬA: IDs chính xác theo JavaScript -->
                                    <p class="mb-1"><strong>Họ tên:</strong> <span id="viewPatientName">-</span></p>
                                    <p class="mb-1"><strong>Giới tính:</strong> <span id="viewPatientGender">-</span></p>
                                    <p class="mb-0"><strong>SĐT:</strong> <span id="viewPatientPhone">-</span></p>
                                </div>
                                <div class="col-md-6">
                                    <!-- ✅ SỬA: IDs chính xác theo JavaScript -->
                                    <p class="mb-1"><strong>Ngày sinh:</strong> <span id="viewPatientDob">-</span></p>
                                    <p class="mb-1"><strong>Ngày khám:</strong> <span id="viewAppointmentDate">-</span></p>
                                    <p class="mb-0"><strong>Giờ khám:</strong> <span id="viewAppointmentTime">-</span></p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <p class="mb-1"><strong>Tiền sử bệnh:</strong></p>
                                    <!-- ✅ SỬA: ID chính xác theo JavaScript -->
                                    <div class="bg-light p-3 rounded" id="viewPatientMedicalHistory" 
                                        style="min-height: 60px; white-space: pre-wrap;">
                                        Đang tải thông tin tiền sử bệnh...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kết quả khám bệnh -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-file-medical-alt mr-2"></i>Kết quả khám bệnh
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- ✅ SỬA: ID chính xác theo JavaScript -->
                            <div class="bg-light p-3 rounded" id="viewExamDescription" style="min-height: 80px; white-space: pre-wrap;">
                                Chưa có thông tin khám bệnh
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <!-- ✅ SỬA: ID chính xác theo JavaScript -->
                                    <p class="mb-0"><strong>Trạng thái:</strong> 
                                        <span class="badge badge-success ml-1" id="viewAppointmentStatus">Đã khám</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <!-- ✅ SỬA: ID chính xác theo JavaScript -->
                                    <p class="mb-0"><strong>Ngày hoàn thành:</strong> <span id="viewCompletedDate">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ✅ SỬA: Đơn thuốc với IDs chính xác -->
                    <div class="card">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-prescription-bottle-alt mr-2"></i>Đơn thuốc
                            </h6>
                            <div>
                                <span class="badge badge-info mr-2" id="viewPrescriptionStatus">Chưa lấy</span>
                                <small class="text-muted">Số ngày: <span id="viewPrescriptionDays">-</span></small>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Thông báo không có đơn thuốc -->
                            <div id="viewNoPrescription" class="text-center p-3" style="display: none;">
                                <i class="fas fa-prescription-bottle fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Không có đơn thuốc</p>
                            </div>
                            
                            <!-- Loading thuốc -->
                            <div id="viewMedicinesLoading" class="text-center p-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="sr-only">Đang tải thuốc...</span>
                                </div>
                                <p class="mt-2 text-muted small mb-0">Đang tải danh sách thuốc...</p>
                            </div>
                            
                            <!-- ✅ SỬA: Bảng thuốc với IDs chính xác -->
                            <div id="viewMedicinesTable" style="display: none;">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="10%" class="text-center">ID</th>
                                                <th width="35%">Tên thuốc</th>
                                                <th width="15%" class="text-center">Đơn vị</th>
                                                <th width="15%" class="text-center">Số lượng</th>
                                                <th width="25%">Cách dùng</th>
                                            </tr>
                                        </thead>
                                        <tbody id="viewMedicinesTableBody">
                                            <!-- ✅ Mỗi thuốc sẽ có 1 dòng riêng với đầy đủ 5 cột -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- ✅ SỬA: Tổng kết với ID chính xác -->
                                <div class="mt-2 text-right">
                                    <small class="text-muted">
                                        Tổng: <strong id="viewMedicinesCount">0</strong> loại thuốc
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Error loading medicines -->
                            <div id="viewMedicinesError" class="text-center p-3" style="display: none;">
                                <i class="fas fa-exclamation-triangle text-warning mb-2"></i>
                                <p class="text-muted small mb-2" id="viewMedicinesErrorMessage">Không thể tải danh sách thuốc</p>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="retryLoadMedicines">
                                    <i class="fas fa-redo mr-1"></i>Thử lại
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Đóng
                </button>
                <button type="button" class="btn btn-info" id="printExamReport">
                    <i class="fas fa-print mr-1"></i>In báo cáo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Truyền user data từ PHP sang JavaScript
window.userData = {
    userId: "<?php echo $_SESSION['user_id'] ?? ''; ?>",
    username: "<?php echo $_SESSION['username'] ?? ''; ?>", 
    role: "<?php echo $_SESSION['role'] ?? ''; ?>"
};

console.log("User ID:", window.userData.userId);
</script>

<script src="/UDPT-QLBN/public/js/doctor-appointment-management.js"></script>