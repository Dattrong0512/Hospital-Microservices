document.addEventListener("DOMContentLoaded", function () {
  // Config
  const config = {
    apiBaseUrl: "/UDPT-QLBN/Appointment",
    currentPage: 1,
    perPage: 10,
    doctorId: getCurrentDoctorId(), // Lấy ID của bác sĩ hiện tại từ session
    filters: {
      status: "",
      startDate: "",
      endDate: "",
    },
  };

  const medicineConfig = {
    apiBaseUrl: "/UDPT-QLBN/Medicine",
    currentPage: 1,
    perPage: 10,
    searchTerm: "",
    viewMode: "all", // 'all' or 'near-expiry'
  };

  // DOM Elements - Appointments
  const statusFilterSelect = document.getElementById("statusFilter");
  const startDateFilterInput = document.getElementById("startDateFilter");
  const endDateFilterInput = document.getElementById("endDateFilter");
  const resetFilterBtn = document.getElementById("resetFilterBtn");
  const applyFilterBtn = document.getElementById("applyFilterBtn");
  const appointmentsTableBody = document.getElementById(
    "appointmentsTableBody"
  );
  const appointmentsLoading = document.getElementById("loadingIndicator");
  const appointmentsEmpty = document.getElementById("appointmentsEmpty");
  const appointmentsPagination = document.getElementById(
    "appointmentsPagination"
  );

  const searchMedicineInput = document.getElementById("searchMedicine");
  const searchMedicineBtn = document.getElementById("searchMedicineBtn");
  const showNearExpiryBtn = document.getElementById("showNearExpiryBtn");
  const medicinesTableBody = document.getElementById("medicinesTableBody");
  const medicinesLoading = document.getElementById("medicinesLoading");
  const medicinesEmpty = document.getElementById("medicinesEmpty");
  const medicinesPagination = document.getElementById("medicinesPagination");

  // Các phần tử DOM cho modal
  const examModal = document.getElementById("examModal");
  const viewExamModal = document.getElementById("viewExamModal");
  const addMedicineBtn = document.getElementById("addMedicineBtn");
  const saveExamBtn = document.getElementById("saveExamBtn");

  // Lấy ID bác sĩ hiện tại từ session
  function getCurrentDoctorId() {
    // Lấy từ window.userData được set từ PHP
    if (
      window.userData &&
      window.userData.userId &&
      window.userData.userId !== "null"
    ) {
      console.log("Found doctor ID:", window.userData.userId);
      return window.userData.userId;
    }

    // Fallback
    console.warn("Doctor ID not found, using default");
    return "1";
  }

  // Hàm trợ giúp lấy cookie
  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
  }

  // Khởi tạo bộ lọc
  function initFilters() {
    // Set giá trị mặc định cho ngày
    const today = new Date();
    const sevenDaysAgo = new Date();
    sevenDaysAgo.setDate(today.getDate() - 7);

    startDateFilterInput.valueAsDate = sevenDaysAgo;
    endDateFilterInput.valueAsDate = today;

    // Đảm bảo ngày bắt đầu <= ngày kết thúc
    startDateFilterInput.addEventListener("change", function () {
      if (startDateFilterInput.value > endDateFilterInput.value) {
        endDateFilterInput.value = startDateFilterInput.value;
      }
    });

    endDateFilterInput.addEventListener("change", function () {
      if (endDateFilterInput.value < startDateFilterInput.value) {
        startDateFilterInput.value = endDateFilterInput.value;
      }
    });

    // Xử lý nút đặt lại bộ lọc
    resetFilterBtn.addEventListener("click", function () {
      statusFilterSelect.value = "";
      startDateFilterInput.valueAsDate = sevenDaysAgo;
      endDateFilterInput.valueAsDate = today;
      
      // ✅ THÊM: Reset filters và reload
      config.filters.status = "";
      config.filters.startDate = startDateFilterInput.value;
      config.filters.endDate = endDateFilterInput.value;
      config.currentPage = 1;
      loadAppointments();
    });

    // Xử lý nút áp dụng bộ lọc
    applyFilterBtn.addEventListener("click", function () {
      config.filters.status = statusFilterSelect.value;
      config.filters.startDate = startDateFilterInput.value;
      config.filters.endDate = endDateFilterInput.value;
      config.currentPage = 1;
      loadAppointments();
    });
  }

  // Load lịch khám theo trang và bộ lọc
  async function loadAppointments() {
    console.log("=== DEBUG loadAppointments START ===");
    console.log("Doctor ID being used:", config.doctorId);
    console.log("Config object:", config);
    showLoading();
    try {
      const url = buildApiUrl();
      console.log("🌐 Full URL being called:", url);
      console.log("🌐 URL breakdown:");
      console.log("  - Base URL:", config.apiBaseUrl);
      console.log("  - Doctor ID:", config.doctorId);
      console.log("  - Page:", config.currentPage);
      console.log("  - Limit:", config.perPage);
      console.log("  - Filters:", config.filters);

      // Thêm debug cho fetch request
      console.log("📡 Making fetch request...");

      const response = await fetch(url, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        credentials: "include", // Include cookies/session
      });

      console.log("📡 Response received:");
      console.log("  - Status:", response.status);
      console.log("  - Status Text:", response.statusText);
      console.log("  - Headers:", [...response.headers.entries()]);
      console.log("  - URL:", response.url);
      console.log("  - Redirected:", response.redirected);
      console.log("  - Type:", response.type);

      // Clone response để đọc text trước
      const responseClone = response.clone();
      const responseText = await responseClone.text();
      console.log("📄 Raw response text (first 1000 chars):");
      console.log(responseText.substring(0, 1000));
      console.log("📄 Response length:", responseText.length);

      // Kiểm tra content type
      const contentType = response.headers.get("content-type");
      console.log("📄 Content-Type:", contentType);

      if (!response.ok) {
        console.error("❌ HTTP Error:", response.status, response.statusText);
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      // Thử parse JSON
      let result;
      try {
        if (contentType && contentType.includes("application/json")) {
          result = await response.json();
          console.log("✅ JSON parsed successfully:", result);
        } else {
          console.error("❌ Response is not JSON. Content-Type:", contentType);
          console.log("Raw response:", responseText);
          throw new Error(
            "Response is not JSON. Got: " + (contentType || "unknown")
          );
        }
      } catch (jsonError) {
        console.error("❌ JSON Parse Error:", jsonError);
        console.log("Response text that failed to parse:", responseText);
        throw new Error("Failed to parse JSON response: " + jsonError.message);
      }

      if (result.success) {
        console.log("✅ API call successful");
        console.log("Data count:", result.data ? result.data.length : 0);
        console.log("Pagination:", result.pagination);
        renderAppointments(result.data);
        renderPagination(result.pagination);
      } else {
        console.error("❌ API returned error:", result.message);
        showError(result.message || "Không thể tải danh sách lịch khám");
      }
    } catch (error) {
      console.error("❌ Error loading appointments:", error);
      console.error("Error stack:", error.stack);
      showError("Đã xảy ra lỗi khi tải danh sách lịch khám: " + error.message);
    } finally {
      hideLoading();
    }
  }

  // Tạo URL API với các tham số
  function buildApiUrl() {
    let url = `${config.apiBaseUrl}/api_getAppointmentsByDoctor/${config.doctorId}?page=${config.currentPage}&limit=${config.perPage}`;

    if (config.filters.status) {
      url += `&status=${encodeURIComponent(config.filters.status)}`;
    }

    if (config.filters.startDate) {
      url += `&started_date=${encodeURIComponent(config.filters.startDate)}`;
    }

    if (config.filters.endDate) {
      url += `&ended_date=${encodeURIComponent(config.filters.endDate)}`;
    }

    return url;
  }

  // Hiển thị loading
  function showLoading() {
    appointmentsTableBody.innerHTML = "";
    appointmentsLoading.classList.remove("d-none");
    appointmentsLoading.style.display = "flex";
    appointmentsEmpty.style.display = "none";
  }

  // Ẩn loading
  function hideLoading() {
    // ✅ SỬA: Thêm class d-none
    appointmentsLoading.classList.add("d-none");
    appointmentsLoading.style.display = "none";
  }

  // Hiển thị thông báo lỗi
  function showError(message) {
    appointmentsTableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-danger">${message}</td>
            </tr>
        `;
  }

  // Hiển thị trạng thái trống
  function showEmpty() {
    appointmentsTableBody.innerHTML = ""; 
    appointmentsEmpty.style.display = "block";
  }

  // Render danh sách lịch khám
  function renderAppointments(appointments) {
    if (!appointments || appointments.length === 0) {
      showEmpty();
      return;
    }

    appointmentsTableBody.innerHTML = "";

    appointments.forEach((appointment) => {
      const row = document.createElement("tr");

      // ✅ Debug: Log appointment data để kiểm tra
      console.log("🔍 [RENDER] Appointment data:", appointment);
      console.log("🔍 [RENDER] Status value:", `"${appointment.status}"`);
      console.log("🔍 [RENDER] Status type:", typeof appointment.status);

      // ✅ SỬA: Normalize status để handle case-sensitive và trim whitespace
      const normalizedStatus = (appointment.status || "").toString().trim();
      console.log("🔍 [RENDER] Normalized status:", `"${normalizedStatus}"`);

      // ✅ SỬA: Chỉ 2 trạng thái và xác định badge tương ứng
      let statusBadgeClass = "";
      let statusDisplay = "";

      switch (normalizedStatus) {
        case "Đã khám":
        case "ĐÃ KHÁM":
        case "da kham":
        case "Completed": // Nếu backend trả về tiếng Anh
          statusBadgeClass = "badge-success";
          statusDisplay = "Đã khám";
          break;
        case "Chưa khám":
        case "CHƯA KHÁM":
        case "chua kham":
        case "Pending": // Nếu backend trả về tiếng Anh
        case "Scheduled":
          statusBadgeClass = "badge-warning";
          statusDisplay = "Chưa khám";
          break;
        default:
          // ✅ Default cho unknown status
          statusBadgeClass = "badge-secondary";
          statusDisplay = normalizedStatus || "Không xác định";
          console.warn("🔍 [RENDER] Unknown status:", normalizedStatus);
      }

      // ✅ SỬA: Nút thao tác dựa trên trạng thái đã normalize
      let actionButton = "";
      if (
        normalizedStatus === "Chưa khám" ||
        normalizedStatus === "CHƯA KHÁM" ||
        normalizedStatus === "chua kham" ||
        normalizedStatus === "Pending" ||
        normalizedStatus === "Scheduled"
      ) {
        actionButton = `
        <button class="btn btn-sm btn-primary exam-btn" data-id="${
          appointment.appointment_id
        }" data-patient="${appointment.patient_name || "N/A"}">
          <i class="fas fa-stethoscope mr-1"></i> Khám bệnh
        </button>
      `;
      } else if (
        normalizedStatus === "Đã khám" ||
        normalizedStatus === "ĐÃ KHÁM" ||
        normalizedStatus === "da kham" ||
        normalizedStatus === "Completed"
      ) {
        actionButton = `
        <button class="btn btn-sm btn-outline-info view-exam-btn" data-id="${
          appointment.appointment_id
        }" data-patient="${appointment.patient_name || "N/A"}">
          <i class="fas fa-eye mr-1"></i> Xem chi tiết
        </button>
      `;
      } else {
        // ✅ Fallback cho status không xác định
        actionButton = `
        <button class="btn btn-sm btn-outline-secondary" disabled title="Trạng thái: ${normalizedStatus}">
          <i class="fas fa-question mr-1"></i> Không xác định
        </button>
      `;
      }

      // ✅ Debug: Log button được tạo
      console.log("🔍 [RENDER] Action button HTML:", actionButton);

      row.innerHTML = `
      <td>
        <div class="d-flex align-items-center">
          <i class="fas fa-user text-muted mr-2"></i>
          <span>${appointment.patient_name || "Không xác định"}</span>
        </div>
      </td>
      <td>
        <div class="d-flex align-items-center">
          <i class="far fa-calendar text-muted mr-2"></i>
          <span>${appointment.date || "N/A"}</span>
        </div>
      </td>
      <td>
        <div class="d-flex align-items-center">
          <i class="far fa-clock text-muted mr-2"></i>
          <span>${appointment.started_time || "N/A"}</span>
        </div>
      </td>
      <td>
        <span class="badge ${statusBadgeClass} px-2 py-1">${statusDisplay}</span>
      </td>
      <td class="action-column">
        ${actionButton}
      </td>
    `;

      appointmentsTableBody.appendChild(row);
    });

    // ✅ Debug: Log số lượng nút được tạo
    setTimeout(() => {
      const examBtns = document.querySelectorAll(".exam-btn");
      const viewBtns = document.querySelectorAll(".view-exam-btn");
      console.log("🔍 [RENDER] Exam buttons created:", examBtns.length);
      console.log("🔍 [RENDER] View buttons created:", viewBtns.length);
    }, 100);

    // Đăng ký sự kiện cho các nút
    registerButtonEvents();
  }

  // Đăng ký sự kiện cho các nút trong bảng
  function registerButtonEvents() {
    console.log("🔗 [EVENTS] Registering button events...");

    // Xử lý nút "Khám bệnh"
    const examBtns = document.querySelectorAll(".exam-btn");
    console.log("🔗 [EVENTS] Found exam buttons:", examBtns.length);

    examBtns.forEach((btn, index) => {
      console.log(`🔗 [EVENTS] Registering exam button ${index + 1}`);
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        const appointmentId = this.dataset.id;
        const patientName = this.dataset.patient;
        console.log(
          "🩺 [EXAM] Opening exam modal for:",
          appointmentId,
          patientName
        );
        openExamModal(appointmentId);
      });
    });

    // Xử lý nút "Xem chi tiết"
    const viewBtns = document.querySelectorAll(".view-exam-btn");
    console.log("🔗 [EVENTS] Found view buttons:", viewBtns.length);

    viewBtns.forEach((btn, index) => {
      console.log(`🔗 [EVENTS] Registering view button ${index + 1}`);
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        const appointmentId = this.dataset.id;
        const patientName = this.dataset.patient;
        console.log(
          "👁️ [VIEW] Opening view modal for:",
          appointmentId,
          patientName
        );
        openViewExamModal(appointmentId);
      });
    });

    console.log("🔗 [EVENTS] Button events registered successfully");
  }

  // Mở modal khám bệnh
  // function openExamModal(appointmentId) {
  //   console.log(
  //     "🩺 [EXAM_MODAL] Opening exam modal for appointment:",
  //     appointmentId
  //   );

  //   // TODO: Load dữ liệu chi tiết cuộc hẹn từ API
  //   fetch(`${config.apiBaseUrl}/api_getAppointment/${appointmentId}`)
  //     .then((response) => {
  //       console.log("🩺 [EXAM_MODAL] API response status:", response.status);
  //       return response.json();
  //     })
  //     .then((data) => {
  //       console.log("🩺 [EXAM_MODAL] API response data:", data);
  //       if (data.success) {
  //         // Populate modal with appointment data
  //         console.log("🩺 [EXAM_MODAL] Appointment data loaded:", data.data);
  //         populateExamModal(data.data);
  //       } else {
  //         console.error(
  //           "🩺 [EXAM_MODAL] Error loading appointment:",
  //           data.message
  //         );
  //         // Show modal anyway for now
  //       }
  //       $("#examModal").modal("show");
  //     })
  //     .catch((error) => {
  //       console.error("🩺 [EXAM_MODAL] Error fetching appointment:", error);
  //       // Show modal anyway for now
  //       $("#examModal").modal("show");
  //     });
  // }

  // Mở modal xem chi tiết khám bệnh
  function openViewExamModal(appointmentId) {
    console.log(
      "👁️ [VIEW_MODAL] Opening view modal for appointment:",
      appointmentId
    );

    // TODO: Load dữ liệu chi tiết khám bệnh từ API
    fetch(`${config.apiBaseUrl}/api_getAppointment/${appointmentId}`)
      .then((response) => {
        console.log("👁️ [VIEW_MODAL] API response status:", response.status);
        return response.json();
      })
      .then((data) => {
        console.log("👁️ [VIEW_MODAL] API response data:", data);
        if (data.success) {
          // Populate modal with appointment data
          console.log("👁️ [VIEW_MODAL] Appointment data loaded:", data.data);
          populateViewModal(data.data);
        } else {
          console.error(
            "👁️ [VIEW_MODAL] Error loading appointment:",
            data.message
          );
          // Show modal anyway for now
        }
        $("#viewExamModal").modal("show");
      })
      .catch((error) => {
        console.error("👁️ [VIEW_MODAL] Error fetching appointment:", error);
        // Show modal anyway for now
        $("#viewExamModal").modal("show");
      });
  }

  function populateExamModal(appointmentData) {
    // TODO: Implement populating exam modal with appointment data
    console.log("🩺 [POPULATE] Populating exam modal with:", appointmentData);
  }

  function populateViewModal(appointmentData) {
    // TODO: Implement populating view modal with appointment data
    console.log("👁️ [POPULATE] Populating view modal with:", appointmentData);
  }

  // Render phân trang
  function renderPagination(pagination) {
    const { page, total_pages } = pagination;

    if (total_pages <= 1) {
      appointmentsPagination.innerHTML = "";
      return;
    }

    let paginationHtml = "";

    // Nút Previous
    paginationHtml += `
            <li class="page-item ${page === 1 ? "disabled" : ""}">
                <a class="page-link" href="#" data-page="${
                  page - 1
                }" aria-label="Previous">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;

    // Các trang
    const startPage = Math.max(1, page - 2);
    const endPage = Math.min(total_pages, page + 2);

    for (let i = startPage; i <= endPage; i++) {
      paginationHtml += `
                <li class="page-item ${i === page ? "active" : ""}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
    }

    // Nút Next
    paginationHtml += `
            <li class="page-item ${page === total_pages ? "disabled" : ""}">
                <a class="page-link" href="#" data-page="${
                  page + 1
                }" aria-label="Next">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;

    appointmentsPagination.innerHTML = paginationHtml;

    // Đăng ký sự kiện cho các nút phân trang
    document
      .querySelectorAll("#appointmentsPagination .page-link")
      .forEach((link) => {
        link.addEventListener("click", function (e) {
          e.preventDefault();
          const pageNumber = parseInt(this.dataset.page, 10);

          if (pageNumber !== config.currentPage && !isNaN(pageNumber)) {
            config.currentPage = pageNumber;
            loadAppointments();
          }
        });
      });
  }

  function initMedicinesTab() {
    console.log("💊 [INIT_MEDICINES] Initializing medicines tab...");

    // ✅ KIỂM TRA: DOM elements tồn tại
    const elementsToCheck = {
      searchMedicineInput: document.getElementById("searchMedicine"),
      searchMedicineBtn: document.getElementById("searchMedicineBtn"),
      showNearExpiryBtn: document.getElementById("showNearExpiryBtn"),
      medicinesTableBody: document.getElementById("medicinesTableBody"),
      medicinesLoading: document.getElementById("medicinesLoading"),
      medicinesEmpty: document.getElementById("medicinesEmpty"),
      medicinesPagination: document.getElementById("medicinesPagination"),
      medicinesTab: document.getElementById("medicines-tab"),
    };

    console.log("💊 [INIT_MEDICINES] DOM elements check:");
    Object.entries(elementsToCheck).forEach(([name, element]) => {
      console.log(`  - ${name}:`, element ? "✅ Found" : "❌ Missing");
    });

    // ✅ SỬA: Kiểm tra critical elements
    if (!elementsToCheck.medicinesTableBody) {
      console.error(
        "❌ [INIT_MEDICINES] Critical DOM elements missing, aborting initialization"
      );
      return;
    }

    const {
      searchMedicineInput,
      searchMedicineBtn,
      showNearExpiryBtn,
      medicinesTableBody,
      medicinesLoading,
      medicinesEmpty,
      medicinesPagination,
      medicinesTab,
    } = elementsToCheck;

    // ✅ SỬA: Search button event với null check
    if (searchMedicineBtn) {
      searchMedicineBtn.addEventListener("click", function () {
        console.log("🔍 [MEDICINES] Search button clicked");
        medicineConfig.searchTerm = searchMedicineInput
          ? searchMedicineInput.value.trim()
          : "";
        medicineConfig.currentPage = 1;
        medicineConfig.viewMode = "all";
        console.log(
          "🔍 [MEDICINES] Search term set to:",
          `"${medicineConfig.searchTerm}"`
        );
        loadMedicines();
      });
      console.log("✅ [INIT_MEDICINES] Search button event registered");
    }

    // ✅ SỬA: Enter key search với null check
    if (searchMedicineInput) {
      searchMedicineInput.addEventListener("keyup", function (e) {
        if (e.key === "Enter") {
          console.log("🔍 [MEDICINES] Enter key pressed in search");
          medicineConfig.searchTerm = searchMedicineInput.value.trim();
          medicineConfig.currentPage = 1;
          medicineConfig.viewMode = "all";
          console.log(
            "🔍 [MEDICINES] Search term set to:",
            `"${medicineConfig.searchTerm}"`
          );
          loadMedicines();
        }
      });
      console.log("✅ [INIT_MEDICINES] Search input event registered");
    }

    // ✅ SỬA: Near expiry button với enhanced toggle
    if (showNearExpiryBtn) {
      showNearExpiryBtn.addEventListener("click", function () {
        console.log("⚠️ [MEDICINES] Near expiry button clicked");
        console.log(
          "⚠️ [MEDICINES] Current view mode:",
          medicineConfig.viewMode
        );

        medicineConfig.currentPage = 1;
        medicineConfig.searchTerm = "";
        if (searchMedicineInput) searchMedicineInput.value = "";

        if (medicineConfig.viewMode === "near-expiry") {
          medicineConfig.viewMode = "all";
          showNearExpiryBtn.innerHTML =
            '<i class="fas fa-exclamation-triangle mr-1"></i> Sắp hết hạn';
          showNearExpiryBtn.classList.remove("btn-warning");
          showNearExpiryBtn.classList.add("btn-outline-primary");
          console.log("⚠️ [MEDICINES] Switched to ALL mode");
        } else {
          medicineConfig.viewMode = "near-expiry";
          showNearExpiryBtn.innerHTML =
            '<i class="fas fa-list mr-1"></i> Xem tất cả';
          showNearExpiryBtn.classList.remove("btn-outline-primary");
          showNearExpiryBtn.classList.add("btn-warning");
          console.log("⚠️ [MEDICINES] Switched to NEAR-EXPIRY mode");
        }

        loadMedicines();
      });
      console.log("✅ [INIT_MEDICINES] Near expiry button event registered");
    }

    // ✅ SỬA: Tab shown event với proper Bootstrap event handling
    if (medicinesTab) {
      $(medicinesTab).on("shown.bs.tab", function (e) {
        console.log("📋 [MEDICINES] Tab shown event triggered");
        console.log(
          "📋 [MEDICINES] Current table body children:",
          medicinesTableBody.children.length
        );
        console.log(
          "📋 [MEDICINES] Loading display:",
          medicinesLoading.style.display
        );

        // Load medicines when tab becomes active if not already loaded
        if (
          medicinesTableBody.children.length === 0 &&
          medicinesLoading.style.display !== "flex"
        ) {
          console.log("📋 [MEDICINES] Loading medicines on tab show");
          loadMedicines();
        }
      });
      console.log("✅ [INIT_MEDICINES] Tab shown event registered");
    }

    // ✅ THÊM: Auto-load if tab is initially active
    setTimeout(() => {
      if (medicinesTab && medicinesTab.classList.contains("active")) {
        console.log(
          "📋 [MEDICINES] Tab is initially active, loading medicines..."
        );
        loadMedicines();
      } else {
        console.log("📋 [MEDICINES] Tab is not initially active");
      }
    }, 500);

    console.log("✅ [INIT_MEDICINES] Medicines tab initialized successfully");
  }

  function loadMedicines() {
    console.log("💊 [LOAD_MEDICINES] ================ START ================");
    console.log("💊 [LOAD_MEDICINES] Medicine config:", medicineConfig);

    // ✅ SỬA: SHOW LOADING NGAY ĐẦU
    showMedicinesLoading();

    const url = buildMedicinesApiUrl();
    console.log("🌐 [LOAD_MEDICINES] API URL:", url);

    fetch(url, {
      method: "GET",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      credentials: "include",
    })
      .then((response) => {
        console.log("📡 [LOAD_MEDICINES] Response received:");
        console.log("  - Status:", response.status);
        console.log("  - Status Text:", response.statusText);
        console.log("  - URL:", response.url);
        console.log("  - Headers:", [...response.headers.entries()]);

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const contentType = response.headers.get("content-type");
        console.log("📡 [LOAD_MEDICINES] Content-Type:", contentType);

        if (!contentType || !contentType.includes("application/json")) {
          throw new Error(`Expected JSON, got: ${contentType}`);
        }

        return response.json();
      })
      .then((result) => {
        console.log("📊 [LOAD_MEDICINES] Raw API result:", result);

        let processedData = processMedicinesResponse(result);
        console.log("📊 [LOAD_MEDICINES] Processed data:", processedData);

        const { data, pagination } = processedData;

        console.log("📊 [LOAD_MEDICINES] Final data count:", data.length);
        console.log("📊 [LOAD_MEDICINES] Final pagination:", pagination);

        // ✅ SỬA: LUÔN ẨN LOADING TRƯỚC KHI XỬ LÝ
        hideMedicinesLoading();

        if (data.length === 0) {
          showMedicinesEmpty();
        } else {
          renderMedicines(data);
          renderMedicinesPagination(pagination);
        }
      })
      .catch((error) => {
        console.error("❌ [LOAD_MEDICINES] Error:", error);
        console.error("❌ [LOAD_MEDICINES] Error stack:", error.stack);

        // ✅ SỬA: ẨN LOADING KHI CÓ LỖI
        hideMedicinesLoading();
        showMedicinesError(
          "Đã xảy ra lỗi khi tải danh sách thuốc: " + error.message
        );
      })
      .finally(() => {
        // ✅ SỬA: Đảm bảo loading luôn được ẩn
        setTimeout(() => {
          hideMedicinesLoading();
          console.log(
            "💊 [LOAD_MEDICINES] ================ END ================"
          );
        }, 100);
      });
  }

  function processMedicinesResponse(result) {
    console.log("🔄 [PROCESS] Processing API response...");

    let data = [];
    let pagination = {
      page: medicineConfig.currentPage,
      limit: medicineConfig.perPage,
      total: 0,
      total_pages: 1,
    };

    // ✅ Case 1: Response có cấu trúc phân trang chuẩn (từ MedicineService.php)
    if (result && result.data && Array.isArray(result.data)) {
      console.log("🔄 [PROCESS] Structured response detected");
      data = result.data;
      pagination = {
        page: result.page || medicineConfig.currentPage,
        limit: result.limit || medicineConfig.perPage,
        total: result.total || data.length,
        total_pages:
          result.total_pages || Math.ceil(data.length / medicineConfig.perPage),
      };
    }
    // ✅ Case 2: Response là array trực tiếp
    else if (Array.isArray(result)) {
      console.log("🔄 [PROCESS] Direct array response detected");
      data = result;
      pagination = {
        page: medicineConfig.currentPage,
        limit: medicineConfig.perPage,
        total: data.length,
        total_pages: Math.ceil(data.length / medicineConfig.perPage),
      };
    }
    // ✅ Case 3: Response có error
    else if (result && result.error) {
      console.error("🔄 [PROCESS] API error:", result.error);
      throw new Error(result.error);
    }
    // ✅ Case 4: Empty hoặc invalid response
    else {
      console.warn("🔄 [PROCESS] Empty or invalid response");
      data = [];
    }

    console.log("🔄 [PROCESS] Final processed data:");
    console.log("  - Data items:", data.length);
    console.log("  - Page:", pagination.page);
    console.log("  - Total:", pagination.total);
    console.log("  - Total pages:", pagination.total_pages);

    return { data, pagination };
  }

  function buildMedicinesApiUrl() {
    console.log("🔗 [BUILD_URL] Building medicines API URL...");
    console.log("🔗 [BUILD_URL] Current config:", medicineConfig);

    let url;
    const baseUrl = medicineConfig.apiBaseUrl; // "/UDPT-QLBN/Medicine"
    const page = medicineConfig.currentPage;
    const limit = medicineConfig.perPage;

    console.log("🔗 [BUILD_URL] Parameters:");
    console.log("  - Base URL:", baseUrl);
    console.log("  - View Mode:", medicineConfig.viewMode);
    console.log("  - Search Term:", `"${medicineConfig.searchTerm}"`);
    console.log("  - Page:", page);
    console.log("  - Limit:", limit);

    // ✅ SỬA: Đúng với routing trong Medicine.php
    if (medicineConfig.viewMode === "near-expiry") {
      url = `${baseUrl}/api_getNearExpiryMedicines?page=${page}&limit=${limit}`;
      console.log("🔗 [BUILD_URL] Near expiry mode selected");
    } else if (medicineConfig.searchTerm && medicineConfig.searchTerm.trim()) {
      const encodedQuery = encodeURIComponent(medicineConfig.searchTerm.trim());
      url = `${baseUrl}/api_searchMedicines?query=${encodedQuery}&page=${page}&limit=${limit}`;
      console.log("🔗 [BUILD_URL] Search mode selected");
      console.log("🔗 [BUILD_URL] Encoded query:", encodedQuery);
    } else {
      url = `${baseUrl}/api_getAllMedicines?page=${page}&limit=${limit}`;
      console.log("🔗 [BUILD_URL] All medicines mode selected");
    }

    console.log("🔗 [BUILD_URL] Final URL:", url);
    return url;
  }

  function showMedicinesLoading() {
    console.log("🔄 [UI] Showing medicines loading...");

    const medicinesTableBody = document.getElementById("medicinesTableBody");
    const medicinesLoading = document.getElementById("medicinesLoading");
    const medicinesEmpty = document.getElementById("medicinesEmpty");

    console.log("🔄 [UI] Elements check:");
    console.log(
      "  - medicinesTableBody:",
      medicinesTableBody ? "✅ Found" : "❌ Missing"
    );
    console.log(
      "  - medicinesLoading:",
      medicinesLoading ? "✅ Found" : "❌ Missing"
    );
    console.log(
      "  - medicinesEmpty:",
      medicinesEmpty ? "✅ Found" : "❌ Missing"
    );

    // Clear table first
    if (medicinesTableBody) {
      medicinesTableBody.innerHTML = "";
      console.log("✅ [UI] Table cleared");
    } else {
      console.error("❌ [UI] medicinesTableBody not found");
    }

    // Show loading
    if (medicinesLoading) {
      medicinesLoading.style.display = "flex";
      console.log("✅ [UI] Loading shown");
    } else {
      console.error("❌ [UI] medicinesLoading element not found");
      // ✅ FALLBACK: Tạo loading indicator tạm thời
      if (medicinesTableBody) {
        medicinesTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Đang tải...</span>
                        </div>
                        <p class="mt-2 text-muted">Đang tải danh sách thuốc...</p>
                    </td>
                </tr>
            `;
        console.log("✅ [UI] Fallback loading shown in table");
      }
    }

    // Hide empty state
    if (medicinesEmpty) {
      medicinesEmpty.style.display = "none";
      console.log("✅ [UI] Empty state hidden");
    }
  }

  function hideMedicinesLoading() {
    console.log("🔄 [UI] Hiding medicines loading...");

    const medicinesLoading = document.getElementById("medicinesLoading");

    if (medicinesLoading) {
      medicinesLoading.style.display = "none";
      console.log("✅ [UI] Loading hidden successfully");
    } else {
      console.error("❌ [UI] medicinesLoading element not found for hiding");
      // ✅ FALLBACK: Clear table loading if exists
      const medicinesTableBody = document.getElementById("medicinesTableBody");
      if (
        medicinesTableBody &&
        medicinesTableBody.innerHTML.includes("spinner-border")
      ) {
        console.log("✅ [UI] Clearing fallback loading from table");
        // Don't clear here, let other functions handle content
      }
    }
  }

  function showMedicinesError(message) {
    console.error("📋 [UI] Showing error:", message);

    // ✅ SỬA: LUÔN ẨN LOADING TRƯỚC
    hideMedicinesLoading();

    const medicinesTableBody = document.getElementById("medicinesTableBody");

    if (!medicinesTableBody) {
      console.warn("⚠️ [UI] medicinesTableBody not found");
      return;
    }

    medicinesTableBody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center text-danger p-4">
                <div>
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h6 class="text-danger">Lỗi tải dữ liệu</h6>
                    <p class="mb-3">${message}</p>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadMedicines()">
                        <i class="fas fa-redo mr-1"></i>Thử lại
                    </button>
                </div>
            </td>
        </tr>
    `;

    console.log("✅ [UI] Error state shown");
  }

  function showMedicinesEmpty() {
    console.log("📋 [UI] Showing empty medicines state");

    // ✅ SỬA: LUÔN ẨN LOADING TRƯỚC
    hideMedicinesLoading();

    const medicinesTableBody = document.getElementById("medicinesTableBody");
    const medicinesEmpty = document.getElementById("medicinesEmpty");

    if (!medicinesTableBody) {
      console.warn("⚠️ [UI] medicinesTableBody not found");
      return;
    }

    // Clear table content
    medicinesTableBody.innerHTML = "";

    // Show empty state in table body
    medicinesTableBody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center p-5">
                <div class="text-muted">
                    <i class="fas fa-pills fa-3x mb-3 text-muted" style="opacity: 0.3;"></i>
                    <h6 class="text-muted">
                        ${
                          medicineConfig.viewMode === "near-expiry"
                            ? "Không có thuốc sắp hết hạn"
                            : medicineConfig.searchTerm
                            ? `Không tìm thấy thuốc "${medicineConfig.searchTerm}"`
                            : "Không có thuốc trong kho"
                        }
                    </h6>
                    <p class="small text-muted mb-0">
                        ${
                          medicineConfig.searchTerm
                            ? "Thử tìm kiếm với từ khóa khác"
                            : "Danh sách thuốc trống"
                        }
                    </p>
                </div>
            </td>
        </tr>
    `;

    // Hide separate empty div
    if (medicinesEmpty) {
      medicinesEmpty.style.display = "none";
    }

    console.log("✅ [UI] Empty state shown in table");
  }

  function formatPrice(price) {
    return new Intl.NumberFormat("vi-VN").format(price);
  }

  function formatDate(dateString) {
    if (!dateString) return "N/A";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;

    return new Intl.DateTimeFormat("vi-VN").format(date);
  }

  function renderMedicines(medicines) {
    console.log("📋 [RENDER] Rendering medicines:", medicines);

    // ✅ SỬA: LUÔN ẨN LOADING ĐẦU TIÊN
    hideMedicinesLoading();

    if (!medicinesTableBody) {
      console.error("❌ [RENDER] medicinesTableBody not found");
      return;
    }

    if (!medicines || !Array.isArray(medicines) || medicines.length === 0) {
      console.log("📋 [RENDER] No medicines to render, showing empty state");
      showMedicinesEmpty();
      return;
    }

    // ✅ SỬA: Clear table và hide empty state
    medicinesTableBody.innerHTML = "";

    const medicinesEmpty = document.getElementById("medicinesEmpty");
    if (medicinesEmpty) {
      medicinesEmpty.style.display = "none";
    }

    console.log(`📋 [RENDER] Rendering ${medicines.length} medicines`);

    medicines.forEach((medicine, index) => {
      try {
        // ✅ SỬA: Simple data structure
        const medicineData = {
          id: medicine.medicine_id || medicine.id || index + 1,
          name:
            medicine.name ||
            medicine.medicine_name ||
            "Tên thuốc không xác định",
          unit: medicine.unit || medicine.medicine_unit || "viên",
          price: medicine.price || 0,
          amount: medicine.amount || 0,
        };

        console.log(
          `📋 [RENDER] Processing medicine ${index + 1}:`,
          medicineData
        );

        const row = document.createElement("tr");

        // ✅ SỬA: Simple black & white table - không màu mè
        row.innerHTML = `
                <td class="text-center">${medicineData.id}</td>
                <td>${medicineData.name}</td>
                <td class="text-center">${medicineData.unit}</td>
                <td class="text-right">${formatPrice(medicineData.price)} ₫</td>
                <td class="text-center">${medicineData.amount}</td>
            `;

        medicinesTableBody.appendChild(row);
      } catch (error) {
        console.error(`❌ [RENDER] Error rendering medicine ${index}:`, error);

        // ✅ FALLBACK: Simple row on error
        const fallbackRow = document.createElement("tr");
        fallbackRow.innerHTML = `
                <td class="text-center">${index + 1}</td>
                <td>Lỗi hiển thị thuốc</td>
                <td class="text-center">-</td>
                <td class="text-right">-</td>
                <td class="text-center">-</td>
            `;
        medicinesTableBody.appendChild(fallbackRow);
      }
    });

    console.log("✅ [RENDER] All medicines rendered successfully");
  }

  function renderMedicinesPagination(pagination) {
    if (!medicinesPagination) return;

    const { page, total_pages } = pagination;

    if (total_pages <= 1) {
      medicinesPagination.innerHTML = "";
      return;
    }

    let paginationHtml = "";

    // Nút Previous
    paginationHtml += `
      <li class="page-item ${page === 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${
          page - 1
        }" aria-label="Previous">
          <i class="fas fa-chevron-left"></i>
        </a>
      </li>
    `;

    // Các trang
    const startPage = Math.max(1, page - 2);
    const endPage = Math.min(total_pages, page + 2);

    for (let i = startPage; i <= endPage; i++) {
      paginationHtml += `
        <li class="page-item ${i === page ? "active" : ""}">
          <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>
      `;
    }

    // Nút Next
    paginationHtml += `
      <li class="page-item ${page === total_pages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${page + 1}" aria-label="Next">
          <i class="fas fa-chevron-right"></i>
        </a>
      </li>
    `;

    medicinesPagination.innerHTML = paginationHtml;

    // Đăng ký sự kiện cho các nút phân trang
    document
      .querySelectorAll("#medicinesPagination .page-link")
      .forEach((link) => {
        link.addEventListener("click", function (e) {
          e.preventDefault();
          const pageNumber = parseInt(this.dataset.page, 10);

          if (pageNumber !== medicineConfig.currentPage && !isNaN(pageNumber)) {
            medicineConfig.currentPage = pageNumber;
            loadMedicines();
          }
        });
      });
  }

  let currentExamData = null;
  let selectedMedicines = [];
  let medicineCounter = 0;

  // ✅ UPDATED: Handle add medicine button
  function handleAddMedicine() {
    console.log("💊 [ADD_MEDICINE] Adding new medicine form");

    // Create new medicine form
    createNewMedicineForm();

    // Show prescription items container
    showPrescriptionContainer();
  }

  function createNewMedicineForm() {
    medicineCounter++;

    const formDiv = document.createElement("div");
    formDiv.className = "simple-medicine-form new-form";
    formDiv.setAttribute("data-medicine-id", medicineCounter);

    formDiv.innerHTML = `
        <div class="medicine-form-header">
            <h6 class="mb-0 text-primary d-flex align-items-center">
                <i class="fas fa-pills mr-2"></i>
                Thuốc ${medicineCounter}
            </h6>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-form" 
                    data-medicine-id="${medicineCounter}" title="Xóa thuốc này">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="form-group position-relative">
            <label class="form-label">Tên thuốc <span class="text-danger">*</span></label>
            <input type="text" class="form-control medicine-name-input" 
                   placeholder="Nhập tên thuốc..." 
                   data-medicine-id="${medicineCounter}" 
                   autocomplete="off" required>
            <input type="hidden" class="medicine-id-input" data-medicine-id="${medicineCounter}">
            
            <!-- Dropdown suggestions -->
            <div class="medicine-suggestions" data-medicine-id="${medicineCounter}" 
                 style="display: none; position: absolute; top: 100%; left: 0; right: 0; 
                        background: white; border: 1px solid #dee2e6; border-top: none; 
                        border-radius: 0 0 4px 4px; max-height: 200px; overflow-y: auto; z-index: 1000;">
            </div>
        </div>
        
        <div class="medicine-info-display" data-medicine-id="${medicineCounter}" style="display: none;">
            <div class="alert alert-info py-2 px-3 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="medicine-selected-name"></strong>
                        <span class="medicine-selected-unit text-muted ml-2"></span>
                    </div>
                    <small class="medicine-selected-stock text-muted"></small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control medicine-amount-input" 
                               placeholder="Số lượng" min="1" value="1"
                               data-medicine-id="${medicineCounter}" required>
                        <div class="input-group-append">
                            <span class="input-group-text medicine-unit-display">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Ghi chú sử dụng</label>
            <input type="text" class="form-control medicine-note-input" 
                   placeholder="VD: Uống sau ăn, 1 viên x 3 lần/ngày"
                   data-medicine-id="${medicineCounter}">
        </div>
    `;

    // Add to prescription items container
    const prescriptionItems = document.getElementById("prescriptionItems");
    prescriptionItems.appendChild(formDiv);

    // Add event listeners
    addFormEventListeners(formDiv);

    // Focus on name input
    const nameInput = formDiv.querySelector(".medicine-name-input");
    setTimeout(() => nameInput.focus(), 100);

    console.log("✅ [ADD_MEDICINE] Form created with ID:", medicineCounter);
  }

  function addFormEventListeners(formDiv) {
    const medicineId = formDiv.getAttribute("data-medicine-id");

    // Remove form button
    const removeBtn = formDiv.querySelector(".btn-remove-form");
    removeBtn.addEventListener("click", () => removeMedicineForm(medicineId));

    // Medicine name autocomplete
    const nameInput = formDiv.querySelector(".medicine-name-input");
    const suggestionsDiv = formDiv.querySelector(".medicine-suggestions");
    const medicineIdInput = formDiv.querySelector(".medicine-id-input");
    const infoDisplay = formDiv.querySelector(".medicine-info-display");
    const unitDisplay = formDiv.querySelector(".medicine-unit-display");

    let searchTimeout;
    let selectedMedicineData = null;

    // Search medicines as user types
    nameInput.addEventListener("input", function () {
      const query = this.value.trim();

      clearTimeout(searchTimeout);

      if (query.length < 2) {
        hideSuggestions();
        clearMedicineSelection();
        return;
      }

      searchTimeout = setTimeout(() => {
        searchMedicines(query, suggestionsDiv, medicineId);
      }, 300);
    });

    // Hide suggestions when clicking outside
    document.addEventListener("click", function (e) {
      if (!formDiv.contains(e.target)) {
        hideSuggestions();
      }
    });

    // Helper functions
    function hideSuggestions() {
      suggestionsDiv.style.display = "none";
    }

    function clearMedicineSelection() {
      medicineIdInput.value = "";
      infoDisplay.style.display = "none";
      unitDisplay.textContent = "-";
      selectedMedicineData = null;
    }

    function selectMedicine(medicine) {
      nameInput.value = medicine.name;
      medicineIdInput.value = medicine.medicine_id || medicine.id;
      selectedMedicineData = medicine;

      // Show medicine info
      const nameSpan = infoDisplay.querySelector(".medicine-selected-name");
      const unitSpan = infoDisplay.querySelector(".medicine-selected-unit");
      const stockSpan = infoDisplay.querySelector(".medicine-selected-stock");

      nameSpan.textContent = medicine.name;
      unitSpan.textContent = `(${medicine.unit || "N/A"})`;
      stockSpan.textContent = `Tồn kho: ${medicine.amount || 0}`;

      infoDisplay.style.display = "block";
      unitDisplay.textContent = medicine.unit || "-";

      hideSuggestions();

      console.log("✅ [MEDICINE_SELECT] Selected:", medicine);
    }

    // Auto-convert when form is complete
    const amountInput = formDiv.querySelector(".medicine-amount-input");
    amountInput.addEventListener("blur", () => {
      if (selectedMedicineData) {
        checkAndConvertForm(medicineId);
      }
    });

    // Store select function for use in suggestions
    formDiv._selectMedicine = selectMedicine;
  }

  async function searchMedicines(query, suggestionsDiv, medicineId) {
    try {
      console.log("🔍 [SEARCH] Searching medicines for:", query);

      // ✅ SỬA: URL và params giống medicine-management.js
      const params = `page=1&limit=10`;
      const url = `/UDPT-QLBN/Medicine/api_searchMedicines?query=${encodeURIComponent(
        query.trim()
      )}&${params}`;

      console.log("🔍 [SEARCH] Request URL:", url);

      const response = await fetch(url, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        credentials: "include",
      });

      console.log("🔍 [SEARCH] Response status:", response.status);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      console.log("🔍 [SEARCH] Raw response:", data);

      // ✅ SỬA: Copy logic xử lý response từ medicine-management.js
      let processedData;

      if (data && typeof data === "object") {
        // Nếu có cấu trúc phân trang từ Python API
        if (data.hasOwnProperty("data") && Array.isArray(data.data)) {
          processedData = data;
          console.log("📊 [SEARCH] Structured response with pagination");
        }
        // Nếu là array đơn giản
        else if (Array.isArray(data)) {
          processedData = {
            data: data,
            page: 1,
            limit: 10,
            total: data.length,
            total_pages: Math.ceil(data.length / 10),
          };
          console.log(
            "📊 [SEARCH] Array response, created pagination structure"
          );
        }
        // Nếu có lỗi
        else if (data.hasOwnProperty("error")) {
          throw new Error(data.error);
        } else {
          throw new Error("Định dạng dữ liệu API không hợp lệ");
        }
      } else {
        throw new Error("Dữ liệu API không hợp lệ");
      }

      // ✅ SỬA: Sử dụng processedData.data thay vì medicines trực tiếp
      const medicines = processedData.data || [];

      console.log("🔍 [SEARCH] Processed medicines:", medicines);

      if (medicines.length === 0) {
        suggestionsDiv.innerHTML = `
                <div class="p-3 text-center text-muted">
                    <i class="fas fa-search mr-2"></i>Không tìm thấy thuốc phù hợp cho "${query}"
                </div>
            `;
      } else {
        suggestionsDiv.innerHTML = medicines
          .map(
            (medicine) => `
                    <div class="medicine-suggestion-item p-2 border-bottom" 
                         data-medicine-id="${
                           medicine.medicine_id || medicine.id
                         }"
                         style="cursor: pointer;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${medicine.name}</strong>
                                <span class="text-muted ml-2">(${
                                  medicine.unit || "N/A"
                                })</span>
                            </div>
                            <small class="text-muted">Tồn: ${
                              medicine.amount || 0
                            }</small>
                        </div>
                    </div>
                `
          )
          .join("");

        // Add click handlers for suggestions
        suggestionsDiv
          .querySelectorAll(".medicine-suggestion-item")
          .forEach((item, index) => {
            item.addEventListener("click", () => {
              const medicine = medicines[index];
              const formDiv = suggestionsDiv.closest(".simple-medicine-form");
              if (formDiv && formDiv._selectMedicine) {
                formDiv._selectMedicine(medicine);
              }
            });

            item.addEventListener("mouseenter", () => {
              item.style.backgroundColor = "#f8f9fa";
            });

            item.addEventListener("mouseleave", () => {
              item.style.backgroundColor = "";
            });
          });
      }

      suggestionsDiv.style.display = "block";
    } catch (error) {
      console.error("❌ [SEARCH] Error searching medicines:", error);
      suggestionsDiv.innerHTML = `
            <div class="p-3 text-center text-danger">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <div>Lỗi tìm kiếm thuốc</div>
                <small>${error.message}</small>
            </div>
        `;
      suggestionsDiv.style.display = "block";
    }
  }

  // ✅ NEW: Auto-convert form to item when required fields are filled
  function checkAndConvertForm(medicineId) {
    const formDiv = document.querySelector(
      `[data-medicine-id="${medicineId}"]`
    );
    if (!formDiv || !formDiv.classList.contains("simple-medicine-form")) return;

    const nameInput = formDiv.querySelector(".medicine-name-input");
    const medicineIdInput = formDiv.querySelector(".medicine-id-input");
    const amountInput = formDiv.querySelector(".medicine-amount-input");
    const noteInput = formDiv.querySelector(".medicine-note-input");
    const unitDisplay = formDiv.querySelector(".medicine-unit-display");

    const name = nameInput.value.trim();
    const selectedMedicineId = medicineIdInput.value;
    const amount = parseInt(amountInput.value);
    const note = noteInput.value.trim();
    const unit =
      unitDisplay.textContent !== "-" ? unitDisplay.textContent : "viên";

    // ✅ VALIDATION: Kiểm tra thuốc đã được chọn
    if (name && !selectedMedicineId && amount && amount > 0 && note.length > 0) {
      // Highlight input with error
      nameInput.classList.add("is-invalid");

      // Show error message
      let errorMsg = formDiv.querySelector(".medicine-error-msg");
      if (!errorMsg) {
        errorMsg = document.createElement("div");
        errorMsg.className = "medicine-error-msg invalid-feedback";
        nameInput.parentNode.appendChild(errorMsg);
      }
      errorMsg.textContent = "Vui lòng chọn thuốc từ danh sách gợi ý";
      return;
    }

    // Remove error styling if validation passes
    nameInput.classList.remove("is-invalid");
    const errorMsg = formDiv.querySelector(".medicine-error-msg");
    if (errorMsg) errorMsg.remove();

    // Check if required fields are filled and medicine is selected
    if (name && selectedMedicineId && amount && amount > 0) {
      // Create medicine item
      const medicineItem = {
        id: parseInt(medicineId),
        medicine_id: selectedMedicineId,
        name: name,
        amount: amount,
        unit: unit,
        note: note,
      };

      // Add to selected medicines if not already added
      const existingIndex = selectedMedicines.findIndex(
        (m) => m.id === medicineItem.id
      );
      if (existingIndex === -1) {
        selectedMedicines.push(medicineItem);
      } else {
        selectedMedicines[existingIndex] = medicineItem;
      }

      // Convert form to display item
      convertFormToDisplayItem(formDiv, medicineItem);

      console.log("✅ [AUTO_CONVERT] Medicine auto-converted:", medicineItem);
      console.log(
        "📋 [MEDICINES] Current selected medicines:",
        selectedMedicines
      );

      // Update display
      updatePrescriptionDisplay();
    }
  }

  // ✅ NEW: Convert form to display item (replace old confirmAddMedicine)
  function convertFormToDisplayItem(formDiv, medicine) {
    // Update form styling to show it's completed
    formDiv.classList.add("completed");

    // Update header to show completed status
    const header = formDiv.querySelector(".medicine-form-header h6");
    header.innerHTML = `
      <i class="fas fa-check-circle mr-2 text-success"></i>
      ${medicine.name}
      <span class="medicine-form-counter ml-2" style="background-color: #28a745; color: white; border-radius: 12px; padding: 2px 8px; font-size: 11px;">Đã thêm</span>
  `;

    // Make all inputs readonly
    const inputs = formDiv.querySelectorAll("input");
    inputs.forEach((input) => {
      input.readOnly = true;
      input.style.backgroundColor = "#f8f9fa";
    });

    // Update remove button styling
    const removeBtn = formDiv.querySelector(".btn-remove-form");
    removeBtn.className = "btn btn-sm btn-outline-danger btn-remove-item";
    removeBtn.title = "Xóa thuốc khỏi đơn";

    // Update event listener for remove button
    removeBtn.removeEventListener("click", () =>
      removeMedicineForm(medicine.id)
    );
    removeBtn.addEventListener("click", () => removeMedicineItem(medicine.id));
  }

  // ✅ UPDATED: Remove medicine item (works for both forms and converted items)
  function removeMedicineItem(medicineId) {
    // Remove from selectedMedicines array
    selectedMedicines = selectedMedicines.filter((m) => m.id !== medicineId);

    // Remove from DOM
    const itemDiv = document.querySelector(
      `[data-medicine-id="${medicineId}"]`
    );
    if (itemDiv) {
      itemDiv.remove();
    }

    console.log("🗑️ [REMOVE_ITEM] Removed medicine with ID:", medicineId);
    console.log("📋 [MEDICINES] Remaining medicines:", selectedMedicines);

    // Update display
    updatePrescriptionDisplay();
  }

  function removeMedicineForm(medicineId) {
    // Remove from DOM
    const formDiv = document.querySelector(
      `[data-medicine-id="${medicineId}"]`
    );
    if (formDiv) {
      formDiv.remove();
      console.log("🗑️ [REMOVE_FORM] Removed form with ID:", medicineId);
    }

    // Update display
    updatePrescriptionDisplay();
  }

  function showPrescriptionContainer() {
    const emptyDiv = document.getElementById("emptyPrescription");
    const itemsDiv = document.getElementById("prescriptionItems");
    const summaryDiv = document.getElementById("prescriptionSummary");

    emptyDiv.style.display = "none";
    itemsDiv.style.display = "block";
    summaryDiv.style.display = "block";
  }

  function updatePrescriptionDisplay() {
    const emptyDiv = document.getElementById("emptyPrescription");
    const itemsDiv = document.getElementById("prescriptionItems");
    const summaryDiv = document.getElementById("prescriptionSummary");
    const countSpan = document.getElementById("totalMedicinesCount");

    // Check if there are any items (forms or confirmed medicines) in the container
    const hasItems = itemsDiv && itemsDiv.children.length > 0;

    if (!hasItems) {
      if (emptyDiv) emptyDiv.style.display = "flex";
      if (itemsDiv) itemsDiv.style.display = "none";
      if (summaryDiv) summaryDiv.style.display = "none";
    } else {
      if (emptyDiv) emptyDiv.style.display = "none";
      if (itemsDiv) itemsDiv.style.display = "block";
      if (summaryDiv) summaryDiv.style.display = "block";

      // Update count (only confirmed medicines)
      if (countSpan) countSpan.textContent = selectedMedicines.length;
    }
  }

  function resetExamModal() {
    console.log("🔄 [RESET] Resetting exam modal");

    // Show loading, hide content and error
    const loadingDiv = document.getElementById("examModalLoading");
    const contentDiv = document.getElementById("examModalContent");
    const errorDiv = document.getElementById("examModalError");

    if (loadingDiv) loadingDiv.style.display = "block";
    if (contentDiv) contentDiv.style.display = "none";
    if (errorDiv) errorDiv.style.display = "none";

    // Reset form data
    const examDescription = document.getElementById("examDescription");
    const prescriptionDays = document.getElementById("prescriptionDays");

    if (examDescription) examDescription.value = "";
    if (prescriptionDays) prescriptionDays.value = "7";

    // Reset medicines
    selectedMedicines = [];
    medicineCounter = 0;
    currentExamData = null;

    // Clear prescription items
    const prescriptionItems = document.getElementById("prescriptionItems");
    if (prescriptionItems) {
      prescriptionItems.innerHTML = "";
    }

    updatePrescriptionDisplay();

    // Reset patient info
    const patientElements = [
      "examPatientName",
      "examPatientNameDetail",
      "examPatientDob",
      "examPatientGender",
      "examPatientPhone",
      "examAppointmentDate",
      "examAppointmentTime",
    ];

    patientElements.forEach((id) => {
      const element = document.getElementById(id);
      if (element) {
        element.textContent = id === "examPatientName" ? "Bệnh nhân" : "-";
      }
    });
  }

  function initExamModalEvents() {
    console.log("🔗 [INIT] Initializing exam modal events");

    // Add medicine button
    const addMedicineBtn = document.getElementById("addMedicineBtn");
    if (addMedicineBtn) {
      // Remove existing event listeners
      addMedicineBtn.removeEventListener("click", handleAddMedicine);
      // Add new event listener
      addMedicineBtn.addEventListener("click", handleAddMedicine);
      console.log("✅ [INIT] Add medicine button event registered");
    } else {
      console.warn("⚠️ [INIT] Add medicine button not found");
    }

    // Save exam button
    const saveExamBtn = document.getElementById("saveExamBtn");
    if (saveExamBtn) {
      // Remove existing event listeners
      saveExamBtn.removeEventListener("click", handleSaveExam);
      // Add new event listener
      saveExamBtn.addEventListener("click", handleSaveExam);
      console.log("✅ [INIT] Save exam button event registered");
    } else {
      console.warn("⚠️ [INIT] Save exam button not found");
    }
  }

  // ✅ THÊM: Debug function để kiểm tra DOM elements
  function debugModalElements() {
    console.log("🔍 [DEBUG] Checking modal elements:");

    const elements = [
      "examModal",
      "examModalLoading",
      "examModalContent",
      "examModalError",
      "addMedicineBtn",
      "saveExamBtn",
      "prescriptionItems",
      "emptyPrescription",
    ];

    elements.forEach((id) => {
      const element = document.getElementById(id);
      console.log(`  - ${id}:`, element ? "✅ Found" : "❌ Missing");
    });
  }

  // ✅ UPDATED: Gọi debug khi DOM ready
  document.addEventListener("DOMContentLoaded", function () {
    console.log("🚀 [INIT] DOM Content Loaded");

    // Debug modal elements
    setTimeout(() => {
      debugModalElements();
    }, 1000);

    // Test modal functionality
    setTimeout(() => {
      const addBtn = document.getElementById("addMedicineBtn");
      if (addBtn) {
        console.log("🧪 [TEST] Add medicine button found, adding test event");
        addBtn.addEventListener("click", function () {
          console.log("🧪 [TEST] Add medicine button clicked!");
        });
      }
    }, 1500);
  });

  async function handleSaveExam() {
    console.log("💾 [SAVE_EXAM] ================== START ==================");

    if (!currentExamData) {
      alert("Không có thông tin lịch khám");
      return;
    }

    const saveBtn = document.getElementById("saveExamBtn");
    const originalText = saveBtn.innerHTML;

    try {
      // Disable button and show loading
      saveBtn.disabled = true;
      saveBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin mr-1"></i>Đang lưu...';

      // ✅ VALIDATION: Get and validate form data
      const examDescription = document.getElementById("examDescription");
      const prescriptionDays = document.getElementById("prescriptionDays");

      if (!examDescription) {
        throw new Error("Không tìm thấy trường mô tả khám bệnh");
      }

      if (!prescriptionDays) {
        throw new Error("Không tìm thấy trường số ngày thuốc");
      }

      const description = examDescription.value.trim();
      const days = parseInt(prescriptionDays.value);

      console.log("💾 [SAVE_EXAM] Form data:");
      console.log("  - Description:", `"${description}"`);
      console.log("  - Days:", days);
      console.log("  - Description length:", description.length);

      // Validate description
      if (!description || description.length < 10) {
        alert("Vui lòng nhập mô tả khám bệnh (ít nhất 10 ký tự)");
        examDescription.focus();
        return;
      }

      // Validate prescription days
      if (!days || days < 1 || days > 30) {
        alert("Vui lòng nhập số ngày uống thuốc hợp lệ (1-30 ngày)");
        prescriptionDays.focus();
        return;
      }

      // ✅ COLLECT medicines
      const allForms = document.querySelectorAll(".simple-medicine-form");
      const uncommittedMedicines = [];

      console.log("💾 [SAVE_EXAM] Found forms:", allForms.length);

      // Process uncommitted forms
      for (const form of allForms) {
        if (!form.classList.contains("completed")) {
          const medicineId = form.getAttribute("data-medicine-id");
          const nameInput = form.querySelector(".medicine-name-input");
          const medicineIdInput = form.querySelector(".medicine-id-input");
          const amountInput = form.querySelector(".medicine-amount-input");
          const noteInput = form.querySelector(".medicine-note-input");

          if (nameInput && medicineIdInput && amountInput) {
            const name = nameInput.value.trim();
            const selectedMedicineId = medicineIdInput.value;
            const amount = parseInt(amountInput.value);
            const note = noteInput ? noteInput.value.trim() : "";

            if (name && selectedMedicineId && amount > 0) {
              uncommittedMedicines.push({
                id: parseInt(medicineId),
                medicine_id: selectedMedicineId,
                name: name,
                amount: amount,
                note: note,
              });
            }
          }
        }
      }

      const allMedicines = [...selectedMedicines, ...uncommittedMedicines];
      console.log("💾 [SAVE_EXAM] All medicines:", allMedicines);

      // ✅ STEP 1: Update appointment
      console.log("📝 [SAVE_EXAM] Step 1: Updating appointment...");
      const updateAppointmentData = {
        description: description,
        status: "Đã khám",
      };

      console.log(
        "📝 [SAVE_EXAM] Appointment update data:",
        updateAppointmentData
      );

      const updateAppointmentResponse = await fetch(
        `${config.apiBaseUrl}/api_updateAppointment/${currentExamData.appointment_id}`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          credentials: "include",
          body: JSON.stringify(updateAppointmentData),
        }
      );

      console.log(
        "📝 [SAVE_EXAM] Appointment update response status:",
        updateAppointmentResponse.status
      );

      if (!updateAppointmentResponse.ok) {
        const errorText = await updateAppointmentResponse.text();
        console.error("📝 [SAVE_EXAM] Appointment update failed:", errorText);
        throw new Error(
          `Cập nhật lịch khám thất bại (${updateAppointmentResponse.status})`
        );
      }

      const appointmentResult = await updateAppointmentResponse.json();
      console.log(
        "📝 [SAVE_EXAM] Appointment update result:",
        appointmentResult
      );

      if (!appointmentResult.success) {
        throw new Error(
          appointmentResult.message || "Cập nhật lịch khám thất bại"
        );
      }

      // ✅ STEP 2: Create prescription if there are medicines
      if (allMedicines.length > 0) {
        console.log("💊 [SAVE_EXAM] Step 2: Creating prescription...");

        const prescriptionData = {
          appointment_id: parseInt(currentExamData.appointment_id),
          no_days: days,
          status: "Chưa lấy",
        };

        console.log("💊 [SAVE_EXAM] Prescription data:", prescriptionData);
        console.log("💊 [SAVE_EXAM] Sending prescription data:", JSON.stringify(prescriptionData));
        const createPrescriptionResponse = await fetch(
          "/UDPT-QLBN/Prescription/api_createPrescription",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              Accept: "application/json",
            },
            credentials: "include",
            body: JSON.stringify(prescriptionData),
          }
        );

        console.log(
          "💊 [SAVE_EXAM] Prescription create response status:",
          createPrescriptionResponse.status
        );

        if (!createPrescriptionResponse.ok) {
          const errorText = await createPrescriptionResponse.text();
          console.error(
            "💊 [SAVE_EXAM] Prescription create failed:",
            errorText
          );
          throw new Error(
            `Tạo đơn thuốc thất bại (${createPrescriptionResponse.status})`
          );
        }

        const prescriptionResult = await createPrescriptionResponse.json();
        console.log(
          "💊 [SAVE_EXAM] Prescription create result:",
          prescriptionResult
        );

        if (!prescriptionResult.success) {
          throw new Error(
            prescriptionResult.message || "Tạo đơn thuốc thất bại"
          );
        }

        const prescriptionId = prescriptionResult.data.prescription_id;
        console.log("💊 [SAVE_EXAM] Created prescription ID:", prescriptionId);

        // ✅ STEP 3: Add medicines to prescription
        console.log(
          "💊 [SAVE_EXAM] Step 3: Adding medicines to prescription..."
        );

        let successCount = 0;
        let errorCount = 0;

        for (const medicine of allMedicines) {
          try {
            // ✅ SỬA: Data structure khớp với Python API
            const medicineDetailData = {
              prescription_id: prescriptionId,
              medicine_id: parseInt(medicine.medicine_id),
              amount: medicine.amount,
              note: medicine.note || "Uống sau bữa ăn",
            };

            console.log(
              "💊 [SAVE_EXAM] Adding medicine detail:",
              medicineDetailData
            );

            const addMedicineResponse = await fetch(
              "/UDPT-QLBN/Prescription/api_addPrescriptionDetail",
              {
                method: "POST",
                headers: {
                  "Content-Type": "application/json",
                  Accept: "application/json",
                },
                credentials: "include",
                body: JSON.stringify(medicineDetailData),
              }
            );
            console.log(
              "💊 [SAVE_EXAM] Medicine detail response status:",
              addMedicineResponse.status
            );

            if (!addMedicineResponse.ok) {
              const errorText = await addMedicineResponse.text();
              console.error(
                "💊 [SAVE_EXAM] Medicine detail failed:",
                errorText
              );
              errorCount++;
              continue;
            }

            const medicineResult = await addMedicineResponse.json();
            console.log(
              "💊 [SAVE_EXAM] Medicine detail result:",
              medicineResult
            );

            if (medicineResult.success) {
              successCount++;
              console.log(`✅ [SAVE_EXAM] Added medicine: ${medicine.name}`);
            } else {
              console.warn(
                `⚠️ [SAVE_EXAM] Failed to add medicine: ${medicine.name} - ${medicineResult.message}`
              );
              errorCount++;
            }
          } catch (medicineError) {
            console.error(
              `❌ [SAVE_EXAM] Error adding medicine ${medicine.name}:`,
              medicineError
            );
            errorCount++;
          }
        }

        console.log(
          `💊 [SAVE_EXAM] Medicine summary: ${successCount} success, ${errorCount} errors`
        );

        if (successCount === 0 && errorCount > 0) {
          throw new Error("Không thể thêm thuốc vào đơn thuốc");
        }
      }

      // ✅ SUCCESS
      console.log("✅ [SAVE_EXAM] All steps completed successfully");

      alert("Lưu thông tin khám bệnh thành công!");
      $("#examModal").modal("hide");

      // Reload appointments
      await loadAppointments();
    } catch (error) {
      console.error("❌ [SAVE_EXAM] Error:", error);
      alert("Lỗi khi lưu thông tin: " + error.message);
    } finally {
      // Restore button
      saveBtn.disabled = false;
      saveBtn.innerHTML = originalText;
      console.log("💾 [SAVE_EXAM] ================== END ==================");
    }
  }

  async function openExamModal(appointmentId) {
    console.log(
      "🩺 [EXAM_MODAL] Opening exam modal for appointment:",
      appointmentId
    );

    // Reset modal state
    resetExamModal();
    $("#examModal").modal("show");

    // Load appointment data
    await loadExamAppointmentDetails(appointmentId);
  }

  async function loadExamAppointmentDetails(appointmentId) {
    try {
      console.log("📡 [LOAD_EXAM] Loading appointment:", appointmentId);

      // Load appointment data
      const response = await fetch(
        `${config.apiBaseUrl}/api_getAppointment/${appointmentId}`
      );
      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || "Không thể tải thông tin lịch khám");
      }

      console.log("✅ [LOAD_EXAM] Appointment loaded:", data.data);

      // ✅ THÊM: Enhanced appointment với patient info từ PatientService
      let enhancedAppointment = data.data;

      if (enhancedAppointment.patient_id) {
        try {
          console.log(
            "👤 [LOAD_EXAM] Loading patient info for ID:",
            enhancedAppointment.patient_id
          );

          // Call patient API to get full patient info
          const patientResponse = await fetch(
            `/UDPT-QLBN/Patient/api_getPatient/${enhancedAppointment.patient_id}`,
            {
              method: "GET",
              headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
              },
              credentials: "include",
            }
          );

          if (patientResponse.ok) {
            const patientData = await patientResponse.json();
            if (patientData.success && patientData.data) {
              console.log(
                "👤 [LOAD_EXAM] Patient info loaded:",
                patientData.data
              );

              // ✅ SỬA: Merge patient info kể cả identity_card
              enhancedAppointment.patient_gender =
                patientData.data.gender ||
                enhancedAppointment.patient_gender ||
                "-";
              enhancedAppointment.patient_birthdate =
                patientData.data.birth_date ||
                enhancedAppointment.patient_birthdate ||
                "-";
              enhancedAppointment.patient_medical_history =
                patientData.data.medical_history || "Không có";
              enhancedAppointment.patient_address =
                patientData.data.address || "Không có";
              enhancedAppointment.patient_email =
                patientData.data.email || "Không có";
              enhancedAppointment.patient_identity_card =
                patientData.data.identity_card || "Không có";

              console.log(
                "✅ [LOAD_EXAM] Patient identity_card:",
                enhancedAppointment.patient_identity_card
              );
            }
          }
        } catch (patientError) {
          console.warn(
            "⚠️ [LOAD_EXAM] Could not load patient info:",
            patientError.message
          );
        }
      }

      // Store current exam data
      currentExamData = enhancedAppointment;

      // Populate appointment info
      populateExamAppointmentInfo(enhancedAppointment);

      // Show content, hide loading
      document.getElementById("examModalLoading").style.display = "none";
      document.getElementById("examModalContent").style.display = "block";

      // Initialize exam modal events
      initExamModalEvents();
    } catch (error) {
      console.error("❌ [LOAD_EXAM] Error:", error);
      showExamModalError(error.message);
    }
  }

  function populateExamAppointmentInfo(appointment) {
    console.log("📝 [POPULATE_EXAM] Populating appointment info:", appointment);

    try {
      // Header
      document.getElementById("examPatientName").textContent =
        appointment.patient_name || "Bệnh nhân";

      // ✅ SỬA: Enhanced patient info với đầy đủ fields
      const patientFields = [
        { id: "examPatientNameDetail", value: appointment.patient_name },
        {
          id: "examPatientDob",
          value: appointment.patient_birthdate || appointment.patient_dob,
        },
        { id: "examPatientGender", value: appointment.patient_gender },
        { id: "examPatientPhone", value: appointment.patient_phone },
        { id: "examAppointmentDate", value: appointment.date },
        { id: "examAppointmentTime", value: appointment.started_time },
      ];

      patientFields.forEach((field) => {
        const element = document.getElementById(field.id);
        if (element) {
          element.textContent = field.value || "-";
        }
      });

      // ✅ THÊM: Medical history
      const medicalHistoryElement = document.getElementById(
        "examPatientMedicalHistory"
      );
      if (medicalHistoryElement) {
        const medicalHistory =
          appointment.patient_medical_history || "Không có";
        medicalHistoryElement.textContent = medicalHistory;
      }

      // Pre-fill description if exists
      if (appointment.description) {
        document.getElementById("examDescription").value =
          appointment.description;
      }

      // ✅ THÊM: Initialize medical history events
      initMedicalHistoryEvents();

      console.log("✅ [POPULATE_EXAM] Appointment info populated successfully");
    } catch (error) {
      console.error(
        "❌ [POPULATE_EXAM] Error populating appointment info:",
        error
      );
    }
  }

  function showExamModalError(message) {
    document.getElementById("examModalLoading").style.display = "none";
    document.getElementById("examModalContent").style.display = "none";
    document.getElementById("examModalError").style.display = "block";
    document.getElementById("examModalErrorMessage").textContent = message;
  }

  let currentViewExamData = null;
  let currentPrescriptionData = null;

  // ✅ THÊM: Main function mở modal xem chi tiết
  async function openViewExamModal(appointmentId) {
    console.log(
      "👁️ [VIEW_EXAM] Opening view exam modal for appointment:",
      appointmentId
    );

    // Reset modal state
    resetViewExamModal();

    // Show modal
    $("#viewExamModal").modal("show");

    // Load appointment data
    await loadViewExamDetails(appointmentId);
  }

  // ✅ THÊM: Reset modal state
  function resetViewExamModal() {
    console.log("🔄 [VIEW_EXAM] Resetting view exam modal");

    // Show loading, hide content and error
    const loadingDiv = document.getElementById("viewExamModalLoading");
    const contentDiv = document.getElementById("viewExamModalContent");
    const errorDiv = document.getElementById("viewExamModalError");

    if (loadingDiv) loadingDiv.style.display = "block";
    if (contentDiv) contentDiv.style.display = "none";
    if (errorDiv) errorDiv.style.display = "none";

    // Reset data
    currentViewExamData = null;
    currentPrescriptionData = null;

    // Reset patient name in header
    const patientNameHeader = document.getElementById("viewExamPatientName");
    if (patientNameHeader) patientNameHeader.textContent = "Bệnh nhân";
  }

  // ✅ THÊM: Load appointment details
  async function loadViewExamDetails(appointmentId) {
    try {
      console.log("📡 [VIEW_EXAM] Loading appointment details:", appointmentId);

      // Step 1: Load appointment data
      const appointmentResponse = await fetch(
        `${config.apiBaseUrl}/api_getAppointment/${appointmentId}`,
        {
          method: "GET",
          headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          credentials: "include",
        }
      );

      console.log(
        "📡 [VIEW_EXAM] Appointment response status:",
        appointmentResponse.status
      );

      if (!appointmentResponse.ok) {
        throw new Error(
          `Không thể tải thông tin lịch khám: HTTP ${appointmentResponse.status}`
        );
      }

      const appointmentResult = await appointmentResponse.json();
      console.log("📡 [VIEW_EXAM] Appointment data:", appointmentResult);

      if (!appointmentResult.success) {
        throw new Error(
          appointmentResult.message || "Không thể tải thông tin lịch khám"
        );
      }

      // ✅ THÊM: Enhanced appointment với patient info từ PatientService
      let enhancedAppointment = appointmentResult.data;

      if (enhancedAppointment.patient_id) {
        try {
          console.log(
            "👤 [VIEW_EXAM] Loading patient info for ID:",
            enhancedAppointment.patient_id
          );

          // Call patient API to get full patient info
          const patientResponse = await fetch(
            `/UDPT-QLBN/Patient/api_getPatient/${enhancedAppointment.patient_id}`,
            {
              method: "GET",
              headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
              },
              credentials: "include",
            }
          );

          if (patientResponse.ok) {
            const patientData = await patientResponse.json();
            if (patientData.success && patientData.data) {
              console.log(
                "👤 [VIEW_EXAM] Patient info loaded:",
                patientData.data
              );

              // ✅ SỬA: Merge patient info đầy đủ
              enhancedAppointment.patient_gender =
                patientData.data.gender ||
                enhancedAppointment.patient_gender ||
                "-";
              enhancedAppointment.patient_birthdate =
                patientData.data.birth_date ||
                enhancedAppointment.patient_birthdate ||
                "-";
              enhancedAppointment.patient_medical_history =
                patientData.data.medical_history || "Không có";
              enhancedAppointment.patient_address =
                patientData.data.address || "Không có";
              enhancedAppointment.patient_email =
                patientData.data.email || "Không có";
              enhancedAppointment.patient_identity_card =
                patientData.data.identity_card || "Không có";

              console.log(
                "✅ [VIEW_EXAM] Enhanced appointment with patient info"
              );
              console.log(
                "👤 [VIEW_EXAM] Patient gender:",
                enhancedAppointment.patient_gender
              );
              console.log(
                "👤 [VIEW_EXAM] Patient birthdate:",
                enhancedAppointment.patient_birthdate
              );
              console.log(
                "👤 [VIEW_EXAM] Medical history:",
                enhancedAppointment.patient_medical_history
              );
            }
          } else {
            console.warn(
              "⚠️ [VIEW_EXAM] Patient API response not OK:",
              patientResponse.status
            );
          }
        } catch (patientError) {
          console.warn(
            "⚠️ [VIEW_EXAM] Could not load patient info:",
            patientError.message
          );
        }
      }

      // Store current view exam data
      currentViewExamData = enhancedAppointment;

      // Step 2: Populate appointment info
      populateViewExamAppointmentInfo(enhancedAppointment);

      // Step 3: Load prescription data
      await loadViewExamPrescription(appointmentId);

      // Show content, hide loading
      document.getElementById("viewExamModalLoading").style.display = "none";
      document.getElementById("viewExamModalContent").style.display = "block";
    } catch (error) {
      console.error("❌ [VIEW_EXAM] Error loading details:", error);
      showViewExamError(error.message);
    }
  }

  // ✅ THÊM: Populate appointment information
  function populateViewExamAppointmentInfo(appointment) {
    console.log("📝 [VIEW_EXAM] Populating appointment info:", appointment);

    try {
      // Header
      const patientNameHeader = document.getElementById("viewExamPatientName");
      if (patientNameHeader) {
        patientNameHeader.textContent = appointment.patient_name || "Bệnh nhân";
      }

      // ✅ SỬA: Enhanced patient info với debug
      const patientFields = [
        {
          id: "viewPatientName",
          value: appointment.patient_name,
          label: "Patient Name",
        },
        {
          id: "viewPatientDob",
          value: appointment.patient_birthdate || appointment.patient_dob,
          label: "Patient DOB",
        },
        {
          id: "viewPatientGender",
          value: appointment.patient_gender,
          label: "Patient Gender",
        },
        {
          id: "viewPatientPhone",
          value: appointment.patient_phone,
          label: "Patient Phone",
        },
        {
          id: "viewAppointmentDate",
          value: appointment.date,
          label: "Appointment Date",
        },
        {
          id: "viewAppointmentTime",
          value: appointment.started_time,
          label: "Appointment Time",
        },
      ];

      patientFields.forEach((field) => {
        const element = document.getElementById(field.id);
        if (element) {
          const value = field.value || "-";
          element.textContent = value;
          console.log(
            `📝 [VIEW_EXAM] ${field.label}: "${value}" -> ${field.id}`
          );
        } else {
          console.warn(`⚠️ [VIEW_EXAM] Element not found: ${field.id}`);
        }
      });

      // ✅ THÊM: Medical history for view modal
      const viewMedicalHistoryElement = document.getElementById(
        "viewPatientMedicalHistory"
      );
      if (viewMedicalHistoryElement) {
        const medicalHistory =
          appointment.patient_medical_history || "Không có";
        viewMedicalHistoryElement.textContent = medicalHistory;
        console.log("📝 [VIEW_EXAM] Medical history:", medicalHistory);
      } else {
        console.warn("⚠️ [VIEW_EXAM] Medical history element not found");
      }

      // Exam description
      const examDescElement = document.getElementById("viewExamDescription");
      if (examDescElement) {
        const description =
          appointment.description || "Chưa có thông tin khám bệnh";
        examDescElement.textContent = description;
        console.log("📝 [VIEW_EXAM] Exam description:", description);
      } else {
        console.warn("⚠️ [VIEW_EXAM] Exam description element not found");
      }

      // Status and completed date
      const statusElement = document.getElementById("viewAppointmentStatus");
      if (statusElement) {
        const status = appointment.status || "Đã khám";
        statusElement.textContent = status;
        console.log("📝 [VIEW_EXAM] Status:", status);
      }

      const completedDateElement = document.getElementById("viewCompletedDate");
      if (completedDateElement) {
        const completedDate = appointment.updated_at || appointment.date;
        completedDateElement.textContent = formatDate(completedDate);
        console.log("📝 [VIEW_EXAM] Completed date:", completedDate);
      }

      console.log("✅ [VIEW_EXAM] Appointment info populated successfully");
    } catch (error) {
      console.error("❌ [VIEW_EXAM] Error populating appointment info:", error);
    }
  }

  function showToast(message, type = "info") {
    // Create toast element
    const toast = document.createElement("div");
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = `
        top: 20px; 
        right: 20px; 
        z-index: 9999; 
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;

    toast.innerHTML = `
        <i class="fas fa-${
          type === "success" ? "check-circle" : "info-circle"
        } mr-2"></i>
        ${message}
    `;

    document.body.appendChild(toast);

    // Auto remove after 3 seconds
    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 150);
    }, 3000);
  }

  // ✅ THÊM: Load prescription data
  async function loadViewExamPrescription(appointmentId) {
    try {
      console.log(
        "💊 [VIEW_EXAM] Loading prescription for appointment:",
        appointmentId
      );

      // Show medicines loading
      showMedicinesLoading();

      // ✅ SỬA: Get prescription by appointment ID (đúng với Python API)
      const prescriptionResponse = await fetch(
        `/UDPT-QLBN/Prescription/api_getPrescriptionByAppointment/${appointmentId}`,
        {
          method: "GET",
          headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          credentials: "include",
        }
      );

      console.log(
        "💊 [VIEW_EXAM] Prescription response status:",
        prescriptionResponse.status
      );

      if (!prescriptionResponse.ok) {
        throw new Error(
          `HTTP ${prescriptionResponse.status}: ${prescriptionResponse.statusText}`
        );
      }

      const prescriptionResult = await prescriptionResponse.json();
      console.log("💊 [VIEW_EXAM] Prescription result:", prescriptionResult);

      if (!prescriptionResult.success) {
        // No prescription found
        console.log("💊 [VIEW_EXAM] No prescription found for appointment");
        showNoPrescription();
        return;
      }

      currentPrescriptionData = prescriptionResult.data;

      // Step 2: Populate prescription info
      populateViewExamPrescriptionInfo(currentPrescriptionData);

      // ✅ SỬA: Step 3: Load prescription details using appointment ID (không dùng prescription ID)
      await loadViewExamPrescriptionDetailsByAppointment(appointmentId);
    } catch (error) {
      console.error("❌ [VIEW_EXAM] Error loading prescription:", error);
      showMedicinesError(error.message);
    }
  }

  async function loadViewExamPrescriptionDetailsByAppointment(appointmentId) {
    try {
      console.log(
        "💊 [VIEW_EXAM] Loading prescription details for appointment ID:",
        appointmentId
      );

      // ✅ SỬA: Call API với appointment ID (endpoint mới)
      const detailsResponse = await fetch(
        `/UDPT-QLBN/Prescription/api_getPrescriptionDetailsByAppointment/${appointmentId}?page=1&limit=10`,
        {
          method: "GET",
          headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          credentials: "include",
        }
      );

      console.log(
        "💊 [VIEW_EXAM] Details response status:",
        detailsResponse.status
      );

      if (!detailsResponse.ok) {
        throw new Error(
          `HTTP ${detailsResponse.status}: ${detailsResponse.statusText}`
        );
      }

      const detailsResult = await detailsResponse.json();
      console.log("💊 [VIEW_EXAM] Details result:", detailsResult);

      // ✅ SỬA: Handle response structure
      let medicines = [];
      if (detailsResult && detailsResult.success && detailsResult.data) {
        // If data is paginated structure
        if (Array.isArray(detailsResult.data.data)) {
          medicines = detailsResult.data.data;
        } else if (Array.isArray(detailsResult.data)) {
          medicines = detailsResult.data;
        }
      }

      console.log("💊 [VIEW_EXAM] Processed medicines:", medicines);

      if (medicines.length === 0) {
        showNoPrescriptionMedicines();
      } else {
        renderViewExamMedicines(medicines);
      }
    } catch (error) {
      console.error(
        "❌ [VIEW_EXAM] Error loading prescription details:",
        error
      );
      showMedicinesError(error.message);
    }
  }

  // ✅ THÊM: Populate prescription information
  function populateViewExamPrescriptionInfo(prescription) {
    console.log("📝 [VIEW_EXAM] Populating prescription info:", prescription);

    try {
      // Prescription ID
      const prescriptionIdElement =
        document.getElementById("viewPrescriptionId");
      if (prescriptionIdElement) {
        prescriptionIdElement.textContent = prescription.prescription_id || "-";
      }

      // Days
      const daysElement = document.getElementById("viewPrescriptionDays");
      if (daysElement) {
        daysElement.textContent = prescription.no_days || "-";
      }

      // Status
      const statusElement = document.getElementById("viewPrescriptionStatus");
      const statusTextElement = document.getElementById(
        "viewPrescriptionStatusText"
      );

      if (statusElement && statusTextElement) {
        const status = prescription.status || "Chưa lấy";
        statusElement.textContent = status;
        statusTextElement.textContent = status;

        // Update badge class based on status
        statusElement.className = "badge";
        if (status === "Đã lấy") {
          statusElement.classList.add("badge-success");
        } else if (status === "Đã hủy") {
          statusElement.classList.add("badge-danger");
        } else {
          statusElement.classList.add("badge-warning");
        }
      }

      // Created date
      const createdDateElement = document.getElementById(
        "viewPrescriptionCreatedDate"
      );
      if (createdDateElement) {
        const createdDate = prescription.created_at || prescription.date;
        createdDateElement.textContent = formatDate(createdDate);
      }

      console.log("✅ [VIEW_EXAM] Prescription info populated successfully");
    } catch (error) {
      console.error(
        "❌ [VIEW_EXAM] Error populating prescription info:",
        error
      );
    }
  }

  //Render medicines table
  function renderViewExamMedicines(medicines) {
    console.log("📝 [VIEW_EXAM] Rendering medicines:", medicines);

    try {
      const tableBody = document.getElementById("viewMedicinesTableBody");
      const medicinesTable = document.getElementById("viewMedicinesTable");
      const medicinesCount = document.getElementById("viewMedicinesCount");

      if (!tableBody) {
        console.error("❌ [VIEW_EXAM] Medicines table body not found");
        return;
      }

      // Clear existing content
      tableBody.innerHTML = "";

      // ✅ SỬA: Render từng thuốc một dòng với đầy đủ 5 cột
      medicines.forEach((medicine, index) => {
        const row = document.createElement("tr");

        // ✅ SỬA: Lấy đúng thông tin từ response
        const medicineId = medicine.medicine_id || index + 1;
        const medicineName =
          medicine.medicine_name || `Thuốc ID: ${medicineId}`;
        const medicineUnit = medicine.medicine_unit || medicine.unit || "viên";
        const amount = medicine.amount || 0;
        const note = medicine.note || "Uống theo chỉ dẫn bác sĩ";

        // ✅ SỬA: Cấu trúc bảng đúng 5 cột: ID, Tên thuốc, Đơn vị, Số lượng, Cách dùng
        row.innerHTML = `
                <td class="text-center">
                    <span class="medicine-id">${medicineId}</span>
                </td>
                <td>
                    <div class="medicine-name">${medicineName}</div>
                </td>
                <td class="text-center">
                    <span class="medicine-unit">${medicineUnit}</span>
                </td>
                <td class="text-center">
                    <span class="medicine-amount">${amount}</span>
                </td>
                <td>
                    <span class="medicine-note">${note}</span>
                </td>
            `;

        tableBody.appendChild(row);
      });

      // Update count
      if (medicinesCount) {
        medicinesCount.textContent = medicines.length;
      }

      // Show table
      hideMedicinesLoading();
      if (medicinesTable) medicinesTable.style.display = "block";

      console.log("✅ [VIEW_EXAM] Medicines rendered successfully");
    } catch (error) {
      console.error("❌ [VIEW_EXAM] Error rendering medicines:", error);
      showMedicinesError("Lỗi hiển thị danh sách thuốc");
    }
  }

  // ✅ THÊM: Helper functions for UI states
  function showMedicinesLoading() {
    const loadingDiv = document.getElementById("viewMedicinesLoading");
    const tableDiv = document.getElementById("viewMedicinesTable");
    const errorDiv = document.getElementById("viewMedicinesError");
    const noPrescDiv = document.getElementById("viewNoPrescription");

    if (loadingDiv) loadingDiv.style.display = "block";
    if (tableDiv) tableDiv.style.display = "none";
    if (errorDiv) errorDiv.style.display = "none";
    if (noPrescDiv) noPrescDiv.style.display = "none";
  }

  function hideMedicinesLoading() {
    const loadingDiv = document.getElementById("viewMedicinesLoading");
    if (loadingDiv) loadingDiv.style.display = "none";
  }

  function showNoPrescription() {
    const noPrescDiv = document.getElementById("viewNoPrescription");
    const tableDiv = document.getElementById("viewMedicinesTable");
    const errorDiv = document.getElementById("viewMedicinesError");
    const summaryDiv = document.getElementById("viewPrescriptionSummary");

    hideMedicinesLoading();
    if (noPrescDiv) noPrescDiv.style.display = "block";
    if (tableDiv) tableDiv.style.display = "none";
    if (errorDiv) errorDiv.style.display = "none";
    if (summaryDiv) summaryDiv.style.display = "none";
  }

  function showNoPrescriptionMedicines() {
    const tableDiv = document.getElementById("viewMedicinesTable");
    const tableBody = document.getElementById("viewMedicinesTableBody");

    hideMedicinesLoading();

    if (tableBody) {
      tableBody.innerHTML = `
            <tr>
                <td colspan="3" class="text-center p-4">
                    <i class="fas fa-pills text-muted fa-2x mb-2"></i>
                    <div class="text-muted">Không có thuốc trong đơn</div>
                </td>
            </tr>
        `;
    }

    if (tableDiv) tableDiv.style.display = "block";
  }

  function showMedicinesError(message) {
    const errorDiv = document.getElementById("viewMedicinesError");
    const errorMessageDiv = document.getElementById(
      "viewMedicinesErrorMessage"
    );
    const tableDiv = document.getElementById("viewMedicinesTable");

    hideMedicinesLoading();

    if (errorDiv) errorDiv.style.display = "block";
    if (errorMessageDiv) errorMessageDiv.textContent = message;
    if (tableDiv) tableDiv.style.display = "none";
  }

  function showViewExamError(message) {
    const loadingDiv = document.getElementById("viewExamModalLoading");
    const contentDiv = document.getElementById("viewExamModalContent");
    const errorDiv = document.getElementById("viewExamModalError");
    const errorMessageDiv = document.getElementById(
      "viewExamModalErrorMessage"
    );

    if (loadingDiv) loadingDiv.style.display = "none";
    if (contentDiv) contentDiv.style.display = "none";
    if (errorDiv) errorDiv.style.display = "block";
    if (errorMessageDiv) errorMessageDiv.textContent = message;
  }

  // ✅ THÊM: Event listeners for modal buttons
  document.addEventListener("DOMContentLoaded", function () {
    // Retry load exam details
    const retryLoadBtn = document.getElementById("retryLoadViewExam");
    if (retryLoadBtn) {
      retryLoadBtn.addEventListener("click", function () {
        if (currentViewExamData && currentViewExamData.appointment_id) {
          loadViewExamDetails(currentViewExamData.appointment_id);
        }
      });
    }

    // Retry load medicines
    const retryMedicinesBtn = document.getElementById("retryLoadMedicines");
    if (retryMedicinesBtn) {
      retryMedicinesBtn.addEventListener("click", function () {
        if (currentViewExamData && currentViewExamData.appointment_id) {
          loadViewExamPrescriptionDetailsByAppointment(
            currentViewExamData.appointment_id
          );
        }
      });
    }

    // Print report (placeholder)
    const printBtn = document.getElementById("printExamReport");
    if (printBtn) {
      printBtn.addEventListener("click", function () {
        // TODO: Implement print functionality
        alert("Chức năng in báo cáo sẽ được triển khai sau");
      });
    }
  });

  function initMedicalHistoryEvents() {
    console.log("🩺 [MEDICAL_HISTORY] Initializing events");

    const editBtn = document.getElementById("editMedicalHistoryBtn");
    const cancelBtn = document.getElementById("cancelMedicalHistoryBtn");
    const saveBtn = document.getElementById("saveMedicalHistoryBtn");
    const viewDiv = document.getElementById("medicalHistoryView");
    const editDiv = document.getElementById("medicalHistoryEdit");
    const textarea = document.getElementById("medicalHistoryTextarea");
    const displayDiv = document.getElementById("examPatientMedicalHistory");

    if (editBtn) {
      editBtn.addEventListener("click", function () {
        console.log("✏️ [MEDICAL_HISTORY] Switching to edit mode");

        // Copy current text to textarea
        const currentText =
          displayDiv.textContent || displayDiv.innerText || "";
        textarea.value = currentText;

        // Switch to edit mode
        viewDiv.style.display = "none";
        editDiv.style.display = "block";
        textarea.focus();
      });
    }

    if (cancelBtn) {
      cancelBtn.addEventListener("click", function () {
        console.log("❌ [MEDICAL_HISTORY] Canceling edit");

        // Switch back to view mode
        editDiv.style.display = "none";
        viewDiv.style.display = "block";
      });
    }

    if (saveBtn) {
      saveBtn.addEventListener("click", function () {
        console.log("💾 [MEDICAL_HISTORY] Saving medical history");
        saveMedicalHistory();
      });
    }

    console.log("✅ [MEDICAL_HISTORY] Events initialized");
  }

  async function saveMedicalHistory() {
    if (!currentExamData || !currentExamData.patient_identity_card) {
      alert("Không có thông tin CMND/CCCD bệnh nhân để cập nhật");
      return;
    }

    const saveBtn = document.getElementById("saveMedicalHistoryBtn");
    const textarea = document.getElementById("medicalHistoryTextarea");
    const displayDiv = document.getElementById("examPatientMedicalHistory");
    const viewDiv = document.getElementById("medicalHistoryView");
    const editDiv = document.getElementById("medicalHistoryEdit");

    const originalText = saveBtn.innerHTML;
    const newMedicalHistory = textarea.value.trim();

    try {
      // Show loading
      saveBtn.disabled = true;
      saveBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin mr-1"></i>Đang lưu...';

      console.log(
        "💾 [MEDICAL_HISTORY] Saving for patient identity_card:",
        currentExamData.patient_identity_card
      );
      console.log("💾 [MEDICAL_HISTORY] New history:", newMedicalHistory);

      // ✅ SỬA: Call API với identity_card thay vì patient_id
      const response = await fetch(
        `/UDPT-QLBN/Patient/api_updatePatient/${currentExamData.patient_identity_card}`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          credentials: "include",
          body: JSON.stringify({
            medical_history: newMedicalHistory,
          }),
        }
      );

      console.log("💾 [MEDICAL_HISTORY] Response status:", response.status);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const result = await response.json();
      console.log("💾 [MEDICAL_HISTORY] Update result:", result);

      if (!result.success) {
        throw new Error(result.message || "Cập nhật tiền sử bệnh thất bại");
      }

      // ✅ SUCCESS: Update UI
      displayDiv.textContent = newMedicalHistory || "Không có";

      // Switch back to view mode
      editDiv.style.display = "none";
      viewDiv.style.display = "block";

      // Update current exam data
      if (currentExamData) {
        currentExamData.patient_medical_history = newMedicalHistory;
      }

      // Show success message
      showToast("Cập nhật tiền sử bệnh thành công!", "success");

      console.log("✅ [MEDICAL_HISTORY] Medical history saved successfully");
    } catch (error) {
      console.error("❌ [MEDICAL_HISTORY] Error saving:", error);
      alert("Lỗi khi lưu tiền sử bệnh: " + error.message);
    } finally {
      // Restore button
      saveBtn.disabled = false;
      saveBtn.innerHTML = originalText;
    }
  }

  // ✅ Helper function đã có
  function formatDate(dateString) {
    if (!dateString) return "N/A";
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString("vi-VN");
    } catch (error) {
      return dateString;
    }
  }

  // Khởi tạo trang
  function initPage() {
    // Kiểm tra nếu ID bác sĩ không tồn tại
    if (!config.doctorId) {
      showError("Không thể xác định thông tin bác sĩ. Vui lòng đăng nhập lại.");
      return;
    }

    // Khởi tạo bộ lọc
    initFilters();

    initMedicinesTab();

    // Load lịch khám ban đầu
    loadAppointments();
  }
  // Bắt đầu khởi tạo trang
  initPage();
});
