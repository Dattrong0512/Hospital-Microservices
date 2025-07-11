document.addEventListener("DOMContentLoaded", function () {
  // Variables to track current state
  let patients = [];
  let currentPage = 1;
  let itemsPerPage = 10;
  let currentPatient = null;

  // DOM Elements
  const tableBody = document.querySelector("#patientsTable tbody");
  const searchInput = document.getElementById("searchInput");
  const paginationInfo = document.getElementById("paginationInfo");
  const pagination = document.getElementById("pagination");
  const prevPageBtn = document.getElementById("prevPage");
  const nextPageBtn = document.getElementById("nextPage");
  const loadingIndicator = document.getElementById("loadingIndicator");

  // Load patients on page load
  loadPatients();
  let debounceTimeout = null;

  searchInput.addEventListener("input", function () {
    const identityCard = this.value.trim();
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      if (identityCard.length >= 3) {
        searchPatientByIdentityCard(identityCard);
      } else if (identityCard.length === 0) {
        loadPatients(); // Load all patients when search is empty
      }
    }, 1000); // Chờ 350ms sau khi người dùng dừng gõ mới gửi request
  });

  // Save patient button
  document
    .getElementById("savePatientBtn")
    .addEventListener("click", function () {
      const form = document.getElementById("addPatientForm");
      if (form.checkValidity()) {
        addPatient();
      } else {
        form.classList.add("was-validated");
      }
    });

  /**
   * Load all patients
   */
  function loadPatients() {
    showLoading(true);

    fetch(
      `/UDPT-QLBN/Patient/api_getPatients?page=${currentPage}&limit=${itemsPerPage}`
    )
      .then((response) => response.json())
      .then((data) => {
        showLoading(false);
        if (data.success) {
          patients = data.data || [];
          renderTable(patients);
          updatePagination(data.pagination);
        } else {
          showError("Không thể tải danh sách bệnh nhân");
          renderTable([]);
        }
      })
      .catch((error) => {
        console.error("Error loading patients:", error);
        showLoading(false);
        showError("Lỗi kết nối. Vui lòng thử lại.");
        renderTable([]);
      });
  }

  /**
   * Search patient by identity card
   */
  function searchPatientByIdentityCard(identityCard) {
    showLoading(true);

    fetch(
      `/UDPT-QLBN/Patient/api_getPatientByIdentityCard/${encodeURIComponent(
        identityCard
      )}`
    )
      .then((response) => {
        if (response.ok) {
          return response.json();
        } else if (response.status === 404) {
          // Patient not found
          return { success: false, message: "Không tìm thấy bệnh nhân" };
        } else {
          throw new Error(`HTTP ${response.status}`);
        }
      })
      .then((data) => {
        showLoading(false);
        if (data.success && data.data) {
          // Found patient, show in table
          renderTable([data.data]);
          updatePagination({ page: 1, limit: 1, total: 1, total_pages: 1 });
        } else {
          // No patient found
          renderTable([]);
          updatePagination({ page: 1, limit: 0, total: 0, total_pages: 0 });
          showMessage("Không tìm thấy bệnh nhân với CMND/CCCD này", "warning");
        }
      })
  }

  /**
   * Render patients table
   */
  function renderTable(patientsData) {
    if (!tableBody) return;

    if (!patientsData || patientsData.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="5" class="text-center py-4">
            <i class="fas fa-user-slash text-muted"></i>
            <p class="mt-2 text-muted">Không có dữ liệu bệnh nhân</p>
          </td>
        </tr>
      `;
      return;
    }

    tableBody.innerHTML = patientsData
      .map(
        (patient) => `
      <tr>
        <td>${patient.id || "N/A"}</td>
        <td>${patient.fullname || "N/A"}</td>
        <td>${patient.email || "N/A"}</td>
        <td>${patient.phone_number || "N/A"}</td>
        <td>
          <button class="btn btn-outline-primary btn-sm view-patient-btn" 
                  data-patient-id="${patient.id}" 
                  data-patient-identity="${patient.identity_card}"
                  title="Xem chi tiết">
            <i class="fas fa-eye"></i>
          </button>
        </td>
      </tr>
    `
      )
      .join("");

    // Setup view buttons
    setupViewButtons();
  }

  /**
   * Setup view patient buttons
   */
  function setupViewButtons() {
    const viewButtons = document.querySelectorAll(".view-patient-btn");
    viewButtons.forEach((btn) => {
      btn.addEventListener("click", function () {
        const patientId = this.getAttribute("data-patient-id");
        const identityCard = this.getAttribute("data-patient-identity");
        viewPatientDetails(identityCard || patientId);
      });
    });
  }

  /**
   * View patient details
   */
  function viewPatientDetails(identifier) {
    const modal = new bootstrap.Modal(
      document.getElementById("viewPatientModal")
    );
    modal.show();

    // Show loading
    document.getElementById("patientDetailLoading").classList.remove("d-none");
    document.getElementById("patientDetailContent").classList.add("d-none");

    fetch(`/UDPT-QLBN/Patient/api_getPatient/${encodeURIComponent(identifier)}`)
      .then((response) => response.json())
      .then((data) => {
        document.getElementById("patientDetailLoading").classList.add("d-none");

        if (data.success && data.data) {
          currentPatient = data.data;
          displayPatientDetails(data.data);
          document
            .getElementById("patientDetailContent")
            .classList.remove("d-none");
        } else {
          showError("Không thể tải thông tin bệnh nhân");
        }
      })
      .catch((error) => {
        console.error("Error loading patient details:", error);
        document.getElementById("patientDetailLoading").classList.add("d-none");
        showError("Lỗi khi tải thông tin bệnh nhân");
      });
  }

  /**
   * Display patient details in modal
   */
  function displayPatientDetails(patient) {
    const viewContent = document.getElementById("viewModeContent");
    viewContent.innerHTML = `
      <div class="row">
        <div class="col-md-6">
          <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin cơ bản</h6>
          <table class="table table-sm table-borderless">
            <tr><td class="font-weight-bold" width="40%">Mã bệnh nhân:</td><td>${
              patient.id || "N/A"
            }</td></tr>
            <tr><td class="font-weight-bold">Tên bệnh nhân:</td><td>${
              patient.fullname || "N/A"
            }</td></tr>
            <tr><td class="font-weight-bold">CMND/CCCD:</td><td>${
              patient.identity_card || "N/A"
            }</td></tr>
            <tr><td class="font-weight-bold">Ngày sinh:</td><td>${
              patient.birth_date || "N/A"
            }</td></tr>
            <tr><td class="font-weight-bold">Giới tính:</td><td>${
              patient.gender || "N/A"
            }</td></tr>
          </table>
        </div>
        <div class="col-md-6">
          <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin liên hệ</h6>
          <table class="table table-sm table-borderless">
            <tr><td class="font-weight-bold" width="40%">Email:</td><td>${
              patient.email || "N/A"
            }</td></tr>
            <tr><td class="font-weight-bold">Điện thoại:</td><td>${
              patient.phone_number || "N/A"
            }</td></tr>
            <tr><td class="font-weight-bold">Địa chỉ:</td><td>${
              patient.address || "N/A"
            }</td></tr>
          </table>
        </div>
      </div>
      <div class="row mt-3">
        <div class="col-12">
          <h6 class="font-weight-bold mb-3 border-bottom pb-2">Thông tin y tế</h6>
          <table class="table table-sm table-borderless">
            <tr><td class="font-weight-bold" width="20%">Tiền sử bệnh:</td><td>${
              patient.medical_history || "Không có"
            }</td></tr>
          </table>
        </div>
      </div>
    `;

    // Setup edit mode button
    setupEditButton();
  }

  /**
   * Setup edit mode button
   */
  function setupEditButton() {
    const editBtn = document.getElementById("switchToEditModeBtn");
    if (editBtn) {
      // Remove existing event listeners to prevent duplicates
      editBtn.replaceWith(editBtn.cloneNode(true));
      const newEditBtn = document.getElementById("switchToEditModeBtn");

      newEditBtn.addEventListener("click", function () {
        switchToEditMode();
      });
    }

    // Setup cancel edit button
    const cancelBtn = document.getElementById("cancelEditBtn");
    if (cancelBtn) {
      // Remove existing event listeners to prevent duplicates
      cancelBtn.replaceWith(cancelBtn.cloneNode(true));
      const newCancelBtn = document.getElementById("cancelEditBtn");

      newCancelBtn.addEventListener("click", function () {
        switchToViewMode();
      });
    }

    // Setup save changes button
    const saveBtn = document.getElementById("savePatientChangesBtn");
    if (saveBtn) {
      // Remove existing event listeners to prevent duplicates
      saveBtn.replaceWith(saveBtn.cloneNode(true));
      const newSaveBtn = document.getElementById("savePatientChangesBtn");

      newSaveBtn.addEventListener("click", function () {
        savePatientChanges();
      });
    }
  }

  /**
   * Switch to edit mode
   */
  function switchToEditMode() {
    if (!currentPatient) return;

    // Hide view mode and show edit mode
    document.getElementById("viewModeContent").classList.add("d-none");
    document.getElementById("editModeContent").classList.remove("d-none");
    document.getElementById("viewModeButtons").classList.add("d-none");
    document.getElementById("editModeButtons").classList.remove("d-none");

    // Populate edit form with current patient data
    populateEditForm(currentPatient);
  }

  /**
   * Switch to view mode
   */
  function switchToViewMode() {
    // Show view mode and hide edit mode
    document.getElementById("viewModeContent").classList.remove("d-none");
    document.getElementById("editModeContent").classList.add("d-none");
    document.getElementById("viewModeButtons").classList.remove("d-none");
    document.getElementById("editModeButtons").classList.add("d-none");
  }

  /**
   * Populate edit form with patient data
   */
  function populateEditForm(patient) {
    // Set hidden fields
    document.getElementById("editPatientId").value = patient.id || "";
    document.getElementById("editPatientIdentityCard").value =
      patient.identity_card || "";

    // Set readonly fields in edit view
    document.getElementById("editViewPatientId").textContent =
      patient.id || "N/A";
    document.getElementById("editViewPatientName").textContent =
      patient.fullname || "N/A";
    document.getElementById("editViewPatientDob").textContent =
      patient.birth_date || "N/A";
    document.getElementById("editViewPatientGender").textContent =
      patient.gender || "N/A";
    document.getElementById("editViewPatientIdentityCard").textContent =
      patient.identity_card || "N/A";

    // Set editable fields
    document.getElementById("editPatientEmail").value = patient.email || "";
    document.getElementById("editPatientPhone").value =
      patient.phone_number || "";
    document.getElementById("editPatientAddress").value = patient.address || "";
    document.getElementById("editPatientMedicalHistory").value =
      patient.medical_history || "";
  }

  /**
   * Save patient changes
   */
  function savePatientChanges() {
    if (!currentPatient) return;

    const form = document.getElementById("editPatientForm");
    if (!form.checkValidity()) {
      form.classList.add("was-validated");
      return;
    }

    const updateData = {
      email: document.getElementById("editPatientEmail").value.trim(),
      phone_number: document.getElementById("editPatientPhone").value.trim(),
      address: document.getElementById("editPatientAddress").value.trim(),
      medical_history: document
        .getElementById("editPatientMedicalHistory")
        .value.trim(),
    };

    const saveBtn = document.getElementById("savePatientChangesBtn");
    const originalText = saveBtn.textContent;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
    saveBtn.disabled = true;

    fetch(
      `/UDPT-QLBN/Patient/api_updatePatient/${encodeURIComponent(
        currentPatient.identity_card
      )}`,
      {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(updateData),
      }
    )
      .then((response) => {
        if (!response.ok) {
          return response.json().then((data) => {
            throw new Error(data.message || "Lỗi từ server");
          });
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          // Update current patient data
          currentPatient = { ...currentPatient, ...updateData };

          // Refresh the view mode display
          displayPatientDetails(currentPatient);

          // Switch back to view mode
          switchToViewMode();

          // Reload patient list to reflect changes
          loadPatients();

          // Show success message
          showMessage("Cập nhật thông tin bệnh nhân thành công!", "success");
        } else {
          showError(
            data.message ||
              "Không thể cập nhật thông tin bệnh nhân. Vui lòng thử lại."
          );
        }
      })
      .catch((error) => {
        console.error("Error updating patient:", error);
        showError(
          error.message ||
            "Lỗi khi cập nhật thông tin bệnh nhân. Vui lòng thử lại."
        );
      })
      .finally(() => {
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
      });
  }

  /**
   * Add new patient
   */
  function addPatient() {
    const formData = new FormData(document.getElementById("addPatientForm"));
    const patientData = Object.fromEntries(formData.entries());

    // Validate required fields
    if (!patientData.fullname || !patientData.identity_card) {
      showError("Vui lòng điền đầy đủ thông tin bắt buộc");
      return;
    }

    const saveBtn = document.getElementById("savePatientBtn");
    const originalText = saveBtn.textContent;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
    saveBtn.disabled = true;

    fetch("/UDPT-QLBN/Patient/api_addPatient", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(patientData),
    })
      .then((response) => {
        if (!response.ok) {
          return response.json().then((data) => {
            throw new Error(data.message || "Lỗi từ server");
          });
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          // Close modal and reset form (Bootstrap 5)
          const modal = bootstrap.Modal.getInstance(
            document.getElementById("addPatientModal")
          );
          if (modal) {
            modal.hide();
          }
          document.getElementById("addPatientForm").reset();
          document
            .getElementById("addPatientForm")
            .classList.remove("was-validated");

          // Reload patient list
          loadPatients();

          // Show success message
          showMessage("Thêm bệnh nhân thành công!", "success");
        } else {
          showError(
            data.message || "Không thể thêm bệnh nhân. Vui lòng thử lại."
          );
        }
      })
      .catch((error) => {
        console.error("Error adding patient:", error);
        showError(error.message || "Lỗi khi thêm bệnh nhân. Vui lòng thử lại.");
      })
      .finally(() => {
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
      });
  }

  /**
   * Update pagination info and buttons
   */
  function updatePagination(paginationData) {
    if (!paginationData) return;

    const { page, limit, total, total_pages } = paginationData;

    // Update info text
    const start = Math.min((page - 1) * limit + 1, total);
    const end = Math.min(page * limit, total);
    paginationInfo.textContent = `Hiển thị ${start}-${end} của ${total} bệnh nhân`;

    // Update pagination buttons
    prevPageBtn.parentElement.classList.toggle("disabled", page <= 1);
    nextPageBtn.parentElement.classList.toggle("disabled", page >= total_pages);

    currentPage = page;

    // Add click handlers for pagination
    prevPageBtn.onclick = (e) => {
      e.preventDefault();
      if (currentPage > 1) {
        currentPage--;
        loadPatients();
      }
    };

    nextPageBtn.onclick = (e) => {
      e.preventDefault();
      if (currentPage < total_pages) {
        currentPage++;
        loadPatients();
      }
    };
  }

  /**
   * Show/hide loading indicator
   */
  function showLoading(show) {
    if (loadingIndicator) {
      loadingIndicator.classList.toggle("d-none", !show);
    }
  }

  /**
   * Show success message
   */
  function showMessage(message, type = "info") {
    const alertClass =
      {
        success: "alert-success",
        error: "alert-danger",
        warning: "alert-warning",
        info: "alert-info",
      }[type] || "alert-info";

    const alertDiv = document.createElement("div");
    alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText =
      "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
    alertDiv.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
      if (alertDiv.parentNode) {
        alertDiv.remove();
      }
    }, 5000);
  }

  /**
   * Show error message
   */
  function showError(message) {
    showMessage(message, "error");
  }
});
