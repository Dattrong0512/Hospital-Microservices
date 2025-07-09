document.addEventListener("DOMContentLoaded", function () {
  // Variables to track current state
  let patients = [];
  let currentPage = 1;
  let itemsPerPage = 5;
  let sortField = "id";
  let sortDirection = "asc";
  let searchTerm = "";
  let totalItems = 0;
  let currentPatient = null;
  // DOM Elements
  const tableBody = document.querySelector("#patientsTable tbody");
  const searchInput = document.getElementById("searchInput");
  const paginationInfo = document.getElementById("paginationInfo");
  const pagination = document.getElementById("pagination");
  const prevPageBtn = document.getElementById("prevPage");
  const nextPageBtn = document.getElementById("nextPage");
  const loadingIndicator = document.getElementById("loadingIndicator");

  initializeTableHeaders();

  // Load patients on page load
  loadPatients();

  // Event Listeners
  searchInput.addEventListener("input", function () {
    searchTerm = this.value;
    currentPage = 1; // Reset to first page when searching
    loadPatients();
  });

  document.querySelectorAll(".sortable").forEach((header) => {
    header.addEventListener("click", function () {
      const field = this.dataset.sort;

      if (sortField === field) {
        sortDirection = sortDirection === "asc" ? "desc" : "asc";
      } else {
        sortField = field;
        sortDirection = "asc";
      }

      // Update the sort icons
      document.querySelectorAll(".sortable i").forEach((icon) => {
        icon.className = "fas fa-sort text-muted ml-1";
      });

      const icon = this.querySelector("i");
      icon.className = `fas fa-sort-${
        sortDirection === "asc" ? "up" : "down"
      } text-primary ml-1`;

      loadPatients();
    });
  });

  prevPageBtn.addEventListener("click", function (e) {
    e.preventDefault();
    if (currentPage > 1) {
      currentPage--;
      loadPatients();
    }
  });

  nextPageBtn.addEventListener("click", function (e) {
    e.preventDefault();
    const totalPages = Math.ceil(totalItems / itemsPerPage);

    if (currentPage < totalPages) {
      currentPage++;
      loadPatients();
    }
  });

  document
    .getElementById("savePatientBtn")
    .addEventListener("click", function () {
      const form = document.getElementById("addPatientForm");

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const patientData = {
        fullname: document.getElementById("patientName").value.trim(),
        email: document.getElementById("patientEmail").value.trim(),
        phone_number: document.getElementById("patientPhone").value.trim(),
        birth_date: document.getElementById("patientDob").value.trim(),
        gender: document.getElementById("patientGender").value.trim(),
        address: document.getElementById("patientAddress").value.trim(),
        identity_card: document
          .getElementById("patientIdentityCard")
          .value.trim(),
      };

      addPatient(patientData);
    });

  document
    .getElementById("confirmDeleteBtn")
    .addEventListener("click", function () {
      const patientId = document.getElementById("deletePatientId").value;
      deletePatient(patientId);
    });

  document
    .getElementById("switchToEditModeBtn")
    .addEventListener("click", function () {
      switchToEditMode();
    });

  document
    .getElementById("cancelEditBtn")
    .addEventListener("click", function () {
      switchToViewMode();
    });

  document
    .getElementById("savePatientChangesBtn")
    .addEventListener("click", function () {
      savePatientChanges();
    });

  document
    .getElementById("cancelEditBtn")
    .addEventListener("click", function () {
      // Chuyển từ chế độ chỉnh sửa về chế độ xem
      switchToViewMode();

      // Log để debug
      console.log("Hủy chỉnh sửa, chuyển về chế độ xem");
    });

  // Functions

  function initializeTableHeaders() {
    const thead = document.querySelector("#patientsTable thead");
    if (thead && !thead.innerHTML.trim()) {
      thead.innerHTML = `
        <tr>
          <th class="sortable" data-sort="id">Mã bệnh nhân <i class="fas fa-sort text-muted ml-1"></i></th>
          <th class="sortable" data-sort="fullname">Tên bệnh nhân <i class="fas fa-sort text-muted ml-1"></i></th>
          <th class="sortable" data-sort="email">Email <i class="fas fa-sort text-muted ml-1"></i></th>
          <th class="sortable" data-sort="phone_number">Số điện thoại <i class="fas fa-sort text-muted ml-1"></i></th>
          <th>Thao tác</th>
        </tr>
      `;
    }
  }

  /**
   * Load patients from the API
   */
  function loadPatients() {
    showLoading();
    fetch(
      `/UDPT-QLBN/Patient/api_getPatients?page=${currentPage}&limit=${itemsPerPage}`
    )
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          patients = data.data;
          // Lấy thông tin phân trang từ server
          if (data.pagination) {
            totalItems = data.pagination.total;
            currentPage = data.pagination.page;
            itemsPerPage = data.pagination.limit;
          } else {
            totalItems = patients.length;
          }
          renderPatients();
        } else {
          showError(data.message);
        }
      })
      .catch((error) => {
        showError("Error loading patients: " + error.message);
      })
      .finally(() => {
        hideLoading();
      });
  }

  /**
   * Render patients to the table
   */
  function renderPatients() {
    try {
      console.log("Rendering", patients.length, "patients");

      // Clear the table body
      tableBody.innerHTML = "";

      // Render patients trực tiếp từ server (không cần filter hay paginate client-side nữa)
      if (patients.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                    <h5 class="font-weight-normal text-muted">Không tìm thấy bệnh nhân nào</h5>
                    ${
                      searchTerm
                        ? `<p class="text-muted">Từ khóa tìm kiếm: "${searchTerm}"</p>`
                        : ""
                    }
                </div>
            </td></tr>`;
      } else {
        // Render tất cả patients mà server đã trả về (đã được phân trang)
        patients.forEach((patient) => {
          const identityCard = patient.identity_card
            ? patient.identity_card
            : "";

          const tr = document.createElement("tr");
          tr.innerHTML = `
                    <td class="align-middle">${patient.id || ""}</td>
                    <td class="align-middle">
                        <div class="d-flex align-items-center">
                            
                            <div>
                                <strong>${patient.fullname || ""}</strong>
                                <div class="small text-muted">${
                                  patient.gender || ""
                                } - ${
            formatDate(patient.birth_date) || ""
          }</div>
                            </div>
                        </div>
                    </td> 
                    <td class="align-middle">
                        <div>${
                          patient.email ||
                          '<span class="text-muted">Chưa có</span>'
                        }</div>
                        <div class="small text-muted">${
                          patient.identity_card || ""
                        }</div>
                    </td>
                    <td class="align-middle">${
                      patient.phone_number ||
                      '<span class="text-muted">Chưa có</span>'
                    }</td> 
                    <td class="align-middle">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-info view-patient-btn" 
                                    data-id="${patient.id || ""}"
                                    data-identity-card="${identityCard}"
                                    title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-danger delete-patient-btn" 
                                    data-id="${patient.id || ""}" 
                                    data-name="${patient.fullname || ""}"
                                    title="Xóa bệnh nhân">
                                <i class="fas fa-trash"></i>
                            </button> 
                        </div>
                    </td>
                `;
          tableBody.appendChild(tr);
        });

        // Add event listeners to buttons
        setupViewButtons();
        setupDeleteButtons();
      }

      // Update pagination info
      updatePaginationInfo();
      renderPagination();
    } catch (error) {
      console.error("Error rendering patients:", error);
      tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-danger">
            <i class="fas fa-exclamation-circle mr-2"></i> Lỗi hiển thị dữ liệu: ${error.message}
        </td></tr>`;
    }
  }

  /**
   * Filter patients based on search term
   */
  function filterPatients() {
    if (!searchTerm) return patients;
    const searchTermLower = searchTerm.toLowerCase();
    return patients.filter(
      (patient) =>
        patient.id.toString().includes(searchTermLower) ||
        (patient.fullname &&
          patient.fullname.toLowerCase().includes(searchTermLower)) ||
        (patient.email &&
          patient.email.toLowerCase().includes(searchTermLower)) ||
        (patient.identity_card &&
          patient.identity_card.toLowerCase().includes(searchTermLower))
    );
  }

  function updatePatientDetailField(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
      element.textContent = value || "Không có";
    } else {
      console.warn(
        `Element with ID ${elementId} not found in modal: ${elementId}`
      );
    }
  }

  /**
   * Sort patients based on sort field and direction
   */
  function sortPatients(patientsList) {
    return [...patientsList].sort((a, b) => {
      // Kiểm tra xem field có tồn tại trong object không
      const hasFieldA = sortField in a;
      const hasFieldB = sortField in b;

      // Xử lý trường hợp field không tồn tại
      if (!hasFieldA && !hasFieldB) return 0;
      if (!hasFieldA) return sortDirection === "asc" ? -1 : 1;
      if (!hasFieldB) return sortDirection === "asc" ? 1 : -1;

      let valueA = a[sortField];
      let valueB = b[sortField];

      // Handle null/undefined values
      if (valueA === null || valueA === undefined) valueA = "";
      if (valueB === null || valueB === undefined) valueB = "";

      // Numeric comparison for ID or numeric fields
      if (
        sortField === "id" ||
        (!isNaN(Number(valueA)) && !isNaN(Number(valueB)))
      ) {
        return sortDirection === "asc"
          ? Number(valueA) - Number(valueB)
          : Number(valueB) - Number(valueA);
      }

      // String comparison for other fields - convert to string first
      const strA = String(valueA);
      const strB = String(valueB);

      try {
        return sortDirection === "asc"
          ? strA.localeCompare(strB)
          : strB.localeCompare(strA);
      } catch (error) {
        console.error("Sort error:", error, "Values:", {
          valueA,
          valueB,
          sortField,
        });
        // Fallback comparison if localeCompare fails
        return sortDirection === "asc"
          ? strA < strB
            ? -1
            : strA > strB
            ? 1
            : 0
          : strB < strA
          ? -1
          : strB > strA
          ? 1
          : 0;
      }
    });
  }

  /**
   * Paginate patients
   */
  function paginatePatients(patientsList) {
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    return patientsList.slice(startIndex, endIndex);
  }

  /**
   * Update pagination info text
   */
  function updatePaginationInfo() {
    const startItem =
      totalItems === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
    const endItem = Math.min(currentPage * itemsPerPage, totalItems);

    paginationInfo.textContent = `Đang xem ${startItem}-${endItem} trên tổng ${totalItems} bệnh nhân`;

    if (searchTerm) {
      const searchInfo = document.createElement("div");
      searchInfo.className = "small text-muted mt-1";
      searchInfo.textContent = `Kết quả tìm kiếm cho: "${searchTerm}"`;
      paginationInfo.appendChild(searchInfo);
    }
  }

  /**
   * Render pagination controls
   */
  function renderPagination() {
    try {
      const totalPages = Math.ceil(totalItems / itemsPerPage);
      const pagination = document.getElementById("pagination");

      if (!pagination) {
        console.error("Pagination element not found");
        return;
      }

      // Clear existing pagination
      pagination.innerHTML = "";

      // Add prev button
      const prevLi = document.createElement("li");
      prevLi.className = `page-item ${currentPage === 1 ? "disabled" : ""}`;

      const prevLink = document.createElement("a");
      prevLink.className = "page-link";
      prevLink.href = "#";
      prevLink.id = "prevPage";
      prevLink.innerHTML = '<i class="fas fa-chevron-left"></i> Trước';
      prevLink.addEventListener("click", function (e) {
        e.preventDefault();
        if (currentPage > 1) {
          currentPage--;
          loadPatients(); // Gọi loadPatients() để load lại dữ liệu với trang mới
        }
      });

      prevLi.appendChild(prevLink);
      pagination.appendChild(prevLi);

      // Add page numbers - chỉ hiển thị tối đa 5 trang
      const maxPagesToShow = 5;
      let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
      let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

      // Điều chỉnh nếu không đủ trang ở cuối
      if (endPage - startPage + 1 < maxPagesToShow && startPage > 1) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
      }

      if (startPage > 1) {
        // Thêm nút trang đầu tiên
        const firstLi = document.createElement("li");
        firstLi.className = "page-item";
        const firstA = document.createElement("a");
        firstA.className = "page-link";
        firstA.href = "#";
        firstA.textContent = "1";
        firstA.addEventListener("click", function (e) {
          e.preventDefault();
          currentPage = 1;
          loadPatients(); // Quan trọng: phải gọi loadPatients()
        });
        firstLi.appendChild(firstA);
        pagination.appendChild(firstLi);

        // Thêm dấu "..." nếu không bắt đầu từ trang 2
        if (startPage > 2) {
          const ellipsisLi = document.createElement("li");
          ellipsisLi.className = "page-item disabled";
          const ellipsisA = document.createElement("a");
          ellipsisA.className = "page-link";
          ellipsisA.innerHTML = "&hellip;";
          ellipsisLi.appendChild(ellipsisA);
          pagination.appendChild(ellipsisLi);
        }
      }

      // Hiển thị các trang trong khoảng đã tính
      for (let i = startPage; i <= endPage; i++) {
        const li = document.createElement("li");
        li.className = `page-item ${currentPage === i ? "active" : ""}`;

        const a = document.createElement("a");
        a.className = "page-link";
        a.href = "#";
        a.textContent = i;
        a.addEventListener("click", function (e) {
          e.preventDefault();
          currentPage = i;
          loadPatients(); // Gọi loadPatients() để load lại dữ liệu với trang mới
        });

        li.appendChild(a);
        pagination.appendChild(li);
      }

      // Thêm dấu "..." và nút trang cuối nếu chưa hiển thị hết
      if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
          const ellipsisLi = document.createElement("li");
          ellipsisLi.className = "page-item disabled";
          const ellipsisA = document.createElement("a");
          ellipsisA.className = "page-link";
          ellipsisA.innerHTML = "&hellip;";
          ellipsisLi.appendChild(ellipsisA);
          pagination.appendChild(ellipsisLi);
        }

        // Thêm nút trang cuối cùng
        const lastLi = document.createElement("li");
        lastLi.className = "page-item";
        const lastA = document.createElement("a");
        lastA.className = "page-link";
        lastA.href = "#";
        lastA.textContent = totalPages;
        lastA.addEventListener("click", function (e) {
          e.preventDefault();
          currentPage = totalPages;
          loadPatients(); // Quan trọng: phải gọi loadPatients()
        });
        lastLi.appendChild(lastA);
        pagination.appendChild(lastLi);
      }

      // Add next button
      const nextLi = document.createElement("li");
      nextLi.className = `page-item ${
        currentPage >= totalPages ? "disabled" : ""
      }`;

      const nextLink = document.createElement("a");
      nextLink.className = "page-link";
      nextLink.href = "#";
      nextLink.id = "nextPage";
      nextLink.innerHTML = 'Sau <i class="fas fa-chevron-right"></i>';
      nextLink.addEventListener("click", function (e) {
        e.preventDefault();
        if (currentPage < totalPages) {
          currentPage++;
          loadPatients(); // Quan trọng: phải gọi loadPatients() với trang mới
        }
      });

      nextLi.appendChild(nextLink);
      pagination.appendChild(nextLi);
    } catch (error) {
      console.error("Error rendering pagination:", error);
    }
  }

  /**
   * Setup view patient buttons
   */
  function setupViewButtons() {
    const viewButtons = document.querySelectorAll(".view-patient-btn");
    viewButtons.forEach((btn) => {
      btn.addEventListener("click", function () {
        const patientId = this.dataset.id;
        const identityCard = this.dataset.identityCard;

        console.log("Button clicked with:", { patientId, identityCard });

        // Nếu có identity_card thì dùng nó, không thì dùng id
        if (identityCard) {
          viewPatientDetails(patientId, identityCard);
        } else {
          // Trường hợp không có identity_card, tìm thông tin identity_card từ mảng bệnh nhân
          const patient = patients.find((p) => p.id == patientId);
          if (patient && patient.identity_card) {
            viewPatientDetails(patientId, patient.identity_card);
          } else {
            viewPatientDetails(patientId);
          }
        }
      });
    });
  }

  /**
   * Setup delete patient buttons
   */
  function setupDeleteButtons() {
    const deleteButtons = document.querySelectorAll(".delete-patient-btn");
    deleteButtons.forEach((btn) => {
      btn.addEventListener("click", function () {
        const patientId = this.dataset.id;
        const patientName = this.dataset.name;

        document.getElementById("deletePatientName").textContent = patientName;
        document.getElementById("deletePatientId").value = patientId;

        $("#deletePatientModal").modal("show");
      });
    });
  }

  /**
   * View patient details
   */
  function viewPatientDetails(patientId, identityCard = null) {
    console.log(
      "Viewing patient details for ID:",
      patientId,
      "Identity card:",
      identityCard
    );

    // Show the modal
    $("#viewPatientModal").modal("show");

    // Show loading, hide content
    const loadingElement = document.getElementById("patientDetailLoading");
    const contentElement = document.getElementById("patientDetailContent");

    if (loadingElement) loadingElement.classList.remove("d-none");
    if (contentElement) contentElement.classList.add("d-none");

    // Ưu tiên dùng identity_card vì API chỉ hỗ trợ tìm theo identity_card
    const endpoint = `/UDPT-QLBN/Patient/api_getPatient/${
      identityCard || patientId
    }`;

    console.log("Using endpoint:", endpoint);

    // Reset to view mode
    switchToViewMode();

    // Fetch patient details
    fetch(endpoint)
      .then((response) => {
        console.log(
          "Patient details API response:",
          response.status,
          response.statusText
        );

        if (!response.ok) {
          return response.text().then((text) => {
            console.error("Error response:", text);
            throw new Error(`Network response was not ok (${response.status})`);
          });
        }
        return response.json();
      })
      .then((data) => {
        console.log("Patient data received:", data);

        if (data.success && data.data) {
          currentPatient = data.data;
          console.log("Processing patient:", currentPatient);
          let formattedAddress = currentPatient.address || "";
          if (formattedAddress.includes("\n")) {
            formattedAddress = formattedAddress.replace(/\n/g, "<br>");
            updatePatientDetailFieldHTML(
              "viewPatientAddress",
              formattedAddress
            );
          } else {
            updatePatientDetailField("viewPatientAddress", formattedAddress);
          }

          let formattedHistory = currentPatient.medical_history || "Không có";
          if (formattedHistory.includes("\n")) {
            formattedHistory = formattedHistory.replace(/\n/g, "<br>");
            updatePatientDetailFieldHTML(
              "viewPatientMedicalHistory",
              formattedHistory
            );
          } else {
            updatePatientDetailField(
              "viewPatientMedicalHistory",
              formattedHistory
            );
          }

          // Update modal content with matched field names
          updatePatientDetailField("viewPatientId", currentPatient.id);
          updatePatientDetailField("viewPatientName", currentPatient.fullname);
          updatePatientDetailField("viewPatientEmail", currentPatient.email);
          updatePatientDetailField(
            "viewPatientPhone",
            currentPatient.phone_number
          );
          updatePatientDetailField(
            "viewPatientAddress",
            currentPatient.address
          );
          updatePatientDetailField(
            "viewPatientDob",
            formatDate(currentPatient.birth_date)
          );
          updatePatientDetailField("viewPatientGender", currentPatient.gender);
          updatePatientDetailField(
            "viewPatientIdentityCard",
            currentPatient.identity_card
          );
          updatePatientDetailField(
            "viewPatientMedicalHistory",
            currentPatient.medical_history || "Không có"
          );

          // Also populate edit form fields
          document.getElementById("editViewPatientId").textContent =
            currentPatient.id || "";
          document.getElementById("editViewPatientName").textContent =
            currentPatient.fullname || "";
          document.getElementById("editViewPatientDob").textContent =
            formatDate(currentPatient.birth_date) || "";
          document.getElementById("editViewPatientGender").textContent =
            currentPatient.gender || "";
          document.getElementById("editViewPatientIdentityCard").textContent =
            currentPatient.identity_card || "";

          document.getElementById("editPatientId").value =
            currentPatient.id || "";
          document.getElementById("editPatientIdentityCard").value =
            currentPatient.identity_card || "";
          document.getElementById("editPatientEmail").value =
            currentPatient.email || "";
          document.getElementById("editPatientPhone").value =
            currentPatient.phone_number || "";
          document.getElementById("editPatientAddress").value =
            currentPatient.address || "";
          document.getElementById("editPatientMedicalHistory").value =
            currentPatient.medical_history || "";

          // Show content, hide loading
          if (loadingElement) loadingElement.classList.add("d-none");
          if (contentElement) contentElement.classList.remove("d-none");
        } else {
          throw new Error(data.message || "Không thể tải thông tin bệnh nhân");
        }
      })
      .catch((error) => {
        console.error("Error loading patient details:", error);
        showError("Error loading patient details: " + error.message);
        $("#viewPatientModal").modal("hide");
      });
  }

  function formatDate(dateString) {
    if (!dateString) return "";

    // Handle different date formats (yyyy-mm-dd or dd-mm-yyyy)
    let parts;
    if (dateString.includes("-")) {
      parts = dateString.split("-");
    } else if (dateString.includes("/")) {
      parts = dateString.split("/");
    } else {
      return dateString; // Return as-is if format is unknown
    }

    // Check if the first part is a 4-digit year (yyyy-mm-dd)
    if (parts[0].length === 4) {
      const date = new Date(dateString);
      if (!isNaN(date.getTime())) {
        return new Intl.DateTimeFormat("vi-VN").format(date);
      }
    }

    // Already in dd-mm-yyyy format
    return dateString;
  }

  /**
   * Add a new patient
   */
  function addPatient(patientData) {
    // Xác thực dữ liệu đầu vào
    if (!patientData.fullname || patientData.fullname.trim() === "") {
      showError("Vui lòng nhập tên bệnh nhân");
      return;
    }

    // Validate email format if provided
    if (patientData.email && patientData.email.trim() !== "") {
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(patientData.email)) {
        showError("Email không hợp lệ");
        return;
      }
    }

    // Validate phone number format if provided
    if (patientData.phone_number && patientData.phone_number.trim() !== "") {
      const phonePattern = /(84|0[3|5|7|8|9])+([0-9]{8})\b/;
      if (!phonePattern.test(patientData.phone_number)) {
        showError("Số điện thoại không hợp lệ");
        return;
      }
    }

    // Make sure data matches the API field naming convention
    const apiData = {
      fullname: patientData.fullname,
      email: patientData.email || null,
      phone_number: patientData.phone_number || null,
      birth_date: patientData.birth_date || null,
      gender: patientData.gender || null,
      address: patientData.address || null,
      identity_card: patientData.identity_card || null,
      medical_history: patientData.medical_history || null,
    };

    // Show loading state on button
    const saveBtn = document.getElementById("savePatientBtn");
    const originalText = saveBtn.textContent;
    saveBtn.innerHTML =
      '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Đang lưu...';
    saveBtn.disabled = true;

    // Send request to server
    fetch("/UDPT-QLBN/Patient/api_addPatient", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(apiData),
      credentials: "include", // Include auth cookies if any
    })
      .then((response) => {
        if (!response.ok) {
          // Try to parse error response as JSON if possible
          const contentType = response.headers.get("content-type");
          if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json().then((data) => {
              throw new Error(data.message || "Lỗi từ server");
            });
          }
          throw new Error(`Mã lỗi: ${response.status}`);
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

          // Reload patient list
          loadPatients();

          // Show success message
          showMessage("Thêm bệnh nhân thành công!", "success");
        } else {
          // Show error from server
          showError(
            data.message || "Không thể thêm bệnh nhân. Vui lòng thử lại."
          );
        }
      })
      .catch((error) => {
        // Handle different error cases
        if (
          error.message.includes("Failed to fetch") ||
          error.message.includes("NetworkError")
        ) {
          showError(
            "Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng."
          );
        } else if (error.message.includes("Unexpected token")) {
          showError("Lỗi định dạng dữ liệu từ server");
        } else {
          showError("Lỗi thêm bệnh nhân: " + error.message);
        }
      })
      .finally(() => {
        // Restore button state
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
      });
  }

  /**
   * Switch to edit mode
   */
  function switchToEditMode() {
    document.getElementById("viewModeContent").classList.add("d-none");
    document.getElementById("editModeContent").classList.remove("d-none");
    document.getElementById("viewModeButtons").classList.add("d-none");
    document.getElementById("editModeButtons").classList.remove("d-none");
  }

  /**
   * Switch to view mode
   */
  function switchToViewMode() {
    document.getElementById("viewModeContent").classList.remove("d-none");
    document.getElementById("editModeContent").classList.add("d-none");
    document.getElementById("viewModeButtons").classList.remove("d-none");
    document.getElementById("editModeButtons").classList.add("d-none");
  }

  /**
   * Delete a patient
   */
  function deletePatient(patientId) {
    // Show loading indicator
    const deleteBtn = document.getElementById("confirmDeleteBtn");
    const originalText = deleteBtn.textContent;
    deleteBtn.innerHTML =
      '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Đang xóa...';
    deleteBtn.disabled = true;

    fetch(`/UDPT-QLBN/Patient/api_deletePatient/${patientId}`, {
      method: "DELETE",
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          // Close modal
          $("#deletePatientModal").modal("hide");

          // Reload patients
          loadPatients();

          // Show success message
          showMessage("Xóa bệnh nhân thành công!", "success");
        } else {
          showError(data.message);
        }
      })
      .catch((error) => {
        showError("Error deleting patient: " + error.message);
      })
      .finally(() => {
        // Reset button
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
      });
  }

  /**
   * Show loading indicator
   */
  function showLoading() {
    tableBody.innerHTML = "";
    loadingIndicator.classList.remove("d-none");
  }

  /**
   * Hide loading indicator
   */
  function hideLoading() {
    loadingIndicator.classList.add("d-none");
  }

  /**
   * Show error message
   */
  function showError(message) {
    // Create alert element
    const alertDiv = document.createElement("div");
    alertDiv.className = "alert alert-danger alert-dismissible fade show";
    alertDiv.innerHTML = `
            <i class="fas fa-exclamation-circle mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;

    // Add to document
    document.querySelector(".page-header").after(alertDiv);

    // Auto dismiss after 5 seconds
    setTimeout(() => {
      alertDiv.classList.remove("show");
      setTimeout(() => alertDiv.remove(), 150);
    }, 5000);
  }

  /**
   * Show message
   */
  function showMessage(message, type = "info") {
    // Create alert element
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
            <i class="fas fa-${
              type === "success" ? "check-circle" : "info-circle"
            } mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;

    // Add to document
    document.querySelector(".page-header").after(alertDiv);

    // Auto dismiss after 5 seconds
    setTimeout(() => {
      alertDiv.classList.remove("show");
      setTimeout(() => alertDiv.remove(), 150);
    }, 5000);
  }
  document.querySelectorAll(".modal .close").forEach((button) => {
    button.addEventListener("click", function () {
      const modalId = this.closest(".modal").id;
      $(`#${modalId}`).modal("hide");
    });
  });
  document
    .querySelectorAll(".modal .modal-footer .btn-secondary")
    .forEach((button) => {
      button.addEventListener("click", function () {
        const modalId = this.closest(".modal").id;
        $(`#${modalId}`).modal("hide");
      });
    });

  /**
   * Save patient changes
   */
  function savePatientChanges() {
    // Get the edited values from form
    const updatedData = {
      email: document.getElementById("editPatientEmail").value.trim(),
      phone_number: document.getElementById("editPatientPhone").value.trim(),
      address: document.getElementById("editPatientAddress").value.trim(),
      medical_history: document
        .getElementById("editPatientMedicalHistory")
        .value.trim(),
    };

    // Validate phone number if provided
    if (updatedData.phone_number) {
      const phonePattern = /(84|0[3|5|7|8|9])+([0-9]{8})\b/;
      if (!phonePattern.test(updatedData.phone_number)) {
        showError("Số điện thoại không hợp lệ");
        return;
      }
    }

    // Validate email if provided
    if (updatedData.email) {
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(updatedData.email)) {
        showError("Email không hợp lệ");
        return;
      }
    }

    // Get patient identity card for API call
    const identityCard = document.getElementById(
      "editPatientIdentityCard"
    ).value;
    const patientId = document.getElementById("editPatientId").value;

    if (!identityCard) {
      showError("Không tìm thấy thông tin CMND/CCCD của bệnh nhân");
      return;
    }

    // Show loading state on button
    const saveBtn = document.getElementById("savePatientChangesBtn");
    const originalText = saveBtn.textContent;
    saveBtn.innerHTML =
      '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Đang lưu...';
    saveBtn.disabled = true;

    // Send request to server - using the identity card as identifier
    fetch(`/UDPT-QLBN/Patient/api_updatePatient/${identityCard}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(updatedData),
      credentials: "include", // Include auth cookies if any
    })
      .then((response) => {
        if (!response.ok) {
          // Try to parse error response as JSON if possible
          const contentType = response.headers.get("content-type");
          if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json().then((data) => {
              throw new Error(data.message || "Lỗi từ server");
            });
          }
          throw new Error(`Mã lỗi: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          // Update current patient data
          if (data.data) {
            currentPatient = data.data;
          } else {
            // Update current patient with the data we sent
            Object.assign(currentPatient, updatedData);
          }

          // Update the view mode details - format text appropriately for multiline
          updatePatientDetailField("viewPatientEmail", currentPatient.email);
          updatePatientDetailField(
            "viewPatientPhone",
            currentPatient.phone_number
          );

          // Handle address with potential line breaks
          let formattedAddress = currentPatient.address || "";
          if (formattedAddress.includes("\n")) {
            formattedAddress = formattedAddress.replace(/\n/g, "<br>");
          }
          updatePatientDetailFieldHTML("viewPatientAddress", formattedAddress);

          // Handle medical history with potential line breaks
          let formattedHistory = currentPatient.medical_history || "Không có";
          if (formattedHistory.includes("\n")) {
            formattedHistory = formattedHistory.replace(/\n/g, "<br>");
          }
          updatePatientDetailFieldHTML(
            "viewPatientMedicalHistory",
            formattedHistory
          );

          // ===== CẬP NHẬT DỮ LIỆU BỆNH NHÂN TRONG MẢNG =====
          // Tìm và cập nhật bệnh nhân trong mảng patients
          const patientIndex = patients.findIndex(
            (p) => p.id == patientId || p.identity_card === identityCard
          );

          if (patientIndex !== -1) {
            // Cập nhật dữ liệu trong mảng
            patients[patientIndex] = {
              ...patients[patientIndex],
              ...updatedData,
            };

            // Cập nhật hiển thị trong bảng
            updatePatientRowInTable(patients[patientIndex]);

            console.log(
              "Cập nhật bệnh nhân trong mảng:",
              patients[patientIndex]
            );
          }

          // Show success message with styled icon
          showMessage(
            '<i class="fas fa-check-circle mr-1"></i> Cập nhật thông tin bệnh nhân thành công!',
            "success"
          );

          // Switch back to view mode
          switchToViewMode();
        } else {
          // Show error from server
          showError(
            data.message ||
              "Không thể cập nhật thông tin bệnh nhân. Vui lòng thử lại."
          );
        }
      })
      .catch((error) => {
        // Handle different error cases
        showError("Lỗi cập nhật thông tin bệnh nhân: " + error.message);
      })
      .finally(() => {
        // Restore button state
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
      });
  }

  /**
   * Cập nhật thông tin của bệnh nhân trong bảng hiển thị
   * mà không cần tải lại toàn bộ danh sách
   */
  function updatePatientRowInTable(patient) {
    // Tìm hàng trong bảng có data-id hoặc data-identity-card phù hợp
    const patientRows = document.querySelectorAll("#patientsTable tbody tr");

    for (const row of patientRows) {
      const viewBtn = row.querySelector(".view-patient-btn");
      if (!viewBtn) continue;

      const rowId = viewBtn.getAttribute("data-id");
      const rowIdentityCard = viewBtn.getAttribute("data-identity-card");

      // Nếu tìm thấy bệnh nhân cần cập nhật
      if (
        rowId == patient.id ||
        (rowIdentityCard && rowIdentityCard === patient.identity_card)
      ) {
        // Cập nhật các ô có thông tin thay đổi
        const cells = row.querySelectorAll("td");

        // Email là cột thứ 3 (index 2)
        if (cells[2]) cells[2].textContent = patient.email || "";

        // SĐT là cột thứ 4 (index 3)
        if (cells[3]) cells[3].textContent = patient.phone_number || "";

        // Thêm highlight tạm thời để người dùng biết hàng nào vừa được cập nhật
        row.classList.add("table-success");

        // Xóa highlight sau 3 giây
        setTimeout(() => {
          row.classList.remove("table-success");
        }, 3000);

        break;
      }
    }
  }

  /**
   * Hiển thị thông báo thành công được nâng cao
   */
  function showMessage(message, type = "info") {
    // Create alert element
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
      ${message}
  `;

    // Thêm hiệu ứng để thông báo nổi bật hơn
    alertDiv.style.boxShadow = "0 0 10px rgba(0,0,0,0.1)";

    // Add to document
    const pageHeader = document.querySelector(".page-header");
    if (pageHeader) {
      pageHeader.after(alertDiv);
    } else {
      // Fallback nếu không tìm thấy page-header
      document.querySelector(".container").prepend(alertDiv);
    }

    // Auto dismiss after 5 seconds
    setTimeout(() => {
      alertDiv.classList.remove("show");
      setTimeout(() => alertDiv.remove(), 150);
    }, 5000);
  }

  function updatePatientDetailFieldHTML(elementId, htmlContent) {
    const element = document.getElementById(elementId);
    if (element) {
      element.innerHTML = htmlContent || "Không có";
    } else {
      console.warn(`Element with ID ${elementId} not found`);
    }
  }

  function switchToViewMode() {
    // Hiển thị nội dung xem chi tiết, ẩn form chỉnh sửa
    document.getElementById("viewModeContent").classList.remove("d-none");
    document.getElementById("editModeContent").classList.add("d-none");

    // Hiển thị nút ở chế độ xem, ẩn nút ở chế độ chỉnh sửa
    document.getElementById("viewModeButtons").classList.remove("d-none");
    document.getElementById("editModeButtons").classList.add("d-none");

    // Phục hồi giá trị ban đầu của form chỉnh sửa (tránh lưu các thay đổi tạm thời)
    if (currentPatient) {
      document.getElementById("editPatientEmail").value =
        currentPatient.email || "";
      document.getElementById("editPatientPhone").value =
        currentPatient.phone_number || "";
      document.getElementById("editPatientAddress").value =
        currentPatient.address || "";
      document.getElementById("editPatientMedicalHistory").value =
        currentPatient.medical_history || "";
    }
  }

  // Thêm sự kiện kiểm tra CMND/CCCD khi nhập
  document
    .getElementById("patientIdentityCard")
    .addEventListener("blur", function () {
      const identityCard = this.value.trim();
      if (!identityCard) return;

      // Kiểm tra định dạng
      const identityCardPattern = /^(\d{9}|\d{12})$/;
      if (!identityCardPattern.test(identityCard)) {
        document.getElementById("identityCardFeedback").textContent =
          "CMND/CCCD phải có 9 hoặc 12 chữ số";
        this.classList.add("is-invalid");
        return;
      }

      // Kiểm tra trùng lặp
      checkDuplicateIdentityCard(identityCard);
    });

  /**
   * Kiểm tra CMND/CCCD trùng lặp
   */
  function checkDuplicateIdentityCard(identityCard) {
    const inputElement = document.getElementById("patientIdentityCard");
    const feedbackElement = document.getElementById("identityCardFeedback");

    // Kiểm tra trong mảng patients hiện tại thay vì gửi request ngay
    const existingPatient = patients.find(
      (patient) =>
        patient.identity_card && patient.identity_card === identityCard
    );

    if (existingPatient) {
      feedbackElement.textContent = "CMND/CCCD này đã tồn tại trong hệ thống";
      inputElement.classList.add("is-invalid");
      return true;
    } else {
      // Xóa thông báo lỗi nếu không trùng
      feedbackElement.textContent = "";
      inputElement.classList.remove("is-invalid");
      return false;
    }
  }

  // Sửa hàm addPatient để hiển thị lỗi trong form
  function addPatient(patientData) {
    // Reset các thông báo lỗi cũ
    document.querySelectorAll(".invalid-feedback").forEach((el) => {
      el.textContent = "";
    });
    document.querySelectorAll(".is-invalid").forEach((el) => {
      el.classList.remove("is-invalid");
    });

    // Kiểm tra tên
    if (!patientData.fullname || patientData.fullname.trim() === "") {
      const nameInput = document.getElementById("patientName");
      nameInput.classList.add("is-invalid");
      document.querySelector("#patientName + .invalid-feedback").textContent =
        "Vui lòng nhập tên bệnh nhân";
      return;
    }

    // Kiểm tra CMND/CCCD
    const identityCardInput = document.getElementById("patientIdentityCard");
    const identityCardFeedback = document.getElementById(
      "identityCardFeedback"
    );

    if (!patientData.identity_card || patientData.identity_card.trim() === "") {
      identityCardInput.classList.add("is-invalid");
      identityCardFeedback.textContent = "Vui lòng nhập CMND/CCCD";
      return;
    } else {
      const identityCardPattern = /^(\d{9}|\d{12})$/;
      if (!identityCardPattern.test(patientData.identity_card)) {
        identityCardInput.classList.add("is-invalid");
        identityCardFeedback.textContent = "CMND/CCCD phải có 9 hoặc 12 chữ số";
        return;
      }
    }

    // Kiểm tra email
    if (patientData.email && patientData.email.trim() !== "") {
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(patientData.email)) {
        const emailInput = document.getElementById("patientEmail");
        emailInput.classList.add("is-invalid");
        document.querySelector(
          "#patientEmail + .invalid-feedback"
        ).textContent = "Email không hợp lệ";
        return;
      }
    }

    // Kiểm tra SĐT
    if (patientData.phone_number && patientData.phone_number.trim() !== "") {
      const phonePattern = /(84|0[3|5|7|8|9])+([0-9]{8})\b/;
      if (!phonePattern.test(patientData.phone_number)) {
        const phoneInput = document.getElementById("patientPhone");
        phoneInput.classList.add("is-invalid");
        document.querySelector(
          "#patientPhone + .invalid-feedback"
        ).textContent = "Số điện thoại không hợp lệ";
        return;
      }
    }

    // Show loading state on button
    const saveBtn = document.getElementById("savePatientBtn");
    const originalText = saveBtn.textContent;
    saveBtn.innerHTML =
      '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Đang lưu...';
    saveBtn.disabled = true;

    // Send request to server
    fetch("/UDPT-QLBN/Patient/api_addPatient", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(patientData),
      credentials: "include",
    })
      .then((response) => {
        if (!response.ok) {
          const contentType = response.headers.get("content-type");
          if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json().then((data) => {
              throw new Error(data.message || "Lỗi từ server");
            });
          }
          throw new Error(`Mã lỗi: ${response.status}`);
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

          // Reload patient list
          loadPatients();

          // Show success message
          showMessage(
            '<i class="fas fa-check-circle mr-1"></i> Thêm bệnh nhân thành công!',
            "success"
          );
        } else {
          // Hiển thị lỗi từ server trong form
          if (data.message.includes("CMND/CCCD")) {
            identityCardInput.classList.add("is-invalid");
            identityCardFeedback.textContent = data.message;
          } else {
            // Lỗi khác
            const formAlert = document.createElement("div");
            formAlert.className = "alert alert-danger mt-3";
            formAlert.innerHTML = data.message;
            document.getElementById("addPatientForm").prepend(formAlert);

            // Tự động xóa thông báo sau 5 giây
            setTimeout(() => formAlert.remove(), 5000);
          }
        }
      })
      .catch((error) => {
        // Hiển thị lỗi trong form
        const formAlert = document.createElement("div");
        formAlert.className = "alert alert-danger mt-3";

        if (error.message.includes("CMND/CCCD")) {
          identityCardInput.classList.add("is-invalid");
          identityCardFeedback.textContent = error.message;
        } else {
          formAlert.innerHTML = "Lỗi: " + error.message;
          document.getElementById("addPatientForm").prepend(formAlert);

          // Tự động xóa thông báo sau 5 giây
          setTimeout(() => formAlert.remove(), 5000);
        }
      })
      .finally(() => {
        // Restore button state
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
      });
  }
});
