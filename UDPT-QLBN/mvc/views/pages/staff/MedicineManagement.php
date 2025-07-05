<?php if (!isset($data['flatpickr_loaded'])): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<?php endif; ?>

<style>
    .form-layout .form-row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -10px;
        margin-left: -10px;
    }
    
    .form-layout .form-group {
        margin-bottom: 1rem;
        margin-left: 30px; /* Thêm margin trái */
    }
    
    .form-layout label {
        font-size: 14px;
        margin-bottom: 0.5rem;
        font-weight: bold;
    }
    
    .form-layout .col {
        flex-basis: 0;
        flex-grow: 1;
        max-width: 100%;
        padding-right: 10px;
        padding-left: 10px;
    }
    
    /* Kích thước cụ thể cho từng loại input */
    .input-date {
        max-width: 100%;
    }
    
    .input-number {
        max-width: 120px;
    }
    
    .input-unit {
        max-width: 120px;
    }
    
    .input-price {
        max-width: 150px;
    }
    
    /* Điều chỉnh input cho tên thuốc */
    #medicineName {
        max-width: 400px; /* Giảm độ rộng cho trường tên thuốc */
        margin-left: -10px
    }
    
    /* Responsive */
    @media (max-width: 767.98px) {
        .input-date, .input-number, .input-unit, .input-price {
            max-width: 100%;
        }
        #medicineName {
            max-width: 100%; /* Toàn chiều rộng trên màn hình nhỏ */
        }
    }
    .badge-expiry-safe {
        background-color: #28a745; /* Màu nền xanh lá */
        color: white; /* Màu chữ trắng */
    }

    .badge-expiry-warning {
        background-color: #ffc107; /* Màu nền vàng */
        color: #212529; /* Màu chữ đen */
    }

    .badge-expiry-danger {
        background-color: #dc3545; /* Màu nền đỏ */
        color: white; /* Màu chữ trắng */
    }
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><?= $data["pageTitle"] ?></h1>
            <p class="text-muted">Quản lý kho thuốc và dược phẩm</p>
        </div>
    </div>
</div>

<!-- Thông báo -->
<div id="alert-container"></div>

<div class="card">
    <div class="card-body p-0">
        <!-- Tìm kiếm và bộ lọc -->
        <div class="p-3 border-bottom">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <!-- <span class="input-group-text bg-white border-right-0">
                                <i class="fas fa-search text-muted"></i>
                            </span> -->
                        </div>
                        <input type="text" id="searchMedicine" class="form-control border-left-0" 
                            placeholder="Tìm kiếm theo tên thuốc...">
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" class="btn btn-outline-secondary" data-toggle="collapse" data-target="#filterCollapse" aria-expanded="false">
                        <i class="fas fa-filter mr-1"></i> Bộ lọc
                    </button>
                    <!-- <button type="button" class="btn btn-warning ml-2" id="btn-near-expiry">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Thuốc sắp hết hạn
                    </button> -->
                    <button type="button" class="btn btn-primary ml-2" data-toggle="modal" data-target="#addMedicineModal">
                        <i class="fas fa-plus-circle mr-1"></i> Thêm thuốc mới
                    </button>
                </div>
            </div>

            <!-- Bộ lọc mở rộng -->
            <div class="collapse" id="filterCollapse">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="expiry-filter">Hạn sử dụng</label>
                        <select class="form-control" id="expiry-filter">
                            <option value="">Tất cả</option>
                            <option value="near">Sắp hết hạn (7 ngày)</option>
                            <option value="expired">Đã hết hạn</option>
                            <option value="valid">Còn hạn</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="amount-filter">Số lượng</label>
                        <select class="form-control" id="amount-filter">
                            <option value="">Tất cả</option>
                            <option value="low">Sắp hết (< 10)</option>
                            <option value="out">Đã hết</option>
                            <option value="available">Còn hàng</option>
                        </select>
                    </div>
                </div>
                <div class="text-right">
                    <button type="button" class="btn btn-secondary" id="reset-filter">Đặt lại</button>
                    <button type="button" class="btn btn-primary ml-2" id="apply-filter">Áp dụng</button>
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
            <table class="table table-hover" id="medicinesTable">
                <thead class="bg-light">
                    <tr>
                        <th class="sortable align-middle" data-sort="medicine_id" width="7%">Mã thuốc <i class="fas fa-sort text-muted ml-1"></i></th>
                        <th class="sortable align-middle" data-sort="name">Tên thuốc <i class="fas fa-sort text-muted ml-1"></i></th>
                        <th class="sortable align-middle" data-sort="MFG" width="12%">Ngày SX <i class="fas fa-sort text-muted ml-1"></i></th>
                        <th class="sortable align-middle" data-sort="EXP" width="12%">Hạn SD <i class="fas fa-sort text-muted ml-1"></i></th>
                        <th class="sortable align-middle" data-sort="amount" width="10%">Số lượng <i class="fas fa-sort text-muted ml-1"></i></th>
                        <th class="sortable align-middle" data-sort="unit" width="8%">Đơn vị <i class="fas fa-sort text-muted ml-1"></i></th>
                        <th class="sortable align-middle" data-sort="price" width="12%">Giá (VNĐ) <i class="fas fa-sort text-muted ml-1"></i></th>
                        <th class="align-middle" width="12%">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dữ liệu sẽ được load bằng JavaScript -->
                    <tr class="text-center" id="no-results-row" style="display: none;">
                        <td colspan="8" class="py-4">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-filter fa-3x text-muted mb-3"></i>
                                <h5 class="font-weight-normal text-muted">Không tìm thấy thuốc nào phù hợp với bộ lọc</h5>
                                <button class="btn btn-outline-secondary mt-3" id="clear-filters">
                                    <i class="fas fa-times mr-1"></i> Xóa bộ lọc
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

