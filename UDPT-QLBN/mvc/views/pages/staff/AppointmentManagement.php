<?php if (!isset($data['flatpickr_loaded'])): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
    /* Các style hiện tại giữ nguyên */
    .form-spacing .form-group {
        margin-bottom: 1.5rem;
    }

    .time-input-wrapper {
        width: 80%;
        position: relative;
    }
    
    .form-row {
        margin-left: -20px;
        margin-right: -20px;
    }

    .flatpickr-time, 
    #time-from, 
    #time-to {
        height: calc(1.5em + .75rem + 2px) !important; 
        line-height: 1.5 !important;
    }

    .form-row > .col,
    .form-row > [class*="col-"] {
        padding-left: 20px;
        padding-right: 20px;
    }
    
    @media (max-width: 767.98px) {
        .time-input-wrapper {
            width: 100%;
            margin-left: 0;
        }
        
        .form-row {
            margin-left: -15px;
            margin-right: -15px;
        }
        
        .form-row > .col,
        .form-row > [class*="col-"] {
            padding-left: 15px;
            padding-right: 15px;
        }
    }
    
    .flatpickr-time .numInputWrapper {
        width: 100% !important;
    }
    
    .appointment-time-input {
        width: 100% !important;
    }
    
    .time-picker-icon {
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        width: 40px;
        text-align: center;
        background: transparent;
        border: none;
        padding: 0;
    }
    
    .input-group-text {
        padding: .375rem .5rem;
    }
    
    .time-only-input {
        width: 100% !important;
        border-right: 1px solid #ced4da;
    }
    
    #appointmentTime, #editTime {
        width: 100% !important;
    }
    
    .has-time-icon {
        padding-right: 40px;
    }

    .patient-results-container {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;
        display: none;
        background-color: #fff;
        max-height: 250px;
        overflow-y: auto;
        margin-top: 2px;
    }
    
    .patient-results-container.show {
        display: block;
    }
    
    .patient-item {
        padding: 0.5rem 1rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .patient-item:last-child {
        border-bottom: none;
    }
    
    .patient-item:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
    
    .dropdown-item {
        white-space: normal;
    }

    #doctorSelect {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    
    #doctorSelect {
        border-bottom: 2px solid #007bff;
    }

    /* ✅ THÊM: Style cho bộ lọc đơn giản */
    .filter-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .filter-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 15px;
    }
</style>

<?php endif; ?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><?= $data["pageTitle"] ?></h1>
            <p class="text-muted">Quản lý lịch hẹn khám bệnh</p>
        </div>
    </div>
</div>

