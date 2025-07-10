<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><?= $data["pageTitle"] ?></h1>
            <p class="text-muted">Quản lý thông tin bệnh nhân của phòng khám</p>
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
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Danh sách bệnh nhân</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                <i class="fas fa-plus-circle mr-1"></i>
                Thêm bệnh nhân
            </button>
        </div>
        
        <div class="mt-3">
            <div class="input-group">
                <div class="input-group-prepend">
                    <!-- <span class="input-group-text bg-white border-right-0">
                        <i class="fas fa-search text-muted"></i>
                    </span> -->
                </div>
                <input type="text" id="searchInput" class="form-control" placeholder="Tìm kiếm theo số CMND/CCCD...">
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div id="loadingIndicator" class="text-center py-4 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Đang tải...</span>
            </div>
            <p class="mt-2">Đang tải dữ liệu...</p>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover" id="patientsTable">
                <thead class="bg-light">
                  <tr>
                      <th class="sortable align-middle" data-sort="id">
                          Mã BN 
                      </th>
                      <th class="sortable align-middle" data-sort="fullname">
                          Tên bệnh nhân 
                      </th>
                      <th class="sortable align-middle" data-sort="email">
                          Email 
                      </th>
                      <th class="sortable align-middle" data-sort="phone_number">
                          Số điện thoại 
                      </th>
                      <th class="align-middle">Thao tác</th>
                  </tr>
              </thead>
              <tbody>
                  <!-- Dữ liệu sẽ được load bằng JavaScript -->
              </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center p-3">
            <div class="text-muted" id="paginationInfo">
                Đang tải...
            </div>
            <nav aria-label="Page navigation">
              <ul class="pagination mb-0" id="pagination">
                  <li class="page-item disabled">
                      <a class="page-link" href="#" id="prevPage">
                          <i class="fas fa-chevron-left"></i> Trước
                      </a>
                  </li>
                  <!-- Các nút trang sẽ được thay thế động bởi JavaScript -->
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

<!-- Add Patient Modal -->
<div class="modal fade" id="addPatientModal" tabindex="-1" aria-labelledby="addPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="addPatientModalLabel">Thêm bệnh nhân mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addPatientForm" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin cơ bản</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="30%" class="font-weight-bold align-middle">Tên bệnh nhân:</td>
                                    <td>
                                        <input type="text" class="form-control" id="patientName" name="fullname" placeholder="Nhập tên bệnh nhân" required>
                                        <div class="invalid-feedback">Vui lòng nhập tên bệnh nhân</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold align-middle">CMND/CCCD:</td>
                                    <td>
                                        <input type="text" class="form-control" id="patientIdentityCard" name="identity_card" placeholder="Nhập số CMND/CCCD" required>
                                        <div class="invalid-feedback" id="identityCardFeedback">Vui lòng nhập CMND/CCCD</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold align-middle">Ngày sinh:</td>
                                    <td>
                                        <input type="date" class="form-control" id="patientDob" name="birth_date">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold align-middle">Giới tính:</td>
                                    <td>
                                        <select class="form-control" id="patientGender" name="gender">
                                            <option value="">-- Chọn giới tính --</option>
                                            <option value="Nam">Nam</option>
                                            <option value="Nữ">Nữ</option>
                                            <option value="Khác">Khác</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin liên hệ</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="30%" class="font-weight-bold align-middle">Email:</td>
                                    <td>
                                        <input type="email" class="form-control" id="patientEmail" name="email" placeholder="Nhập email">
                                        <div class="invalid-feedback">Vui lòng nhập email hợp lệ</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold align-middle">Điện thoại:</td>
                                    <td>
                                        <input type="tel" class="form-control" id="patientPhone" name="phone_number" placeholder="Nhập số điện thoại">
                                        <div class="invalid-feedback">Vui lòng nhập số điện thoại hợp lệ</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold align-middle">Địa chỉ:</td>
                                    <td>
                                        <textarea class="form-control" id="patientAddress" name="address" rows="2" placeholder="Nhập địa chỉ"></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin y tế</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="15%" class="font-weight-bold align-middle">Tiền sử bệnh:</td>
                                    <td>
                                        <textarea class="form-control" id="patientMedicalHistory" name="medical_history" rows="4" placeholder="Nhập tiền sử bệnh"></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="savePatientBtn">Lưu</button>
            </div>
        </div>
    </div>
</div>

