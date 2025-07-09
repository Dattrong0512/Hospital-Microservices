/**
 * QUẢN LÝ LỊCH KHÁM - Refactored Version
 * @version 2.0
 * @updated 2025-06-26
 */

class AppointmentManager {
  constructor() {
    // Biến toàn cục
    this.appointments = [];
    this.selectedAppointment = null;

    // Cấu hình phân trang
    this.currentPage = 1;
    this.itemsPerPage = 10;
    this.totalItems = 0;
    this.totalPages = 0;

    // Cấu hình sắp xếp
    this.sortField = "appointment_id";
    this.sortDirection = "asc";

    // Flatpickr instances
    this.flatpickrInstances = {};

    // Khởi tạo
    this.init();
  }

  //=========================================================
  // KHỞI TẠO HỆ THỐNG
  //=========================================================

  init() {
    console.log("🚀 Khởi tạo Appointment Manager...");

    this.addCustomStyles();
    this.setupEventListeners();
    this.fetchAppointments();

    // Delay flatpickr để tránh conflict
    setTimeout(() => this.initializeFlatpickr(), 200);

    console.log("✓ Khởi tạo hoàn tất");
  }

  addCustomStyles() {
    const style = document.createElement("style");
    style.innerHTML = `
            .custom-badge-warning { background-color: #ffc107 !important; color: #212529 !important; }
            .custom-badge-primary { background-color: #007bff !important; color: #fff !important; }
            .custom-badge-success { background-color: #28a745 !important; color: #fff !important; }
            .custom-badge-danger { background-color: #dc3545 !important; color: #fff !important; }
            .custom-badge-secondary { background-color: #6c757d !important; color: #fff !important; }
        `;
    document.head.appendChild(style);
  }

  //=========================================================
  // FLATPICKR SETUP
  //=========================================================

  initializeFlatpickr() {
    if (typeof flatpickr !== "function") {
      console.warn("❌ Flatpickr không được tải!");
      return;
    }

    console.log("🕒 Khởi tạo flatpickr...");

    // Destroy existing instances
    document
      .querySelectorAll(
        ".flatpickr-time, #time-from, #time-to, #appointmentTime, #editTime"
      )
      .forEach((input) => input._flatpickr?.destroy());

    const timeOptions = {
      enableTime: true,
      noCalendar: true,
      dateFormat: "H:i",
      time_24hr: true,
      minuteIncrement: 5,
      defaultDate: null,
      disableMobile: true,
      allowInput: true,
      static: true,
      onClose: (selectedDates, dateStr, instance) => {
        console.log(`✅ Đã chọn giờ [${instance.element.id}]: ${dateStr}`);
      },
    };

    // Initialize time pickers
    this.initTimePicker("time-from", timeOptions);
    this.initTimePicker("time-to", timeOptions);
    this.initTimePicker("appointmentTime", {
      ...timeOptions,
      minTime: "08:00",
      maxTime: "17:00",
    });
    this.initTimePicker("editTime", {
      ...timeOptions,
      minTime: "08:00",
      maxTime: "17:00",
    });

    console.log("✅ Flatpickr khởi tạo thành công");
  }

  initTimePicker(elementId, options) {
    const element = document.getElementById(elementId);
    if (element) {
      this.flatpickrInstances[elementId] = flatpickr(element, options);
    }
  }

  //=========================================================
  // API CALLS
  //=========================================================

  async fetchAppointments() {
    console.log("🔄 [FETCH] Bắt đầu tải appointments...");
    console.log("🔄 [FETCH] Current page:", this.currentPage);
    console.log("🔄 [FETCH] Items per page:", this.itemsPerPage);

    try {
      this.showLoading();

      const searchTerm = document
        .getElementById("searchAppointment")
        ?.value?.trim();
      let url = `/UDPT-QLBN/Appointment/api_getAllAppointments?page=${this.currentPage}&limit=${this.itemsPerPage}`;

      if (searchTerm) {
        url += `&search=${encodeURIComponent(searchTerm)}`;
        console.log("🔍 [SEARCH] Search term:", searchTerm);
      }

      // ✅ THÊM: Xử lý filter parameters
      const statusFilter = document
        .getElementById("status-filter")
        ?.value?.trim();
      const dateFrom = document.getElementById("date-from")?.value?.trim();
      const dateTo = document.getElementById("date-to")?.value?.trim();

      if (statusFilter) {
        url += `&status=${encodeURIComponent(statusFilter)}`;
        console.log("🔍 [FILTER] Status:", statusFilter);
      }

      if (dateFrom) {
        url += `&start_date=${encodeURIComponent(dateFrom)}`;
        console.log("🔍 [FILTER] Start date:", dateFrom);
      }

      if (dateTo) {
        url += `&end_date=${encodeURIComponent(dateTo)}`;
        console.log("🔍 [FILTER] End date:", dateTo);
      }

      console.log("📡 [API] Calling URL:", url);

      const response = await fetch(url);
      console.log(
        "📡 [API] Response status:",
        response.status,
        response.statusText
      );
      console.log("📡 [API] Response headers:", [
        ...response.headers.entries(),
      ]);

      // Debug: Check raw response text first
      const responseText = await response.text();
      console.log("📡 [API] Raw response text:", responseText);
      console.log("📡 [API] Response text length:", responseText.length);
      console.log("📡 [API] First 500 chars:", responseText.substring(0, 500));

      if (!response.ok) throw new Error("Network response was not ok");

      // Try to parse JSON
      let data;
      try {
        data = JSON.parse(responseText);
        console.log("📡 [API] Parsed JSON successfully");
      } catch (parseError) {
        console.error("❌ [API] JSON parse error:", parseError);
        console.log("📡 [API] Invalid JSON response:", responseText);
        throw new Error("Invalid JSON response");
      }

      console.log("📡 [API] Response data:", data);
      console.log("📡 [API] Data type:", typeof data);
      console.log("📡 [API] Data keys:", Object.keys(data || {}));
      console.log("📡 [API] Appointments count:", data.data?.length || 0);

      if (data.success) {
        this.appointments = data.data || [];
        this.updatePaginationData(data.pagination);
        console.log(
          "✅ [FETCH] Success! Loaded",
          this.appointments.length,
          "appointments"
        );
        this.renderAppointments();
      } else if (data.data && Array.isArray(data.data)) {
        // Handle legacy structured format (without success flag)
        console.log("📡 [API] Legacy structured format detected");
        this.appointments = data.data;
        this.updatePaginationData({
          page: data.page || 1,
          limit: data.limit || 10,
          total: data.total || data.data.length,
          total_pages: data.total_pages || 1,
        });
        console.log(
          "✅ [FETCH] Legacy Success! Loaded",
          this.appointments.length,
          "appointments"
        );
        this.renderAppointments();
      } else {
        console.error(
          "❌ [FETCH] API returned error:",
          data.message || "Unknown error"
        );
        this.showAlert(
          "danger",
          data.message || "Không thể tải danh sách lịch khám"
        );
        this.appointments = [];
        this.renderAppointments();
      }
    } catch (error) {
      console.error("❌ [FETCH] Network error:", error);
      this.showAlert(
        "danger",
        "Không thể tải danh sách lịch khám. Vui lòng kiểm tra kết nối."
      );
      this.appointments = [];
      this.renderAppointments();
    }
  }