<!-- Modal Thêm Thuốc -->
<div class="modal fade" id="addMedicineModal" tabindex="-1" aria-labelledby="addMedicineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="addMedicineModalLabel">Thêm thuốc mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addMedicineForm" class="form-layout">
                    <!-- Tên thuốc -->
                    <div class="form-group">
                        <label for="medicineName">Tên thuốc: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="medicineName" required>
                        <div class="invalid-feedback">Vui lòng nhập tên thuốc</div>
                    </div>

                    <!-- Ngày sản xuất và Hạn sử dụng -->
                    <div class="form-row mb-3">
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label for="medicineMFG">Ngày sản xuất: <span class="text-danger">*</span></label>
                                <input type="date" class="form-control input-date" id="medicineMFG" required>
                                <div class="invalid-feedback">Vui lòng chọn ngày sản xuất</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label for="medicineEXP">Hạn sử dụng: <span class="text-danger">*</span></label>
                                <input type="date" class="form-control input-date" id="medicineEXP" required>
                                <div class="invalid-feedback">Vui lòng chọn hạn sử dụng</div>
                            </div>
                        </div>
                    </div>

                    <!-- Số lượng, Đơn vị và Đơn giá -->
                    <div class="form-row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="medicineAmount">Số lượng: <span class="text-danger">*</span></label>
                                <input type="number" class="form-control input-number" id="medicineAmount" min="0" required>
                                <div class="invalid-feedback">Vui lòng nhập số lượng</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="medicineUnit">Đơn vị: <span class="text-danger">*</span></label>
                                <input type="text" class="form-control input-unit" id="medicineUnit" required>
                                <div class="invalid-feedback">Vui lòng nhập đơn vị</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="medicinePrice">Đơn giá (VNĐ): <span class="text-danger">*</span></label>
                                <input type="number" class="form-control input-price" id="medicinePrice" min="0" required>
                                <div class="invalid-feedback">Vui lòng nhập giá thuốc</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="btnAddMedicine">Thêm thuốc</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chỉnh sửa thuốc -->
<div class="modal fade" id="editMedicineModal" tabindex="-1" aria-labelledby="editMedicineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="editMedicineModalLabel">Chỉnh sửa thuốc</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editMedicineForm" class="form-layout">
                    <input type="hidden" id="editMedicineId">
                    
                    <!-- Tên thuốc -->
                    <div class="form-group">
                        <label for="editMedicineName">Tên thuốc: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editMedicineName" readonly>
                    </div>

                    <!-- Ngày sản xuất và Hạn sử dụng -->
                    <div class="form-row mb-3">
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label for="editMedicineMFG">Ngày sản xuất: <span class="text-danger">*</span></label>
                                <input type="date" class="form-control input-date" id="editMedicineMFG" required>
                                <div class="invalid-feedback">Vui lòng chọn ngày sản xuất</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label for="editMedicineEXP">Hạn sử dụng: <span class="text-danger">*</span></label>
                                <input type="date" class="form-control input-date" id="editMedicineEXP" required>
                                <div class="invalid-feedback">Vui lòng chọn hạn sử dụng</div>
                            </div>
                        </div>
                    </div>

                    <!-- Số lượng, Đơn vị và Đơn giá -->
                    <div class="form-row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editMedicineAmount">Số lượng: <span class="text-danger">*</span></label>
                                <input type="number" class="form-control input-number" id="editMedicineAmount" min="0" required>
                                <div class="invalid-feedback">Vui lòng nhập số lượng</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editMedicineUnit">Đơn vị: <span class="text-danger">*</span></label>
                                <input type="text" class="form-control input-unit" id="editMedicineUnit" required>
                                <div class="invalid-feedback">Vui lòng nhập đơn vị</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editMedicinePrice">Đơn giá (VNĐ): <span class="text-danger">*</span></label>
                                <input type="number" class="form-control input-price" id="editMedicinePrice" min="0" required>
                                <div class="invalid-feedback">Vui lòng nhập giá thuốc</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="btnCancelEdit">Hủy</button>
                <button type="button" class="btn btn-primary" id="btnSaveEdit">Lưu thay đổi</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xác nhận xóa thuốc -->
<div class="modal fade" id="deleteMedicineModal" tabindex="-1" aria-labelledby="deleteMedicineModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteMedicineModalLabel">Xác nhận xóa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa thuốc <span id="deleteMedicineName" class="font-weight-bold"></span>? Hành động này không thể hoàn tác.</p>
                <input type="hidden" id="deleteMedicineId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="btnCancelDelete">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Xóa</button>
            </div>
        </div>
    </div>
</div>

<script src="/UDPT-QLBN/public/js/medicine-management.js"></script>