<?php if(isset($data["error"])): ?>
<div class="alert alert-danger" role="alert">
    <i class="fas fa-exclamation-circle mr-2"></i>
    <?= $data["error"] ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <!-- ✅ BỘ LỌC: Chỉ 3 bộ lọc chính -->
        <div class="filter-section m-3">
            <div class="filter-title">
                <i class="fas fa-filter mr-2"></i> Bộ lọc lịch khám
            </div>
            <div class="row">
                <!-- Trạng thái -->
                <div class="col-md-4 mb-3">
                    <label for="status-filter">Trạng thái</label>
                    <select class="form-control" id="status-filter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Chưa khám">Chưa khám</option>
                        <option value="Đã khám">Đã khám</option>
                        <option value="Đã hủy">Đã hủy</option>
                    </select>
                </div>

                <!-- Từ ngày -->
                <div class="col-md-4 mb-3">
                    <label for="date-from">Từ ngày</label>
                    <input type="date" class="form-control" id="date-from" 
                           placeholder="dd/mm/yyyy">
                </div>

                <!-- Đến ngày -->
                <div class="col-md-4 mb-3">
                    <label for="date-to">Đến ngày</label>
                    <input type="date" class="form-control" id="date-to" 
                           placeholder="dd/mm/yyyy">
                </div>
            </div>

            <!-- Nút hành động -->
            <div class="row">
                <div class="col-12 text-right">
                    <button type="button" class="btn btn-secondary mr-2" id="reset-filter">
                        <i class="fas fa-redo mr-1"></i> Đặt lại
                    </button>
                    <button type="button" class="btn btn-primary mr-2" id="apply-filter">
                        <i class="fas fa-search mr-1"></i> Áp dụng lọc
                    </button>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addAppointmentModal">
                        <i class="fas fa-plus-circle mr-1"></i> Thêm lịch khám
                    </button>
                </div>
            </div>
        </div>
        
        <div id="loadingIndicator" class="text-center py-4 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Đang tải...</span>
            </div>
            <p class="mt-2">Đang tải dữ liệu...</p>
        </div>
        
        <!-- Bảng dữ liệu -->
        <div class="table-responsive">
            <table class="table table-hover" id="appointmentsTable">
                <thead class="bg-light">
                    <tr>
                        <th class="sortable align-middle" data-sort="appointment_id" width="7%">Mã LK </th>
                        <th class="sortable align-middle" data-sort="doctor_name">Bác sĩ </th>
                        <th class="sortable align-middle" data-sort="patient_name">Bệnh nhân </th>
                        <th class="sortable align-middle" data-sort="date" width="12%">Ngày khám </th>
                        <th class="sortable align-middle" data-sort="started_time" width="12%">Giờ khám </th>
                        <th class="sortable align-middle" data-sort="status" width="10%">Trạng thái </th>
                        <th class="align-middle" width="12%">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dữ liệu sẽ được load bằng JavaScript -->
                    <tr class="text-center" id="no-results-row" style="display: none;">
                        <td colspan="7" class="py-4">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-filter fa-3x text-muted mb-3"></i>
                                <h5 class="font-weight-normal text-muted">Không tìm thấy lịch khám nào phù hợp với bộ lọc</h5>
                                <button class="btn btn-outline-secondary mt-3" id="clear-filters">
                                    <i class="fas fa-times mr-1"></i>Xóa bộ lọc
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <div class="d-flex justify-content-between align-items-center p-3 border-top">
            <div class="text-muted" id="paginationInfo">Đang tải...</div>
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0" id="pagination">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" id="prevPage">
                            <i class="fas fa-chevron-left"></i> Trước
                        </a>
                    </li>
                    <li class="page-item active">
                        <a class="page-link" href="#">1</a>
                    </li>
                    <li class="page-item disabled">
                        <a class="page-link" href="#" id="nextPage">
                            Sau <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Các Modal giữ nguyên... -->
<!-- Modal Thêm Lịch Khám -->
<div class="modal fade" id="addAppointmentModal" tabindex="-1" aria-labelledby="addAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="addAppointmentModalLabel">Tạo lịch khám mới</h5>
            </div>
            <div class="modal-body">
                <form id="addAppointmentForm" class="form-spacing">
                    <!-- Thông tin cơ bản với spacing rộng hơn -->
                    <div class="form-row mb-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="department">Khoa <span class="text-danger">*</span></label>
                                <select class="form-control" id="department" required>
                                    <option value="">Chọn khoa</option>
                                    <option value="Khoa Nội">Khoa Nội</option>
                                    <option value="Khoa Ngoại">Khoa Ngoại</option>
                                    <option value="Chấn thương chỉnh hình">Chấn thương chỉnh hình</option>
                                    <option value="Khoa Nhi">Khoa nhi</option>
                                    <option value="Khoa Hồi sức cấp cứu">Khoa hồi sức cấp cứu</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="appointmentDate">Ngày khám <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="appointmentDate" required min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="appointmentTime">Giờ khám <span class="text-danger">*</span></label>
                                <div class="time-input-wrapper">
                                    <input type="text" class="form-control appointment-time-input" id="appointmentTime" required placeholder="HH:MM" readonly>
                                    <button type="button" class="time-picker-icon">
                                        <i class="far fa-clock"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thông báo nếu không có bác sĩ -->
                    <div id="noDoctorAlert" class="alert alert-warning d-none mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Không có bác sĩ nào rảnh vào thời gian này.
                        <br>Gợi ý: Thử chọn giờ từ 08:00 - 17:00 trong ngày làm việc hoặc 08:00 - 12:00 vào thứ 7.
                    </div>

                    <!-- Chọn bác sĩ -->
                    <div class="form-group mb-4" id="doctorSelectContainer">
                        <label for="doctorSelect">Bác sĩ khả dụng <span class="text-danger">*</span></label>
                        <select class="form-control" id="doctorSelect" required>
                            <option value="">Vui lòng chọn khoa, ngày và giờ khám trước</option>
                        </select>
                    </div>

                    <!-- Chọn bệnh nhân -->
                    <div class="form-group mb-4">
                        <label for="patientSearch">Bệnh nhân <span class="text-danger">*</span></label>
                        
                        <div class="position-relative">
                            <input type="text" class="form-control" id="patientSearch" placeholder="Nhập CMND/CCCD để tìm kiếm bệnh nhân" required>
                            
                            <div id="patientResults" class="patient-results-container w-100 border rounded shadow-sm">
                                <!-- Kết quả tìm kiếm bệnh nhân sẽ hiển thị ở đây -->
                            </div>
                        </div>
                        <input type="hidden" id="selectedPatientId">
                    </div>

                    <!-- Hiển thị thông tin bệnh nhân đã chọn -->
                    <div id="selectedPatientInfo" class="card mb-4 d-none">
                        <div class="card-body">
                            <h6 class="card-title">Thông tin bệnh nhân</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Họ tên:</strong> <span id="patientName"></span></p>
                                    <p><strong>Ngày sinh:</strong> <span id="patientDob"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>CCCD/CMND:</strong> <span id="patientIdCard"></span></p>
                                    <p><strong>SĐT:</strong> <span id="patientPhone"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mô tả -->
                    <div class="form-group">
                        <label for="description">Mô tả (tùy chọn)</label>
                        <textarea class="form-control" id="description" rows="3" placeholder="Nhập mô tả hoặc lý do khám"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="btnAddAppointment">Tạo lịch khám</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chi tiết lịch khám -->
