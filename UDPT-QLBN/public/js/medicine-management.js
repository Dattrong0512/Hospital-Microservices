document.addEventListener("DOMContentLoaded", function () {
  // Setup modal handlers sau khi DOM đã sẵn sàng
  setTimeout(() => {
    setupModalHandlers();
  }, 100);

  // Hàm thiết lập xử lý modal
  function setupModalHandlers() {
    // Xử lý nút Thêm thuốc trong modal
    const addBtn = document.getElementById("btnAddMedicine");
    if (addBtn) {
      addBtn.removeEventListener("click", handleAddMedicine); // Loại bỏ listener cũ
      addBtn.addEventListener("click", handleAddMedicine);
    }

    // Xử lý nút Lưu thay đổi trong modal chỉnh sửa
    const saveBtn = document.getElementById("btnSaveEdit");
    if (saveBtn) {
      saveBtn.removeEventListener("click", handleSaveEdit); // Loại bỏ listener cũ
      saveBtn.addEventListener("click", handleSaveEdit);
    }

    // Xử lý nút Xóa trong modal xác nhận xóa
    const deleteBtn = document.getElementById("confirmDeleteBtn");
    if (deleteBtn) {
      deleteBtn.removeEventListener("click", handleDeleteMedicine); // Loại bỏ listener cũ
      deleteBtn.addEventListener("click", handleDeleteMedicine);
    }

    // Xử lý nút hủy và đóng modal
    document.querySelectorAll('[data-dismiss="modal"]').forEach((btn) => {
      btn.addEventListener("click", function () {
        const modalId = this.closest(".modal").id;
        $(`#${modalId}`).modal("hide");
      });
    });

    // Đảm bảo modal đóng đúng
    document.querySelectorAll(".modal").forEach((modal) => {
      $(modal).on("hidden.bs.modal", function () {
        $("body").removeClass("modal-open");
        $(".modal-backdrop").remove();
      });
    });
  }

  // Handler riêng cho nút Add để tránh gọi 2 lần
  function handleAddMedicine() {
    const btn = this;
    if (btn.disabled) return; // Tránh click nhiều lần

    const originalText = btn.innerHTML;
    btn.innerHTML =
      '<span class="spinner-border spinner-border-sm mr-1"></span> Đang thêm...';
    btn.disabled = true;

    addMedicine().finally(() => {
      setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
      }, 1000);
    });
  }

  // Handler riêng cho nút Save để tránh gọi 2 lần
  function handleSaveEdit() {
    const btn = this;
    if (btn.disabled) return; // Tránh click nhiều lần

    const originalText = btn.innerHTML;
    btn.innerHTML =
      '<span class="spinner-border spinner-border-sm mr-1"></span> Đang lưu...';
    btn.disabled = true;

    updateMedicine().finally(() => {
      setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
      }, 1000);
    });
  }

  // Handler riêng cho nút Delete để tránh gọi 2 lần
  function handleDeleteMedicine() {
    const btn = this;
    if (btn.disabled) return; // Tránh click nhiều lần

    const originalText = btn.innerHTML;
    btn.innerHTML =
      '<span class="spinner-border spinner-border-sm mr-1"></span> Đang xóa...';
    btn.disabled = true;

    deleteMedicine().finally(() => {
      setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
      }, 1000);
    });
  }

  // Biến lưu trạng thái hiện tại
  let currentPage = 1;
  let totalPages = 1;
  let limit = 10;
  let searchTerm = "";
  let sortField = "medicine_id";
  let sortDirection = "asc";
  let currentFilters = {
    expiry: "",
    amount: "",
  };
  let viewingNearExpiry = false;
  let selectedMedicineId = null;

  /**
   * Hiển thị/ẩn loading indicator
   */
  function showLoading(show) {
    const loadingIndicator = document.getElementById("loadingIndicator");
    if (show) {
      loadingIndicator.classList.remove("d-none");
    } else {
      loadingIndicator.classList.add("d-none");
    }
  }

  /**
   * Hiển thị thông báo
   */
  function showAlert(message, type = "info", duration = 5000) {
    const alertContainer = document.getElementById("alert-container");

    const alert = document.createElement("div");
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
      ${message}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    `;

    alertContainer.appendChild(alert);

    if (duration > 0) {
      setTimeout(() => {
        alert.classList.remove("show");
        setTimeout(() => {
          alert.remove();
        }, 150);
      }, duration);
    }
  }

  /**
   * Reset bộ lọc về trạng thái mặc định
   */
  function resetFilters() {
    currentFilters.expiry = "";
    currentFilters.amount = "";

    const expiryFilter = document.getElementById("expiry-filter");
    const amountFilter = document.getElementById("amount-filter");

    if (expiryFilter) expiryFilter.value = "";
    if (amountFilter) amountFilter.value = "";

    viewingNearExpiry = false;
  }

  /**
   * Cập nhật giao diện sắp xếp cột
   */
  function updateSortUI() {
    document.querySelectorAll(".sortable i").forEach((icon) => {
      icon.className = "fas fa-sort text-muted ml-1";
    });

    const currentColumn = document.querySelector(`[data-sort="${sortField}"]`);
    if (currentColumn) {
      const icon = currentColumn.querySelector("i");
      if (icon) {
        icon.className = `fas fa-sort-${
          sortDirection === "asc" ? "up" : "down"
        } text-primary ml-1`;
      }
    }
  }

  /**
   * Kiểm tra dữ liệu form thuốc
   */
  function validateMedicineForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    form.classList.add("was-validated");

    const requiredFields = form.querySelectorAll("[required]");
    let isValid = true;

    requiredFields.forEach((field) => {
      // ✅ Skip validation for readonly fields
      if (field.readOnly || field.hasAttribute("readonly")) {
        field.classList.remove("is-invalid");
        return;
      }

      if (!field.value.trim()) {
        field.classList.add("is-invalid");
        isValid = false;
      } else {
        field.classList.remove("is-invalid");
      }
    });

    // Kiểm tra ngày sản xuất và hạn sử dụng
    if (formId === "addMedicineForm") {
      const mfg = new Date(document.getElementById("medicineMFG").value);
      const exp = new Date(document.getElementById("medicineEXP").value);

      if (mfg >= exp) {
        document.getElementById("medicineEXP").classList.add("is-invalid");
        isValid = false;
      }
    } else if (formId === "editMedicineForm") {
      const mfg = new Date(document.getElementById("editMedicineMFG").value);
      const exp = new Date(document.getElementById("editMedicineEXP").value);

      if (mfg >= exp) {
        document.getElementById("editMedicineEXP").classList.add("is-invalid");
        isValid = false;
      }
    }

    return isValid;
  }

  /**
   * Thêm thuốc mới
   */
  function addMedicine() {
    const name = document.getElementById("medicineName").value;
    const MFG = document.getElementById("medicineMFG").value;
    const EXP = document.getElementById("medicineEXP").value;
    const amount = document.getElementById("medicineAmount").value;
    const unit = document.getElementById("medicineUnit").value;
    const price = document.getElementById("medicinePrice").value;

    if (!validateMedicineForm("addMedicineForm")) {
      return Promise.reject(new Error("Form validation failed"));
    }

    const medicineData = {
      name,
      MFG,
      EXP,
      amount: parseInt(amount),
      unit,
      price: parseFloat(price),
    };

    return fetch("/UDPT-QLBN/Medicine/api_createMedicine", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(medicineData),
    })
      .then((response) => {
        if (!response.ok) {
          return response.json().then((data) => {
            throw new Error(data.error || `Lỗi HTTP: ${response.status}`);
          });
        }
        return response.json();
      })
      .then((data) => {
        $("#addMedicineModal").modal("hide");
        document.getElementById("addMedicineForm").reset();
        showAlert(`Đã thêm thuốc "${name}" thành công!`, "success");
        loadMedicines();
        return data;
      })
      .catch((error) => {
        showAlert(`Không thể thêm thuốc. Lỗi: ${error.message}`, "danger");
        throw error;
      });
  }

  /**
   * Cập nhật thông tin thuốc
   */
  function updateMedicine() {
    const medicineId = document.getElementById("editMedicineId").value;
    const name = document.getElementById("editMedicineName").value;
    const MFG = document.getElementById("editMedicineMFG").value;
    const EXP = document.getElementById("editMedicineEXP").value;
    const amount = document.getElementById("editMedicineAmount").value;
    const unit = document.getElementById("editMedicineUnit").value;
    const price = document.getElementById("editMedicinePrice").value;

    // Validate medicine ID
    if (!medicineId || medicineId === "" || medicineId === "undefined") {
      showAlert("ID thuốc không hợp lệ", "danger");
      return Promise.reject(new Error("Medicine ID is missing or invalid"));
    }

    if (!validateMedicineForm("editMedicineForm")) {
      return Promise.reject(new Error("Form validation failed"));
    }

    const medicineData = {
      name,
      MFG,
      EXP,
      amount: parseInt(amount),
      unit,
      price: parseFloat(price),
    };

    console.log(
      "Updating medicine with ID:",
      medicineId,
      "Data:",
      medicineData
    );

    return fetch(`/UDPT-QLBN/Medicine/api_updateMedicine/${medicineId}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(medicineData),
    })
      .then((response) => {
        if (!response.ok) {
          return response.json().then((data) => {
            throw new Error(data.error || `Lỗi HTTP: ${response.status}`);
          });
        }
        return response.json();
      })
      .then((data) => {
        $("#editMedicineModal").modal("hide");
        showAlert(`Đã cập nhật thuốc "${name}" thành công!`, "success");
        loadMedicines();
        return data;
      })
      .catch((error) => {
        console.error("Update error:", error);
        showAlert(`Không thể cập nhật thuốc. Lỗi: ${error.message}`, "danger");
        throw error;
      });
  }

  /**
   * Xóa thuốc
   */
  function deleteMedicine() {
    const medicineId = document.getElementById("deleteMedicineId").value;
    const medicineName =
      document.getElementById("deleteMedicineName").textContent;

    return fetch(`/UDPT-QLBN/Medicine/api_deleteMedicine/${medicineId}`, {
      method: "DELETE",
    })
      .then((response) => {
        if (!response.ok) {
          return response.json().then((data) => {
            throw new Error(data.error || `Lỗi HTTP: ${response.status}`);
          });
        }
        return response.json();
      })
      .then((data) => {
        $("#deleteMedicineModal").modal("hide");
        showAlert(`Đã xóa thuốc "${medicineName}" thành công!`, "success");
        loadMedicines();
        return data;
      })
      .catch((error) => {
        showAlert(`Không thể xóa thuốc. Lỗi: ${error.message}`, "danger");
        throw error;
      });
  }

  // Khởi tạo ứng dụng
  init();

  function init() {
    console.log("🚀 Initializing medicine management app...");
    loadMedicines();
    setupEventListeners();
    initializeDatepickers();
    setTimeout(() => checkBootstrapModal(), 500);
    console.log("✅ Medicine management app initialized");
  }

  function checkBootstrapModal() {
    if (typeof $.fn.modal === "undefined") {
      $(document).on(
        "click",
        '[data-target="#addMedicineModal"]',
        function (e) {
          e.preventDefault();
          document.getElementById("addMedicineModal").style.display = "block";
          document.getElementById("addMedicineModal").classList.add("show");
        }
      );

      $(document).on("click", '[data-dismiss="modal"]', function (e) {
        e.preventDefault();
        $(this).closest(".modal").hide();
      });
    }
  }

  function setupEventListeners() {
    // Modal thêm thuốc
    $(document).on("click", '[data-target="#addMedicineModal"]', function (e) {
      e.preventDefault();
      $("#addMedicineModal").modal("show");
    });

    // Toggle thuốc sắp hết hạn
    const toggleBtn = document.getElementById("toggleNearExpiry");
    if (toggleBtn) {
      toggleBtn.addEventListener("click", function () {
        viewingNearExpiry = !viewingNearExpiry;
        const btn = this;

        if (viewingNearExpiry) {
          btn.classList.remove("btn-outline-warning");
          btn.classList.add("btn-warning");
          btn.innerHTML = '<i class="fas fa-list mr-1"></i>Tất cả thuốc';
          loadNearExpiryMedicines();
        } else {
          btn.classList.remove("btn-warning");
          btn.classList.add("btn-outline-warning");
          btn.innerHTML =
            '<i class="fas fa-exclamation-triangle mr-1"></i>Thuốc sắp hết hạn';
          loadMedicines();
        }
      });
    }

    // Tìm kiếm
    const searchInput = document.getElementById("searchMedicine");
    if (searchInput) {
      let searchTimeout;

      searchInput.addEventListener("keyup", function (e) {
        if (e.key === "Enter" || e.keyCode === 13) {
          clearTimeout(searchTimeout);
          performSearch();
        } else {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
            if (this.value.trim() !== searchTerm) {
              performSearch();
            }
          }, 500);
        }
      });

      searchInput.addEventListener("blur", function () {
        if (this.value.trim() !== searchTerm) {
          performSearch();
        }
      });
    }

    function performSearch() {
      const newSearchTerm = searchInput.value.trim();
      searchTerm = newSearchTerm;
      currentPage = 1;
      viewingNearExpiry = false;

      // Reset toggle button
      const toggleBtn = document.getElementById("toggleNearExpiry");
      if (toggleBtn) {
        toggleBtn.classList.remove("btn-warning");
        toggleBtn.classList.add("btn-outline-warning");
        toggleBtn.innerHTML =
          '<i class="fas fa-exclamation-triangle mr-1"></i>Thuốc sắp hết hạn';
      }

      resetFilters();
      loadMedicines();
    }

    // Phân trang
    const prevBtn = document.getElementById("prevPage");
    if (prevBtn) {
      prevBtn.addEventListener("click", function (e) {
        e.preventDefault();
        if (currentPage > 1) {
          currentPage--;
          loadMedicines();
        }
      });
    }

    const nextBtn = document.getElementById("nextPage");
    if (nextBtn) {
      nextBtn.addEventListener("click", function (e) {
        e.preventDefault();
        if (currentPage < totalPages) {
          currentPage++;
          loadMedicines();
        }
      });
    }

    // Sắp xếp cột
    document.querySelectorAll(".sortable").forEach((column) => {
      column.addEventListener("click", function () {
        const field = this.getAttribute("data-sort");

        if (field === sortField) {
          sortDirection = sortDirection === "asc" ? "desc" : "asc";
        } else {
          sortField = field;
          sortDirection = "asc";
        }

        updateSortUI();
        loadMedicines();
      });
    });

    // Clear filters
    const clearFiltersBtn = document.getElementById("clear-filters");
    if (clearFiltersBtn) {
      clearFiltersBtn.addEventListener("click", function () {
        resetFilters();
        loadMedicines();
      });
    }

    // Modal dismiss - đã xử lý trong setupModalHandlers()
  }

  function initializeDatepickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach((input) => {
      input.addEventListener("click", function () {
        this.showPicker();
      });
    });
  }

  function loadMedicines() {
    console.log(
      "🔄 Loading medicines - viewingNearExpiry:",
      viewingNearExpiry,
      "searchTerm:",
      searchTerm
    );
    showLoading(true);

    let url;
    let params = `page=${currentPage}&limit=${limit}`;

    if (viewingNearExpiry) {
      url = `/UDPT-QLBN/Medicine/api_getNearExpiryMedicines?${params}`;
    } else if (searchTerm && searchTerm.trim() !== "") {
      url = `/UDPT-QLBN/Medicine/api_searchMedicines?query=${encodeURIComponent(
        searchTerm.trim()
      )}&${params}`;
    } else {
      url = `/UDPT-QLBN/Medicine/api_getAllMedicines?${params}`;
    }

    console.log("📡 Fetching from URL:", url);

    fetch(url)
      .then((response) => {
        console.log(
          "📥 Response received:",
          response.status,
          response.statusText
        );
        if (!response.ok) {
          throw new Error(`Lỗi HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        console.log("📦 Raw data received:", data);
        let processedData;

        if (data && typeof data === "object") {
          if (data.hasOwnProperty("data") && Array.isArray(data.data)) {
            processedData = data;
          } else if (Array.isArray(data)) {
            processedData = {
              data: data,
              page: currentPage,
              limit: limit,
              total: data.length,
              total_pages: Math.ceil(data.length / limit),
            };
          } else if (data.hasOwnProperty("error")) {
            throw new Error(data.error);
          } else {
            throw new Error("Định dạng dữ liệu API không hợp lệ");
          }
        } else {
          throw new Error("Dữ liệu API không hợp lệ");
        }

        console.log("🔧 Processed data:", processedData);
        updatePaginationInfo(processedData);
        displayMedicines(processedData.data);
      })
      .catch((error) => {
        console.error("❌ Load medicines error:", error);
        showAlert(
          `Không thể tải dữ liệu thuốc. Lỗi: ${error.message}`,
          "danger"
        );
      })
      .finally(() => {
        showLoading(false);
      });
  }

  function loadNearExpiryMedicines() {
    showLoading(true);

    const params = `page=${currentPage}&limit=${limit}`;
    const url = `/UDPT-QLBN/Medicine/api_getNearExpiryMedicines?${params}`;

    fetch(url)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Lỗi HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        let processedData;
        if (data && data.data && Array.isArray(data.data)) {
          processedData = data;
        } else if (Array.isArray(data)) {
          processedData = {
            data: data,
            page: currentPage,
            limit: limit,
            total: data.length,
            total_pages: Math.ceil(data.length / limit),
          };
        } else {
          processedData = {
            data: [],
            page: 1,
            limit: limit,
            total: 0,
            total_pages: 0,
          };
        }

        updatePaginationInfo(processedData);
        displayMedicines(processedData.data);
        showLoading(false);
      })
      .catch((error) => {
        showAlert("Không thể tải danh sách thuốc sắp hết hạn", "danger");
        showLoading(false);
      });
  }

  function displayMedicines(medicines) {
    console.log("🖥️ Displaying medicines:", medicines);
    const tbody = document.querySelector("#medicinesTable tbody");
    const noResultsRow = document.getElementById("no-results-row");

    console.log("📋 Table body found:", !!tbody);
    console.log("🚫 No results row found:", !!noResultsRow);

    // Xóa dữ liệu cũ
    const rows = tbody.querySelectorAll("tr:not(#no-results-row)");
    console.log("🗑️ Removing", rows.length, "old rows");
    rows.forEach((row) => row.remove());

    // Kiểm tra nếu không có dữ liệu
    if (!medicines || medicines.length === 0) {
      console.log("❌ No medicines to display");
      noResultsRow.style.display = "table-row";
      return;
    }

    console.log("✅ Displaying", medicines.length, "medicines");

    // Ẩn thông báo không có kết quả
    noResultsRow.style.display = "none";

    // Hiển thị dữ liệu
    medicines.forEach((medicine, index) => {
      console.log(`📝 Processing medicine ${index}:`, medicine);
      const expStatus = getExpiryStatus(medicine.EXP);
      const expiryClass = getExpiryClass(expStatus);

      const row = document.createElement("tr");
      row.innerHTML = `
        <td>${medicine.medicine_id}</td>
        <td>${medicine.name}</td>
        <td>${formatDate(medicine.MFG)}</td>
        <td>
          <span class="${expiryClass.text}">${formatDate(medicine.EXP)}</span>
          <br><small class="badge ${expiryClass.badge}">${
        expStatus.text
      }</small>
        </td>
        <td>${medicine.amount.toLocaleString()}</td>
        <td>${medicine.unit}</td>
        <td>${medicine.price.toLocaleString()} đ</td>
        <td>
          <button class="btn btn-sm btn-outline-primary mr-1" onclick="editMedicine(${
            medicine.medicine_id
          })">
            <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="showDeleteModal(${
            medicine.medicine_id
          }, '${medicine.name}')">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      `;
      tbody.appendChild(row);
      console.log(`✅ Added row for medicine ${medicine.medicine_id}`);
    });

    console.log("🎉 Display medicines completed");
  }

  function getExpiryStatus(expDateStr) {
    const today = new Date();
    const expDate = parseDate(expDateStr);

    if (!expDate) {
      return { code: "unknown", text: "Không xác định" };
    }

    const daysRemaining = Math.floor((expDate - today) / (1000 * 60 * 60 * 24));

    if (daysRemaining < 0) {
      return { code: "expired", text: "Đã hết hạn" };
    } else if (daysRemaining <= 7) {
      return { code: "near", text: `Còn ${daysRemaining} ngày` };
    } else {
      return { code: "valid", text: "Còn hạn" };
    }
  }

  function getExpiryClass(status) {
    switch (status.code) {
      case "expired":
        return { badge: "badge-expiry-danger", text: "text-danger" };
      case "near":
        return { badge: "badge-expiry-warning", text: "text-warning" };
      default:
        return { badge: "badge-expiry-safe", text: "text-success" };
    }
  }

  function updatePaginationInfo(data) {
    totalPages = data.total_pages || 1;
    currentPage = data.page || 1;

    const paginationInfo = document.getElementById("paginationInfo");
    if (paginationInfo) {
      paginationInfo.textContent = `Hiển thị ${data.data.length} trên ${data.total} kết quả`;
    }

    updatePaginationUI();
  }

  function updatePaginationUI() {
    const pagination = document.getElementById("pagination");
    const prevPageBtn = document.getElementById("prevPage");
    const nextPageBtn = document.getElementById("nextPage");

    // Xóa các trang cũ
    const pageItems = pagination.querySelectorAll(
      "li:not(.page-item:first-child):not(.page-item:last-child)"
    );
    pageItems.forEach((item) => item.remove());

    // Cập nhật trạng thái nút Trước/Sau
    prevPageBtn.parentElement.classList.toggle("disabled", currentPage <= 1);
    nextPageBtn.parentElement.classList.toggle(
      "disabled",
      currentPage >= totalPages
    );

    // Thêm các trang mới
    const firstPageLi = prevPageBtn.parentElement;

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);

    if (endPage - startPage < 4) {
      startPage = Math.max(1, endPage - 4);
    }

    for (let i = startPage; i <= endPage; i++) {
      const li = document.createElement("li");
      li.className = `page-item ${i === currentPage ? "active" : ""}`;

      const a = document.createElement("a");
      a.className = "page-link";
      a.href = "#";
      a.textContent = i;
      a.addEventListener("click", function (e) {
        e.preventDefault();
        currentPage = i;
        loadMedicines();
      });

      li.appendChild(a);
      pagination.insertBefore(li, nextPageBtn.parentElement);
    }
  }

  function parseDate(dateStr) {
    if (!dateStr) return null;

    const ddmmyyyyRegex = /^(\d{2})-(\d{2})-(\d{4})$/;
    const match = dateStr.match(ddmmyyyyRegex);

    if (match) {
      const [, day, month, year] = match;
      const isoDate = `${year}-${month}-${day}`;
      const date = new Date(isoDate);
      return isNaN(date.getTime()) ? null : date;
    }

    const date = new Date(dateStr);
    return isNaN(date.getTime()) ? null : date;
  }

  function formatDate(dateStr) {
    if (!dateStr) return "";

    if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) {
      return dateStr;
    }

    const date = parseDate(dateStr);
    if (date) {
      const day = String(date.getDate()).padStart(2, "0");
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const year = date.getFullYear();
      return `${day}-${month}-${year}`;
    }

    return dateStr;
  }

  function formatDateForInput(dateStr) {
    if (!dateStr) return "";

    const ddmmyyyyRegex = /^(\d{2})-(\d{2})-(\d{4})$/;
    const match = dateStr.match(ddmmyyyyRegex);

    if (match) {
      const [, day, month, year] = match;
      return `${year}-${month}-${day}`;
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
      return dateStr;
    }

    const date = parseDate(dateStr);
    if (date) {
      const day = String(date.getDate()).padStart(2, "0");
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const year = date.getFullYear();
      return `${year}-${month}-${day}`;
    }

    return "";
  }

  // Global functions để gọi từ HTML
  window.editMedicine = function (medicineId) {
    console.log("Editing medicine:", medicineId);

    // Validate medicineId
    if (!medicineId || medicineId === "" || medicineId === "undefined") {
      showAlert("ID thuốc không hợp lệ", "danger");
      return;
    }

    selectedMedicineId = medicineId;

    fetch(`/UDPT-QLBN/Medicine/api_getMedicineById/${medicineId}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Lỗi HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((responseData) => {
        console.log("Raw response:", responseData);

        // ✅ Handle wrapped response format
        let medicine;
        if (responseData.data) {
          medicine = responseData.data;
        } else if (responseData.medicine_id) {
          medicine = responseData;
        } else {
          throw new Error("Dữ liệu thuốc không hợp lệ");
        }

        console.log("Medicine data:", medicine);

        // Clear validation classes and reset form
        const form = document.getElementById("editMedicineForm");
        if (form) {
          form.reset();
          form.classList.remove("was-validated");
          form
            .querySelectorAll(".is-invalid")
            .forEach((el) => el.classList.remove("is-invalid"));
        }

        // ✅ Show modal first
        $("#editMedicineModal").modal("show");

        // ✅ Try immediate populate (without delay)
        console.log("🔧 Immediate populate attempt...");
        populateEditForm(medicine, medicineId);

        // ✅ Use multiple strategies to populate data
        forcePopulateEditModal(medicine, medicineId);

        // ✅ Also set up modal shown event
        $("#editMedicineModal")
          .off("shown.bs.modal.editMedicine")
          .on("shown.bs.modal.editMedicine", function () {
            console.log("🔧 Modal shown event - populate again...");
            populateEditForm(medicine, medicineId);
            forcePopulateEditModal(medicine, medicineId);
          });
      })
      .catch((error) => {
        console.error("Edit error:", error);
        showAlert(`Không thể tải thông tin thuốc: ${error.message}`, "danger");
      });
  };

  window.showDeleteModal = function (medicineId, medicineName) {
    document.getElementById("deleteMedicineId").value = medicineId;
    document.getElementById("deleteMedicineName").textContent = medicineName;
    $("#deleteMedicineModal").modal("show");
  };

  // ✅ Simple populate function
  function populateEditForm(medicine, medicineId) {
    console.log("📝 Simple populate with:", medicine);

    // Set values directly
    const editMedicineId = document.getElementById("editMedicineId");
    const editMedicineName = document.getElementById("editMedicineName");
    const editMedicineMFG = document.getElementById("editMedicineMFG");
    const editMedicineEXP = document.getElementById("editMedicineEXP");
    const editMedicineAmount = document.getElementById("editMedicineAmount");
    const editMedicineUnit = document.getElementById("editMedicineUnit");
    const editMedicinePrice = document.getElementById("editMedicinePrice");

    if (editMedicineId) {
      editMedicineId.value = medicine.medicine_id || medicineId;
      console.log("📝 Set ID:", editMedicineId.value);
    }

    if (editMedicineName) {
      editMedicineName.value = medicine.name || "";
      console.log("📝 Set Name:", editMedicineName.value);
    }

    if (editMedicineMFG) {
      const mfgValue = formatDateForInput(medicine.MFG || "");
      editMedicineMFG.value = mfgValue;
      console.log("📝 Set MFG:", mfgValue, "→", editMedicineMFG.value);
    }

    if (editMedicineEXP) {
      const expValue = formatDateForInput(medicine.EXP || "");
      editMedicineEXP.value = expValue;
      console.log("📝 Set EXP:", expValue, "→", editMedicineEXP.value);
    }

    if (editMedicineAmount) {
      editMedicineAmount.value = medicine.amount || 0;
      console.log("📝 Set Amount:", editMedicineAmount.value);
    }

    if (editMedicineUnit) {
      editMedicineUnit.value = medicine.unit || "";
      console.log("📝 Set Unit:", editMedicineUnit.value);
    }

    if (editMedicinePrice) {
      editMedicinePrice.value = medicine.price || 0;
      console.log("📝 Set Price:", editMedicinePrice.value);
    }

    console.log("📝 Simple populate completed");
  }

  // ✅ DEBUG: Force populate function
  function forcePopulateEditModal(medicine, medicineId) {
    console.log("🔧 Force populating modal with:", medicine);

    // ✅ Tạo bản copy của medicine object để tránh closure bug
    const medicineCopy = {
      medicine_id: medicine.data.medicine_id,
      name: medicine.data.name,
      MFG: medicine.data.MFG,
      EXP: medicine.data.EXP,
      amount: medicine.data.amount,
      unit: medicine.data.unit,
      price: medicine.data.price,
    };

    console.log("🔧 Medicine copy before setTimeout:", medicineCopy);

    // Wait a bit for DOM to be ready
    setTimeout(() => {
      console.log("🔧 Medicine copy inside setTimeout:", medicineCopy);

      const fields = {
        editMedicineId: medicineCopy.medicine_id || medicineId,
        editMedicineName: medicineCopy.name || "",
        editMedicineMFG: formatDateForInput(medicineCopy.MFG || ""),
        editMedicineEXP: formatDateForInput(medicineCopy.EXP || ""),
        editMedicineAmount: medicineCopy.amount || 0,
        editMedicineUnit: medicineCopy.unit || "",
        editMedicinePrice: medicineCopy.price || 0,
      };

      console.log("🔧 Fields to populate:", fields);

      Object.keys(fields).forEach((fieldId) => {
        const element = document.getElementById(fieldId);
        console.log(`🔍 Looking for element ${fieldId}:`, element);

        if (element) {
          const oldValue = element.value;
          element.value = fields[fieldId];

          console.log(
            `✅ Set ${fieldId}: "${oldValue}" → "${fields[fieldId]}"`
          );
          console.log(`✅ Current value after set: "${element.value}"`);

          // Force trigger change event
          element.dispatchEvent(new Event("change", { bubbles: true }));
          element.dispatchEvent(new Event("input", { bubbles: true }));

          // Special handling for readonly fields
          if (element.readOnly || element.hasAttribute("readonly")) {
            element.setAttribute("value", fields[fieldId]);
            console.log(`🔒 Set readonly field ${fieldId} via attribute`);
          }
        } else {
          console.error(`❌ Element ${fieldId} not found in DOM`);

          // Debug: show all input elements in the modal
          const modal = document.getElementById("editMedicineModal");
          if (modal) {
            const allInputs = modal.querySelectorAll("input");
            console.log(
              "🔍 All inputs in modal:",
              Array.from(allInputs).map((inp) => ({
                id: inp.id,
                type: inp.type,
                value: inp.value,
              }))
            );
          }
        }
      });

      console.log("🔧 Force populate completed");

      // Verify population after a short delay
      setTimeout(() => {
        console.log("🔍 Verification after populate:");
        Object.keys(fields).forEach((fieldId) => {
          const element = document.getElementById(fieldId);
          if (element) {
            console.log(
              `✅ ${fieldId}: "${element.value}" (expected: "${fields[fieldId]}")`
            );
          }
        });
      }, 100);
    }, 200);
  }

  // Test function for debugging
  window.testEditModal = function () {
    console.log("🧪 Testing edit modal...");

    const testMedicine = {
      medicine_id: 1,
      name: "Paracetamol Test",
      MFG: "01-05-2024",
      EXP: "01-05-2026",
      amount: 71,
      unit: "viên",
      price: 10000,
    };

    console.log("🧪 Test medicine data:", testMedicine);

    // Show modal
    $("#editMedicineModal").modal("show");

    // Populate after modal is shown
    setTimeout(() => {
      populateEditForm(testMedicine, 1);
      forcePopulateEditModal(testMedicine, 1);
    }, 500);
  };
});
