<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="/UDPT-QLBN/public/css/doctor-dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Header -->
<div class="bg-white shadow-sm mb-4">
</div>

<!-- Main Content -->
<div class="container-fluid pb-4">
    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="doctorTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" type="button" role="tab" aria-controls="appointments" aria-selected="true">
                <i class="fas fa-calendar-check mr-2"></i>
                Lịch khám của tôi
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center" id="medicines-tab" data-bs-toggle="tab" data-bs-target="#medicines" type="button" role="tab" aria-controls="medicines" aria-selected="false">
                <i class="fas fa-pills mr-2"></i>
                Kho thuốc
            </button>
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
    <div class="modal-dialog modal-xl">
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

                <!-- ✅ UPDATED: Main content với layout cố định -->
                <div id="examModalContent" class="row d-flex" style="display: none;">
                    <!-- ✅ UPDATED: Cột trái -->
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
                                        
                                        <!-- View mode -->
                                        <div id="medicalHistoryView" class="mt-2 p-3 bg-light rounded">
                                            <div id="examPatientMedicalHistory" style="white-space: pre-wrap; min-height: 40px;">
                                                Đang tải thông tin tiền sử bệnh...
                                            </div>
                                        </div>
                                        
                                        <!-- Edit mode -->
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
                        
                        <!-- Hồ sơ khám bệnh -->
                        <div class="card flex-grow-1">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-file-medical mr-2"></i> Hồ sơ khám bệnh
                                </h6>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div class="form-group flex-grow-1">
                                    <label for="examDescription">
                                        <i class="fas fa-notes-medical mr-1"></i> Mô tả khám bệnh
                                        <small class="text-muted">(Triệu chứng, chẩn đoán, ghi chú)</small>
                                    </label>
                                    <textarea class="form-control" id="examDescription" rows="6"
                                             style="min-height: 120px; font-size: 14px;"></textarea>
                                </div>
                                
                                <div class="form-group mb-0 flex-shrink-0">
                                    <label for="prescriptionDays">
                                        <i class="fas fa-calendar-day mr-1"></i> Số ngày uống thuốc
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
                    
                    <!-- ✅ UPDATED: Cột phải với fixed layout -->
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
                                <!-- Empty state -->
                                <div class="prescription-empty" id="emptyPrescription">
                                    <i class="fas fa-prescription-bottle fa-3x mb-3 text-muted"></i>
                                    <p class="mb-1 font-weight-medium">Chưa có thuốc trong đơn</p>
                                    <p class="small text-muted">Nhấn "Thêm thuốc" để bắt đầu kê đơn</p>
                                </div>
                                
                                <!-- ✅ UPDATED: Prescription items với proper scrolling -->
                                <div id="prescriptionItems" style="display: none;">
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