<div class="modal fade" id="viewAppointmentModal" tabindex="-1" aria-labelledby="viewAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="viewAppointmentModalLabel">Chi tiết lịch khám</h5>
            </div>
            <div class="modal-body">
                <div id="appointmentDetails">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Đang tải...</span>
                        </div>
                    </div>
                    <!-- Prescription details will be loaded here -->
                    <div id="prescriptionDetails" class="mt-4 d-none">
                        <h6 class="font-weight-bold text-info mb-3">
                            <i class="fas fa-prescription mr-2"></i>Thông tin đơn thuốc
                        </h6>
                        <div class="card border-0 bg-light">
                            <div class="card-body p-3">
                                <div id="prescriptionStatus" class="mb-3"></div>
                                <div id="prescriptionContent">
                                    <p class="text-muted">Đang tải thông tin đơn thuốc...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="btnEditAppointment">Chỉnh sửa</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chỉnh sửa lịch khám -->
<div class="modal fade" id="editAppointmentModal" tabindex="-1" aria-labelledby="editAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="editAppointmentModalLabel">Chỉnh sửa lịch khám</h5>
            </div>
            <div class="modal-body">
                <form id="editAppointmentForm" class="form-spacing">
                    <input type="hidden" id="editAppointmentId">
                    
                    <div class="form-row mb-4">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label for="editDate">Ngày khám</label>
                                <input type="date" class="form-control" id="editDate" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="editTime">Giờ khám</label>
                                <div class="time-input-wrapper">
                                    <input type="text" class="form-control appointment-time-input" id="editTime" required placeholder="HH:MM" readonly>
                                    <button type="button" class="time-picker-icon">
                                        <i class="far fa-clock"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label for="editStatus">Trạng thái</label>
                        <select class="form-control" id="editStatus" required>
                            <option value="Chưa khám">Chưa khám</option>
                            <option value="Đã khám">Đã khám</option>
                            <option value="Đã hủy">Đã hủy</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label for="editPrescriptionStatus">Trạng thái đơn thuốc</label>
                        <select class="form-control" id="editPrescriptionStatus" required>
                            <option value="Chưa lấy">Chưa lấy</option>
                            <option value="Đã lấy">Đã lấy</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editDescription">Mô tả</label>
                        <textarea class="form-control" id="editDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="btnSaveEdit">Lưu thay đổi</button>
            </div>
        </div>
    </div>
</div>

<script src="/UDPT-QLBN/public/js/appointment-management.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#addAppointmentModal, #editAppointmentModal').on('shown.bs.modal', function() {
        setTimeout(function() {
            if (typeof initializeFlatpickr === 'function') {
                initializeFlatpickr();
            }
        }, 100);
    });
});
</script>