<!-- View Patient Details Modal - Cập nhật căn chỉnh tiêu đề -->
<div class="modal fade" id="viewPatientModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">Chi tiết bệnh nhân</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="patientDetailLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Đang tải thông tin...</span>
          </div>
          <p class="mt-2 text-muted">Đang tải thông tin...</p>
        </div>
        
        <div id="patientDetailContent" class="d-none">
          <!-- View Mode -->
          <div id="viewModeContent">
            <div class="row">
              <div class="col-md-6">
                <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin cơ bản</h6>
                <table class="table table-sm table-borderless">
                  <tr>
                    <td width="30%" class="font-weight-bold align-middle">Mã BN:</td>
                    <td id="viewPatientId" class="align-middle"></td>
                  </tr>
                  <tr>
                    <td class="font-weight-bold align-middle">Họ và tên:</td>
                    <td id="viewPatientName" class="align-middle"></td>
                  </tr>
                  <tr>
                    <td class="font-weight-bold align-middle">Ngày sinh:</td>
                    <td id="viewPatientDob" class="align-middle"></td>
                  </tr>
                  <tr>
                    <td class="font-weight-bold align-middle">Giới tính:</td>
                    <td id="viewPatientGender" class="align-middle"></td>
                  </tr>
                  <tr>
                    <td class="font-weight-bold align-middle">CMND/CCCD:</td>
                    <td id="viewPatientIdentityCard" class="align-middle"></td>
                  </tr>
                </table>
              </div>
              <div class="col-md-6">
                <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin liên hệ</h6>
                <table class="table table-sm table-borderless">
                  <tr>
                    <td width="30%" class="font-weight-bold align-middle">Email:</td>
                    <td id="viewPatientEmail" class="align-middle"></td>
                  </tr>
                  <tr>
                    <td class="font-weight-bold align-middle">Điện thoại:</td>
                    <td id="viewPatientPhone" class="align-middle"></td>
                  </tr>
                  <tr>
                    <td class="font-weight-bold align-middle">Địa chỉ:</td>
                    <td id="viewPatientAddress" class="align-middle"></td>
                  </tr>
                </table>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-12">
                <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin y tế</h6>
                <table class="table table-sm table-borderless">
                  <tr>
                    <td width="30%" class="font-weight-bold align-middle">Tiền sử bệnh:</td>
                    <td id="viewPatientMedicalHistory" class="align-middle"></td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
          
          <!-- Edit Mode -->
          <div id="editModeContent" class="d-none">
            <form id="editPatientForm" class="needs-validation" novalidate>
              <input type="hidden" id="editPatientId">
              <input type="hidden" id="editPatientIdentityCard">
              
              <div class="row">
                <div class="col-md-6">
                  <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin cơ bản (Chỉ đọc)</h6>
                  <table class="table table-sm table-borderless">
                    <tr>
                      <td width="30%" class="font-weight-bold align-middle">Mã BN:</td>
                      <td id="editViewPatientId" class="align-middle"></td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold align-middle">Họ và tên:</td>
                      <td id="editViewPatientName" class="align-middle"></td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold align-middle">Ngày sinh:</td>
                      <td id="editViewPatientDob" class="align-middle"></td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold align-middle">Giới tính:</td>
                      <td id="editViewPatientGender" class="align-middle"></td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold align-middle">CMND/CCCD:</td>
                      <td id="editViewPatientIdentityCard" class="align-middle"></td>
                    </tr>
                  </table>
                </div>
                
                <div class="col-md-6">
                  <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin liên hệ (Có thể chỉnh sửa)</h6>
                  <table class="table table-sm table-borderless">
                    <tr>
                      <td width="30%" class="font-weight-bold align-middle">Email:</td>
                      <td>
                        <input type="email" class="form-control" id="editPatientEmail" name="email" placeholder="Email">
                      </td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold align-middle">Điện thoại:</td>
                      <td>
                        <input type="tel" class="form-control" id="editPatientPhone" name="phone_number" placeholder="Số điện thoại">
                      </td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold align-middle">Địa chỉ:</td>
                      <td>
                        <textarea class="form-control" id="editPatientAddress" name="address" placeholder="Địa chỉ" rows="2"></textarea>
                      </td>
                    </tr>
                  </table>
                </div>
              </div>
              
              <div class="row mt-3">
                <div class="col-12">
                  <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin y tế</h6>
                  <table class="table table-sm table-borderless">
                    <tr>
                      <td width="30%" class="font-weight-bold align-middle">Tiền sử bệnh:</td>
                      <td>
                        <textarea class="form-control" id="editPatientMedicalHistory" name="medical_history" rows="4" placeholder="Tiền sử bệnh"></textarea>
                      </td>
                    </tr>
                  </table>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <!-- View mode buttons -->
        <div id="viewModeButtons">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="button" class="btn btn-primary" id="switchToEditModeBtn">Chỉnh sửa</button>
        </div>
        
        <!-- Edit mode buttons -->
        <div id="editModeButtons" class="d-none">
          <button type="button" class="btn btn-secondary" id="cancelEditBtn">Hủy</button>
          <button type="button" class="btn btn-primary" id="savePatientChangesBtn">Lưu thay đổi</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Inside the addPatientForm
<div class="form-group">
    <label for="patientIdentityCard">CMND/CCCD</label>
    <input type="text" class="form-control" id="patientIdentityCard" name="identity_card" placeholder="Nhập số CMND/CCCD">
</div> -->

<!-- Link to JavaScript file -->
<script src="/UDPT-QLBN/public/js/patient-management-clean.js"></script>