  async createAppointment() {
    console.log("➕ [CREATE] Bắt đầu tạo appointment...");

    if (!this.validateAppointmentForm()) {
      console.log("❌ [CREATE] Form validation failed");
      return;
    }

    const formData = this.getAppointmentFormData();
    console.log("📝 [CREATE] Form data:", formData);

    const addBtn = document.getElementById("btnAddAppointment");

    try {
      console.log("⏳ [CREATE] Setting button loading...");
      this.setButtonLoading(addBtn, true);

      console.log("📡 [CREATE] Sending request to API...");
      const response = await fetch(
        "/UDPT-QLBN/Appointment/api_createAppointment",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(formData),
        }
      );

      console.log("📡 [CREATE] Response status:", response.status);
      const data = await response.json();
      console.log("📡 [CREATE] Response data:", data);

      if (data.success) {
        console.log("✅ [CREATE] Appointment created successfully!");
        this.resetAppointmentForm();
        $("#addAppointmentModal").modal("hide");
        this.fetchAppointments();
        this.showAlert("success", "Tạo lịch khám thành công!");
      } else {
        console.error("❌ [CREATE] API error:", data.message);
        this.showAlert(
          "danger",
          data.message || "Có lỗi xảy ra khi tạo lịch khám"
        );
      }
    } catch (error) {
      console.error("❌ [CREATE] Network error:", error);
      this.showAlert("danger", "Lỗi kết nối khi tạo lịch khám");
    } finally {
      console.log("🔄 [CREATE] Resetting button...");
      this.setButtonLoading(addBtn, false);
    }
  }

  async updateAppointment() {
    const appointmentId = document.getElementById("editAppointmentId").value;
    const formData = this.getEditFormData();
    const saveBtn = document.getElementById("btnSaveEdit");

    if (!formData.date || !formData.started_time) {
      this.showAlert("danger", "Vui lòng điền đầy đủ thông tin bắt buộc!");
      return;
    }

    try {
      this.setButtonLoading(saveBtn, true);

      const response = await fetch(
        `/UDPT-QLBN/Appointment/api_updateAppointment/${appointmentId}`,
        {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(formData),
        }
      );

      const data = await response.json();

      if (data.success) {
        $("#editAppointmentModal").modal("hide");
        this.showAlert("success", "Cập nhật lịch khám thành công!");
        this.fetchAppointments();
      } else {
        this.showAlert("danger", data.message || "Có lỗi xảy ra khi cập nhật");
      }
    } catch (error) {
      console.error("Error:", error);
      this.showAlert("danger", "Lỗi kết nối khi cập nhật lịch khám");
    } finally {
      this.setButtonLoading(saveBtn, false);
    }
  }

  formatTimeForAPI(timeString) {
    if (!timeString) return "";

    // Nếu đã có giây thì giữ nguyên
    if (timeString.includes(":") && timeString.split(":").length === 3) {
      return timeString;
    }

    // Nếu chỉ có giờ:phút (HH:MM) thì thêm :00
    if (timeString.match(/^\d{1,2}:\d{2}$/)) {
      return timeString + ":00";
    }

    return timeString;
  }

  // Cập nhật hàm loadAvailableDoctors()
  async loadAvailableDoctors() {
    const department = document.getElementById("department").value;
    const appointmentDate = document.getElementById("appointmentDate").value;
    const appointmentTime = document.getElementById("appointmentTime").value;
    const doctorSelect = document.getElementById("doctorSelect");
    const noDoctorAlert = document.getElementById("noDoctorAlert");

    if (!department || !appointmentDate || !appointmentTime) {
      console.log("⚠️ Missing data for doctor search");
      doctorSelect.innerHTML =
        '<option value="">-- Vui lòng chọn khoa, ngày và giờ khám trước --</option>';
      if (noDoctorAlert) noDoctorAlert.classList.add("d-none");
      return;
    }

    console.log("🔍 Finding available doctors...");
    doctorSelect.innerHTML =
      '<option value="">Đang tải danh sách bác sĩ...</option>';

    try {
      const url = `/UDPT-QLBN/Appointment/api_getAvailableDoctors`;
      const requestBody = {
        doctor_department: department,
        appointment_date: appointmentDate,
        started_time: this.formatTimeForAPI(appointmentTime), // Tự động thêm :00
      };

      console.log("📡 API URL:", url);
      console.log("📡 Request body:", requestBody);
      console.log("🕒 Original time:", appointmentTime);
      console.log(
        "🕒 API time (with seconds):",
        this.formatTimeForAPI(appointmentTime)
      );

      const response = await fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(requestBody),
      });

      // ... rest of the method remains the same

      if (!response.ok) {
        throw new Error(`HTTP error: ${response.status}`);
      }

      const data = await response.json();
      console.log("📡 Response data:", data);

      const doctors = data.success ? data.data : [];
      this.populateDoctorSelect(doctors, doctorSelect, noDoctorAlert);
    } catch (error) {
      console.error("❌ Error loading doctors:", error);
      doctorSelect.innerHTML =
        '<option value="">-- Lỗi tải danh sách bác sĩ --</option>';
      if (noDoctorAlert) {
        noDoctorAlert.textContent = `Không thể tải danh sách bác sĩ: ${error.message}`;
        noDoctorAlert.classList.remove("d-none");
      }
    }
  }

  async searchPatients() {
    console.log(
      "🔍 [PATIENT_SEARCH] Starting patient search by identity card..."
    );

    const searchInput = document.getElementById("patientSearch");
    const resultsContainer = document.getElementById("patientResults");

    if (!searchInput || !resultsContainer) {
      console.error("❌ [PATIENT_SEARCH] Missing DOM elements");
      return;
    }

    const searchTerm = searchInput.value.trim();
    console.log("🔍 [PATIENT_SEARCH] Search identity card:", searchTerm);

    if (!searchTerm || searchTerm.length < 3) {
      console.log("⚠️ [PATIENT_SEARCH] Identity card too short (min 3 chars)");
      resultsContainer.classList.remove("show");
      return;
    }

    // Validate chỉ số
    if (!/^\d+$/.test(searchTerm)) {
      console.log(
        "⚠️ [PATIENT_SEARCH] Invalid identity card format (numbers only)"
      );
      resultsContainer.classList.add("show");
      resultsContainer.innerHTML =
        '<div class="dropdown-item text-center text-warning">CMND/CCCD chỉ được chứa số</div>';
      return;
    }

    console.log("⏳ [PATIENT_SEARCH] Showing loading...");
    resultsContainer.classList.add("show");
    resultsContainer.innerHTML = `
      <div class="dropdown-item text-center">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div> 
        Đang tìm kiếm bệnh nhân...
      </div>`;

    try {
      // URL gọi API tìm theo identity card
      const url = `/UDPT-QLBN/Patient/api_getPatientByIdentityCard/${encodeURIComponent(
        searchTerm
      )}`;
      console.log("📡 [PATIENT_SEARCH] API URL:", url);

      const response = await fetch(url);
      console.log("📡 [PATIENT_SEARCH] Response status:", response.status);

      if (!response.ok) {
        throw new Error("Lỗi tìm kiếm bệnh nhân: " + response.status);
      }

      const data = await response.json();
      console.log("📡 [PATIENT_SEARCH] Response data:", data);

      // Kiểm tra xem data có phải là mảng hay không
      const patients = [];
      if (data.success && data.data) {
        // Nếu API trả về trong format success/data
        patients.push(data.data);
        console.log("📡 [PATIENT_SEARCH] Found patient in success/data format");
      } else if (data && !Array.isArray(data) && data.identity_card) {
        // Nếu API trả về một đối tượng bệnh nhân đơn lẻ
        patients.push(data);
        console.log("📡 [PATIENT_SEARCH] Found single patient object");
      } else if (Array.isArray(data)) {
        // Nếu API trả về mảng
        patients.push(...data);
        console.log("📡 [PATIENT_SEARCH] Found patient array");
      }

      console.log("📡 [PATIENT_SEARCH] Found", patients.length, "patients");
      this.populatePatientResults(patients, resultsContainer, searchTerm);
    } catch (error) {
      console.error("❌ [PATIENT_SEARCH] Error:", error);
      resultsContainer.innerHTML = `
        <div class="dropdown-item text-danger">
          <i class="fas fa-exclamation-triangle mr-1"></i>
          Không thể tìm kiếm bệnh nhân: ${error.message}
        </div>
      `;
    }
  }

  //=========================================================
  // RENDERING METHODS
  //=========================================================

  renderAppointments() {
    console.log("🔄 Đang cập nhật danh sách lịch khám...");

    // Chuẩn hóa định dạng thời gian
    this.appointments.forEach((appointment) => {
      if (appointment.started_time) {
        appointment.started_time = this.formatTimeTo24Hour(
          appointment.started_time
        );
      }
    });

    const appointmentsTable = document
      .getElementById("appointmentsTable")
      ?.getElementsByTagName("tbody")[0];
    if (!appointmentsTable) {
      console.error("❌ Không tìm thấy bảng lịch khám");
      return;
    }

    appointmentsTable.innerHTML = "";

    if (this.appointments.length === 0) {
      this.renderEmptyState(appointmentsTable);
    } else {
      this.renderAppointmentRows(appointmentsTable);
    }

    this.updatePaginationInfo();
    this.renderPagination();

    console.log(
      `✓ Đã hiển thị ${this.appointments.length} lịch khám từ server`
    );
  }

  renderEmptyState(tableBody) {
    const emptyRow = document.createElement("tr");
    emptyRow.innerHTML = `
            <td colspan="7" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h5 class="font-weight-normal text-muted">Không có dữ liệu lịch khám</h5>
                </div>
            </td>
        `;
    tableBody.appendChild(emptyRow);
  }

  renderAppointmentRows(tableBody) {
    this.appointments.forEach((appointment) => {
      const doctorName =
        appointment.doctor_name ||
        `BS.${appointment.doctor_id.substring(0, 6)}...`;
      const patientName =
        appointment.patient_name || `BN.${appointment.patient_id}`;

      const row = document.createElement("tr");
      row.innerHTML = `
                <td class="align-middle text-center">${
                  appointment.appointment_id
                }</td>
                <td class="align-middle">${doctorName}</td>
                <td class="align-middle">${patientName}</td>
                <td class="align-middle">${appointment.date}</td>
                <td class="align-middle">${appointment.started_time}</td>
                <td class="align-middle">
                    <span class="badge ${this.getCustomBadgeClass(
                      appointment.status
                    )} px-2 py-1">
                        ${this.getStatusText(appointment.status)}
                    </span>
                </td>
                <td class="align-middle">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary view-btn" data-id="${
                          appointment.appointment_id
                        }">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary edit-btn" data-id="${
                          appointment.appointment_id
                        }">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </td>
            `;
      tableBody.appendChild(row);
    });
  }

  renderPagination() {
    const pagination = document.getElementById("pagination");
    if (!pagination) return;

    const serverTotalPages =
      this.totalPages || Math.ceil(this.totalItems / this.itemsPerPage);
    pagination.innerHTML = "";

    // Nút Trước
    this.renderPaginationButton(pagination, "prev", this.currentPage === 1);

    // Số trang
    this.renderPageNumbers(pagination, serverTotalPages);

    // Nút Sau
    this.renderPaginationButton(
      pagination,
      "next",
      this.currentPage === serverTotalPages || serverTotalPages === 0
    );
  }

  renderPaginationButton(container, type, disabled) {
    const li = document.createElement("li");
    li.className = `page-item ${disabled ? "disabled" : ""}`;

    const link = document.createElement("a");
    link.className = "page-link";
    link.href = "#";

    if (type === "prev") {
      link.innerHTML = '<i class="fas fa-chevron-left"></i> Trước';
      link.addEventListener("click", (e) => {
        e.preventDefault();
        if (this.currentPage > 1) {
          this.currentPage--;
          this.fetchAppointments();
        }
      });
    } else {
      link.innerHTML = 'Sau <i class="fas fa-chevron-right"></i>';
      link.addEventListener("click", (e) => {
        e.preventDefault();
        if (this.currentPage < this.totalPages) {
          this.currentPage++;
          this.fetchAppointments();
        }
      });
    }

    li.appendChild(link);
    container.appendChild(li);
  }

  renderPageNumbers(container, totalPages) {
    for (let i = 1; i <= totalPages; i++) {
      if (totalPages > 5) {
        if (
          i === 1 ||
          i === totalPages ||
          (i >= this.currentPage - 1 && i <= this.currentPage + 1)
        ) {
          this.renderPageNumber(container, i);
        } else if (i === this.currentPage - 2 || i === this.currentPage + 2) {
          this.renderEllipsis(container);
        }
      } else {
        this.renderPageNumber(container, i);
      }
    }
  }

  renderPageNumber(container, pageNum) {
    const li = document.createElement("li");
    li.className = `page-item ${this.currentPage === pageNum ? "active" : ""}`;

    const a = document.createElement("a");
    a.className = "page-link";
    a.href = "#";
    a.textContent = pageNum;
    a.addEventListener("click", (e) => {
      e.preventDefault();
      this.currentPage = pageNum;
      this.fetchAppointments();
    });

    li.appendChild(a);
    container.appendChild(li);
  }

  renderEllipsis(container) {
    const ellipsisLi = document.createElement("li");
    ellipsisLi.className = "page-item disabled";
    const ellipsisLink = document.createElement("a");
    ellipsisLink.className = "page-link";
    ellipsisLink.href = "#";
    ellipsisLink.textContent = "...";
    ellipsisLi.appendChild(ellipsisLink);
    container.appendChild(ellipsisLi);
  }

  //=========================================================
  // HELPER METHODS
  //=========================================================

  updatePaginationData(pagination) {
    if (pagination) {
      this.totalItems = pagination.total;
      this.currentPage = pagination.page;
      this.itemsPerPage = pagination.limit;
      this.totalPages = pagination.total_pages;
    } else {
      this.totalItems = this.appointments.length;
      this.totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
    }
  }

  updatePaginationInfo() {
    const paginationInfoElement = document.getElementById("paginationInfo");
    if (!paginationInfoElement) return;

    const startItem =
      this.totalItems === 0
        ? 0
        : (this.currentPage - 1) * this.itemsPerPage + 1;
    const endItem = Math.min(
      this.currentPage * this.itemsPerPage,
      this.totalItems
    );

    paginationInfoElement.innerHTML = `Đang xem ${startItem}-${endItem} trên tổng ${this.totalItems} lịch khám`;

    const searchTerm = document
      .getElementById("searchAppointment")
      ?.value?.trim();
    if (searchTerm) {
      const searchInfo = document.createElement("div");
      searchInfo.className = "small text-muted mt-1";
      searchInfo.textContent = `Kết quả tìm kiếm cho: "${searchTerm}"`;
      paginationInfoElement.appendChild(searchInfo);
    }
  }

  showLoading() {
    const appointmentsTable = document
      .getElementById("appointmentsTable")
      ?.getElementsByTagName("tbody")[0];
    if (!appointmentsTable) return;

    appointmentsTable.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                    <p class="mt-2 mb-0">Đang tải dữ liệu lịch khám...</p>
                </td>
            </tr>
        `;
  }

  showAlert(type, message) {
    // Tìm hoặc tạo alert container
    let alertContainer = document.getElementById("alert-container");
    if (!alertContainer) {
      alertContainer = document.createElement("div");
      alertContainer.id = "alert-container";
      alertContainer.className = "position-fixed";
      alertContainer.style.cssText =
        "top: 20px; right: 20px; z-index: 9999; max-width: 400px;";
      document.body.appendChild(alertContainer);
    }

    const alertElement = document.createElement("div");
    alertElement.className = `alert alert-${type} alert-dismissible fade show`;
    alertElement.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;

    alertContainer.appendChild(alertElement);

    setTimeout(() => {
      alertElement.classList.remove("show");
      setTimeout(() => alertElement.remove(), 500);
    }, 5000);
  }

  setButtonLoading(button, isLoading) {
    if (!button) return;

    if (isLoading) {
      button.originalText = button.innerHTML;
      button.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';
      button.disabled = true;
    } else {
      button.innerHTML = button.originalText || button.innerHTML;
      button.disabled = false;
    }
  }

  //=========================================================
  // FORM HANDLING
  //=========================================================

  getAppointmentFormData() {
    const appointmentTime = document.getElementById("appointmentTime").value;

    return {
      patient_id: parseInt(document.getElementById("selectedPatientId").value),
      doctor_id: document.getElementById("doctorSelect").value,
      status: "Chưa khám",
      description: document.getElementById("description").value || "",
      date: document.getElementById("appointmentDate").value,
      started_time: this.formatTimeForAPI(appointmentTime), // Tự động thêm :00
    };
  }

  getEditFormData() {
    const editTime = document.getElementById("editTime").value;

    return {
      date: document.getElementById("editDate").value,
      started_time: this.formatTimeForAPI(editTime), // Tự động thêm :00
      status: document.getElementById("editStatus").value,
      description: document.getElementById("editDescription").value,
    };
  }

  validateAppointmentForm() {
    console.log("✅ [VALIDATION] Starting form validation...");

    const fields = [
      { id: "department", name: "Khoa" },
      { id: "appointmentDate", name: "Ngày khám" },
      { id: "appointmentTime", name: "Giờ khám" },
      { id: "doctorSelect", name: "Bác sĩ" },
      {
        id: "selectedPatientId",
        name: "Bệnh nhân",
        checkElement: "patientSearch",
      },
    ];

    let isValid = true;

    fields.forEach((field) => {
      const element = document.getElementById(field.id);
      const checkElement = document.getElementById(
        field.checkElement || field.id
      );
      const value = element?.value?.trim();

      console.log(`✅ [VALIDATION] Checking ${field.name}:`, value);

      if (!value) {
        console.log(`❌ [VALIDATION] ${field.name} is empty`);
        checkElement?.classList.add("is-invalid");
        isValid = false;
      } else {
        console.log(`✅ [VALIDATION] ${field.name} is valid`);
        checkElement?.classList.remove("is-invalid");
      }
    });

    // Validate identity card format (nếu có nhập nhưng không chọn bệnh nhân)
    const patientSearch = document.getElementById("patientSearch");
    const identityCard = patientSearch?.value?.trim();
    const selectedPatientId =
      document.getElementById("selectedPatientId")?.value;

    if (identityCard && !selectedPatientId) {
      console.log("❌ [VALIDATION] Patient not selected");
      patientSearch.classList.add("is-invalid");
      isValid = false;
      this.showAlert("danger", "Vui lòng chọn bệnh nhân từ kết quả tìm kiếm!");
    }

    if (identityCard && !/^\d+$/.test(identityCard)) {
      console.log("❌ [VALIDATION] Invalid identity card format");
      patientSearch.classList.add("is-invalid");
      isValid = false;
      this.showAlert("danger", "Số CMND/CCCD chỉ được chứa số!");
    }

    if (!isValid) {
      console.log("❌ [VALIDATION] Form validation failed");
      this.showAlert("danger", "Vui lòng điền đầy đủ thông tin bắt buộc!");
    } else {
      console.log("✅ [VALIDATION] Form validation passed");
    }

    return isValid;
  }

  resetAppointmentForm() {
    document.getElementById("addAppointmentForm").reset();
    document.getElementById("selectedPatientInfo").classList.add("d-none");
  }

  //=========================================================
  // UTILITY METHODS
  //=========================================================

  formatTimeTo24Hour(timeStr) {
    if (!timeStr) return "";
    if (timeStr.indexOf("CH") === -1 && timeStr.indexOf("SA") === -1)
      return timeStr;

    try {
      let [time, period] = timeStr.split(" ");
      let [hours, minutes] = time.split(":").map(Number);

      if (period === "CH" && hours < 12) hours += 12;
      if (period === "SA" && hours === 12) hours = 0;

      return `${hours.toString().padStart(2, "0")}:${minutes
        .toString()
        .padStart(2, "0")}`;
    } catch (error) {
      console.error("❌ Lỗi khi chuyển đổi thời gian:", error);
      return timeStr;
    }
  }

  getStatusText(status) {
    const statusMap = {
      "Chưa khám": "Chưa khám",
      "Đã khám": "Đã khám",
      "Đã hủy": "Đã hủy",
    };
    return statusMap[status] || status;
  }

  getCustomBadgeClass(status) {
    const badgeMap = {
      "Chưa khám": "custom-badge-warning",
      "Đã khám": "custom-badge-success",
      "Đã hủy": "custom-badge-danger",
    };
    return badgeMap[status] || "custom-badge-secondary";
  }

  //=========================================================
  // EVENT LISTENERS
  //=========================================================

  setupEventListeners() {
    console.log("🔄 Thiết lập sự kiện...");

    // ✅ THÊM: Event listener cho nút "Thêm lịch khám" để mở modal
    $(document).on(
      "click",
      '[data-target="#addAppointmentModal"]',
      function (e) {
        e.preventDefault();
        console.log("🔄 Mở modal thêm lịch khám...");
        $("#addAppointmentModal").modal("show");
      }
    );

    // Modal events
    $("#addAppointmentModal, #editAppointmentModal").on(
      "shown.bs.modal",
      () => {
        console.log("🔄 Modal đã mở, khởi tạo flatpickr...");
        setTimeout(() => this.initializeFlatpickr(), 100);
      }
    );

    // ✅ THÊM: Event listeners cho nút đóng modal
    $(".modal .close, .modal [data-dismiss='modal']").on("click", function () {
      console.log("🔄 Đóng modal...");
      $(this).closest(".modal").modal("hide");
    });

    // ✅ THÊM: Reset form khi modal đóng
    $("#addAppointmentModal").on("hidden.bs.modal", () => {
      console.log("🔄 Modal đã đóng, reset form...");
      this.resetAppointmentForm();
    });

    // Search with debounce
    const searchInput = document.getElementById("searchAppointment");
    if (searchInput) {
      let searchTimeout;
      searchInput.addEventListener("input", () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          this.currentPage = 1;
          this.fetchAppointments();
        }, 500);
      });
    }

    // Filter buttons
    this.setupFilterButtons();

    // Appointment form events
    this.setupAppointmentFormEvents();

    // Table events
    this.setupTableEvents();

    // Edit form events
    this.setupEditFormEvents();

    console.log("✓ Đã thiết lập sự kiện");
  }

  setupFilterButtons() {
    const applyFilterBtn = document.getElementById("apply-filter");
    const resetFilterBtn = document.getElementById("reset-filter");
    const clearFiltersBtn = document.getElementById("clear-filters");

    applyFilterBtn?.addEventListener("click", () => {
      this.currentPage = 1;
      this.filterAndRenderAppointments();
    });

    resetFilterBtn?.addEventListener("click", () => this.resetFilters());
    clearFiltersBtn?.addEventListener("click", () => {
      this.resetFilters();
      this.filterAndRenderAppointments();
    });
  }

  setupAppointmentFormEvents() {
    // Form field changes
    ["department", "appointmentDate", "appointmentTime"].forEach((id) => {
      document
        .getElementById(id)
        ?.addEventListener("change", () => this.loadAvailableDoctors());
    });

    // Patient search by identity card
    const patientSearchInput = document.getElementById("patientSearch");
    if (patientSearchInput) {
      // Đổi placeholder
      patientSearchInput.placeholder = "Nhập CMND/CCCD để tìm bệnh nhân";

      let searchTimeout;
      patientSearchInput.addEventListener("input", () => {
        console.log("🔍 [PATIENT_SEARCH] Input event triggered");

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          this.searchPatients();
        }, 300); // Debounce 300ms
      });

      patientSearchInput.addEventListener("focus", function () {
        console.log(
          "🔍 [PATIENT_SEARCH] Focus event - current value:",
          this.value
        );
        if (this.value.trim().length >= 3) {
          document.getElementById("patientResults")?.classList.add("show");
        }
      });
    }

    // Hide dropdown on outside click
    document.addEventListener("click", (e) => {
      const patientResults = document.getElementById("patientResults");
      if (
        patientResults &&
        !e.target.closest("#patientSearch") &&
        !e.target.closest("#patientResults")
      ) {
        patientResults.classList.remove("show");
      }
    });

    // Create appointment button
    document
      .getElementById("btnAddAppointment")
      ?.addEventListener("click", () => this.createAppointment());
  }

  setupTableEvents() {
    const appointmentsTable = document.getElementById("appointmentsTable");
    if (!appointmentsTable) return;

    appointmentsTable.addEventListener("click", (e) => {
      const viewBtn = e.target.closest(".view-btn");
      const editBtn = e.target.closest(".edit-btn");

      if (viewBtn) {
        const appointmentId = viewBtn.dataset.id;
        $("#viewAppointmentModal").modal("show");
        this.viewAppointmentDetails(appointmentId);
      }

      if (editBtn) {
        const appointmentId = editBtn.dataset.id;
        const appointment = this.appointments.find(
          (a) => a.appointment_id === appointmentId
        );
        if (appointment) {
          this.populateEditForm(appointment);
          $("#editAppointmentModal").modal("show");
        }
      }
    });
  }

  setupEditFormEvents() {
    document
      .getElementById("btnSaveEdit")
      ?.addEventListener("click", () => this.updateAppointment());

    const btnEditAppointment = document.getElementById("btnEditAppointment");
    if (btnEditAppointment) {
      btnEditAppointment.addEventListener("click", () => {
        $("#viewAppointmentModal").modal("hide");
        if (this.selectedAppointment) {
          this.populateEditForm(this.selectedAppointment);
          setTimeout(() => $("#editAppointmentModal").modal("show"), 500);
        }
      });
    }
  }

  //=========================================================
  // DATA POPULATION METHODS
  //=========================================================

  populateDoctorSelect(doctors, doctorSelect, noDoctorAlert) {
    let availableDoctors = [];
    if (Array.isArray(doctors)) {
      availableDoctors = doctors;
    } else if (doctors.data && Array.isArray(doctors.data)) {
      availableDoctors = doctors.data;
    }

    if (availableDoctors.length === 0) {
      noDoctorAlert.classList.remove("d-none");
      doctorSelect.innerHTML =
        '<option value="">Không có bác sĩ khả dụng</option>';
      doctorSelect.disabled = true;
    } else {
      noDoctorAlert.classList.add("d-none");
      doctorSelect.innerHTML = '<option value="">Chọn bác sĩ</option>';
      availableDoctors.forEach((doctor) => {
        const option = document.createElement("option");
        option.value = doctor.doctor_id;
        option.textContent = `${doctor.fullname} - ${doctor.email} - ${doctor.phone_number}`;
        doctorSelect.appendChild(option);
      });
      doctorSelect.disabled = false;
    }
  }

  populatePatientResults(patients, resultsContainer, searchTerm = "") {
    console.log(
      "🔄 [PATIENT_SEARCH] Populating results:",
      patients.length,
      "patients"
    );

    resultsContainer.innerHTML = "";

    if (patients.length === 0) {
      console.log("⚠️ [PATIENT_SEARCH] No patients found");
      resultsContainer.innerHTML = `
        <div class="dropdown-item text-center text-muted">
          <i class="fas fa-search mr-2"></i>
          Không tìm thấy bệnh nhân với CMND/CCCD: "${searchTerm}"
        </div>`;
      return;
    }

    console.log(
      "✅ [PATIENT_SEARCH] Rendering",
      patients.length,
      "patient results"
    );

    patients.forEach((patient, index) => {
      const patientId = patient.id || patient.patient_id;
      const patientName = patient.fullname || patient.name;
      const identityCard = patient.identity_card;
      const phone = patient.phone_number || patient.phone;

      console.log(`👤 [PATIENT_SEARCH] Patient ${index + 1}:`, {
        id: patientId,
        name: patientName,
        identity: identityCard,
        phone: phone,
      });

      const item = document.createElement("div");
      item.className = "dropdown-item patient-item";
      item.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <strong>${patientName}</strong>
            <div class="small text-muted">
              <i class="fas fa-id-card mr-1"></i>${identityCard} 
              <i class="fas fa-phone ml-2 mr-1"></i>${phone || "N/A"}
            </div>
          </div>
          <button class="btn btn-sm btn-outline-primary select-patient-btn" 
                  data-id="${patientId}">
            <i class="fas fa-check mr-1"></i>Chọn
          </button>
        </div>
      `;

      item
        .querySelector(".select-patient-btn")
        .addEventListener("click", (e) => {
          e.preventDefault(); // Ngăn chặn hành vi mặc định
          e.stopPropagation(); // Ngăn chặn lan truyền sự kiện
          console.log("👤 [PATIENT_SEARCH] Patient selected:", patient);
          this.selectPatient(patient);
          return false; // Ngăn chặn thêm
        });

      resultsContainer.appendChild(item);
    });
  }

  selectPatient(patient) {
    console.log("👤 [PATIENT_SELECT] Selecting patient:", patient);

    const patientSearch = document.getElementById("patientSearch");
    const patientResults = document.getElementById("patientResults");
    const selectedPatientInfo = document.getElementById("selectedPatientInfo");
    const selectedPatientId = document.getElementById("selectedPatientId");

    if (
      !patientSearch ||
      !patientResults ||
      !selectedPatientInfo ||
      !selectedPatientId
    ) {
      console.error("❌ [PATIENT_SELECT] Missing DOM elements");
      return;
    }

    // Hiển thị CMND/CCCD thay vì tên
    patientSearch.value = patient.identity_card;
    selectedPatientId.value = patient.id || patient.patient_id;
    patientResults.classList.remove("show");

    console.log(
      "👤 [PATIENT_SELECT] Updated form with patient ID:",
      selectedPatientId.value
    );

    // Populate patient info display
    document.getElementById("patientName").textContent =
      patient.fullname || patient.name;
    document.getElementById("patientDob").textContent =
      patient.birth_date || "Không có thông tin";
    document.getElementById("patientIdCard").textContent =
      patient.identity_card;
    document.getElementById("patientPhone").textContent =
      patient.phone_number || patient.phone || "Không có thông tin";

    selectedPatientInfo.classList.remove("d-none");

    console.log("✅ [PATIENT_SELECT] Patient selection completed");
  }

  populateEditForm(appointment) {
    document.getElementById("editAppointmentId").value =
      appointment.appointment_id;

    // Format date from DD-MM-YYYY to YYYY-MM-DD
    const dateParts = appointment.date.split("-");
    const formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;

    document.getElementById("editDate").value = formattedDate;
    document.getElementById("editTime").value = appointment.started_time;
    document.getElementById("editStatus").value = appointment.status;
    document.getElementById("editDescription").value =
      appointment.description || "";

    // Update flatpickr if exists
    const timeInput = document.getElementById("editTime");
    if (timeInput._flatpickr) {
      setTimeout(
        () => timeInput._flatpickr.setDate(appointment.started_time),
        100
      );
    }
  }

  //=========================================================
  // DETAIL VIEW METHODS
  //=========================================================

  async viewAppointmentDetails(id) {
    const appointmentDetails = document.getElementById("appointmentDetails");
    if (!appointmentDetails) return;

    console.log("🔍 [VIEW] Loading appointment details for ID:", id);

    appointmentDetails.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Đang tải...</span>
                </div>
                <p class="mt-2 mb-0">Đang tải thông tin chi tiết...</p>
            </div>
        `;

    try {
      const url = `/UDPT-QLBN/Appointment/api_getAppointment/${id}`;
      console.log("📡 [VIEW] API URL:", url);

      const response = await fetch(url);
      console.log("📡 [VIEW] Response status:", response.status);

      if (!response.ok) {
        throw new Error(`HTTP Error: ${response.status}`);
      }

      const data = await response.json();
      console.log("📡 [VIEW] Response data:", data);

      if (data.success && data.data) {
        this.selectedAppointment = data.data;
        this.renderAppointmentDetails(data.data);
      } else {
        throw new Error(data.message || "Không thể tải thông tin lịch khám");
      }
    } catch (error) {
      console.error("❌ [VIEW] Error:", error);
      appointmentDetails.innerHTML = `
                <div class="alert alert-danger">
                    <h6>Lỗi tải dữ liệu</h6>
                    <p class="mb-0">${error.message}</p>
                </div>
            `;
    }
  }

  renderAppointmentDetails(appointment) {
    const appointmentDetails = document.getElementById("appointmentDetails");
    if (!appointmentDetails) return;

    console.log("🔄 [VIEW] Rendering appointment details:", appointment);

    // Fallback values nếu không có thông tin
    const doctorInfo = {
      name:
        appointment.doctor_name ||
        `BS.${appointment.doctor_id.substring(0, 8)}`,
      email: appointment.doctor_email || "Không có thông tin",
      phone: appointment.doctor_phone || "Không có thông tin",
      department: appointment.doctor_department || "Không có thông tin",
    };

    const patientInfo = {
      name: appointment.patient_name || `BN.${appointment.patient_id}`,
      birth_date: appointment.patient_birth_date || "Không có thông tin",
      identity_card: appointment.patient_identity || "Không có thông tin",
      phone: appointment.patient_phone || "Không có thông tin",
    };

    appointmentDetails.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 class="font-weight-bold text-primary mb-3">
                            <i class="fas fa-user-md mr-2"></i>Thông tin bác sĩ
                        </h6>
                        <div class="card border-0 bg-light">
                            <div class="card-body p-3">
                                <p class="mb-2"><strong>Tên bác sĩ:</strong> ${
                                  doctorInfo.name
                                }</p>
                                <p class="mb-2"><strong>Khoa:</strong> ${
                                  doctorInfo.department
                                }</p>
                                <p class="mb-2"><strong>Email:</strong> ${
                                  doctorInfo.email
                                }</p>
                                <p class="mb-0"><strong>Số điện thoại:</strong> ${
                                  doctorInfo.phone
                                }</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 class="font-weight-bold text-success mb-3">
                            <i class="fas fa-user mr-2"></i>Thông tin bệnh nhân
                        </h6>
                        <div class="card border-0 bg-light">
                            <div class="card-body p-3">
                                <p class="mb-2"><strong>Tên bệnh nhân:</strong> ${
                                  patientInfo.name
                                }</p>
                                <p class="mb-2"><strong>Ngày sinh:</strong> ${
                                  patientInfo.birth_date
                                }</p>
                                <p class="mb-2"><strong>CMND/CCCD:</strong> ${
                                  patientInfo.identity_card
                                }</p>
                                <p class="mb-0"><strong>Số điện thoại:</strong> ${
                                  patientInfo.phone
                                }</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <h6 class="font-weight-bold text-info mb-3">
                    <i class="fas fa-calendar-check mr-2"></i>Thông tin lịch khám
                </h6>
                <div class="card border-0 bg-light">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Mã lịch khám:</strong> 
                                    <span class="badge badge-primary">${
                                      appointment.appointment_id
                                    }</span>
                                </p>
                                <p class="mb-2"><strong>Ngày khám:</strong> 
                                    <span class="text-primary">${
                                      appointment.date
                                    }</span>
                                </p>
                                <p class="mb-2"><strong>Giờ khám:</strong> 
                                    <span class="text-primary">${
                                      appointment.started_time
                                    }</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Trạng thái:</strong> 
                                    <span class="badge ${this.getCustomBadgeClass(
                                      appointment.status
                                    )} px-2 py-1">
                                        ${this.getStatusText(
                                          appointment.status
                                        )}
                                    </span>
                                </p>
                                <p class="mb-0"><strong>Mô tả:</strong> 
                                    <span class="text-muted">${
                                      appointment.description ||
                                      "Không có mô tả"
                                    }</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

    console.log("✅ [VIEW] Appointment details rendered successfully");
  }

  //=========================================================
  // FILTER METHODS
  //=========================================================

  resetFilters() {
    console.log("🔄 Đang reset tất cả bộ lọc...");

    [
      "department-filter",
      "status-filter",
      "date-from",
      "date-to",
      "searchAppointment",
    ].forEach((id) => {
      const element = document.getElementById(id);
      if (element) element.value = "";
    });

    // Reset flatpickr time inputs
    ["time-from", "time-to"].forEach((id) => {
      const input = document.getElementById(id);
      if (input?._flatpickr) {
        input._flatpickr.clear();
      } else if (input) {
        input.value = "";
      }
    });

    console.log("✓ Đã reset tất cả bộ lọc");
  }

  filterAndRenderAppointments() {
    this.currentPage = 1;
    this.fetchAppointments();

    // Close filter modal
    if (typeof bootstrap !== "undefined") {
      const filterModal = bootstrap.Modal.getInstance(
        document.getElementById("filterModal")
      );
      filterModal?.hide();
    } else if (typeof $ !== "undefined") {
      $("#filterModal").modal("hide");
    }
  }
}

// Khởi tạo khi DOM ready
document.addEventListener("DOMContentLoaded", function () {
  window.appointmentManager = new AppointmentManager();
});
