document.addEventListener("DOMContentLoaded", function () {
  // Biến lưu trạng thái hiện tại
  $(document).ready(function () {
    // Xử lý nút Thêm thuốc trong modal
    $("#btnAddMedicine")
      .off("click")
      .on("click", function () {
        const btn = $(this);
        const originalText = btn.html();
        btn.html(
          '<span class="spinner-border spinner-border-sm mr-1"></span> Đang thêm...'
        );
        btn.prop("disabled", true);

        addMedicine();

        setTimeout(() => {
          btn.html(originalText);
          btn.prop("disabled", false);
        }, 1000);
      });

    // Xử lý nút Lưu thay đổi trong modal chỉnh sửa
    $("#btnSaveEdit")
      .off("click")
      .on("click", function () {
        const btn = $(this);
        const originalText = btn.html();
        btn.html(
          '<span class="spinner-border spinner-border-sm mr-1"></span> Đang lưu...'
        );
        btn.prop("disabled", true);

        updateMedicine();

        setTimeout(() => {
          btn.html(originalText);
          btn.prop("disabled", false);
        }, 1000);
      });

    // Xử lý nút Xóa trong modal xác nhận xóa
    $("#confirmDeleteBtn")
      .off("click")
      .on("click", function () {
        const btn = $(this);
        const originalText = btn.html();
        btn.html(
          '<span class="spinner-border spinner-border-sm mr-1"></span> Đang xóa...'
        );
        btn.prop("disabled", true);

        deleteMedicine();

        setTimeout(() => {
          btn.html(originalText);
          btn.prop("disabled", false);
        }, 1000);
      });

    // Xử lý nút hủy và đóng modal
    $('[data-dismiss="modal"]')
      .off("click")
      .on("click", function () {
        const modalId = $(this).closest(".modal").attr("id");
        $(`#${modalId}`).modal("hide");
      });

    // Đảm bảo modal đóng đúng
    $(".modal").on("hidden.bs.modal", function () {
      $("body").removeClass("modal-open");
      $(".modal-backdrop").remove();
    });
  });

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
      return;
    }

    const medicineData = {
      name,
      MFG,
      EXP,
      amount: parseInt(amount),
      unit,
      price: parseFloat(price),
    };

    fetch("/UDPT-QLBN/Medicine/api_createMedicine", {
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
      })
      .catch((error) => {
        showAlert(`Không thể thêm thuốc. Lỗi: ${error.message}`, "danger");
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

    if (!validateMedicineForm("editMedicineForm")) {
      return;
    }

    const medicineData = {
      name,
      MFG,
      EXP,
      amount: parseInt(amount),
      unit,
      price: parseFloat(price),
    };

    fetch(`/UDPT-QLBN/Medicine/api_updateMedicine/${medicineId}`, {
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
      })
      .catch((error) => {
        showAlert(`Không thể cập nhật thuốc. Lỗi: ${error.message}`, "danger");
      });
  }

  /**
   * Xóa thuốc
   */
  function deleteMedicine() {
    const medicineId = document.getElementById("deleteMedicineId").value;
    const medicineName =
      document.getElementById("deleteMedicineName").textContent;

    fetch(`/UDPT-QLBN/Medicine/api_deleteMedicine/${medicineId}`, {
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
      })
      .catch((error) => {
        showAlert(`Không thể xóa thuốc. Lỗi: ${error.message}`, "danger");
      });
  }

  // Khởi tạo ứng dụng
  init();

  function init() {
    loadMedicines();
    setupEventListeners();
    initializeDatepickers();
    setTimeout(() => checkBootstrapModal(), 500);
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

    // Thêm thuốc mới
    const addBtn = document.getElementById("btnAddMedicine");
    if (addBtn) {
      addBtn.addEventListener("click", function () {
        addMedicine();
      });
    }

    // Lưu chỉnh sửa thuốc
    const saveBtn = document.getElementById("btnSaveEdit");
    if (saveBtn) {
      saveBtn.addEventListener("click", function () {
        updateMedicine();
      });
    }

    // Xác nhận xóa thuốc
    const deleteBtn = document.getElementById("confirmDeleteBtn");
    if (deleteBtn) {
      deleteBtn.addEventListener("click", function () {
        deleteMedicine();
      });
    }

    // Modal dismiss
    document.querySelectorAll('[data-dismiss="modal"]').forEach((button) => {
      button.addEventListener("click", function () {
        const modalId = this.closest(".modal").id;
        $(`#${modalId}`).modal("hide");
      });
    });
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

    fetch(url)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Lỗi HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
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

        updatePaginationInfo(processedData);
        displayMedicines(processedData.data);
      })
      .catch((error) => {
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
    const tbody = document.querySelector("#medicinesTable tbody");
    const noResultsRow = document.getElementById("no-results-row");

    // Xóa dữ liệu cũ
    const rows = tbody.querySelectorAll("tr:not(#no-results-row)");
    rows.forEach((row) => row.remove());

    // Kiểm tra nếu không có dữ liệu
    if (!medicines || medicines.length === 0) {
      noResultsRow.style.display = "table-row";
      return;
    }

    // Ẩn thông báo không có kết quả
    noResultsRow.style.display = "none";

    // Hiển thị dữ liệu
    medicines.forEach((medicine) => {
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
    });
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
    selectedMedicineId = medicineId;

    fetch(`/UDPT-QLBN/Medicine/api_getMedicineById/${medicineId}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Lỗi HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((medicine) => {
        if (!medicine || !medicine.medicine_id) {
          throw new Error("Medicine ID is missing");
        }

        document.getElementById("editMedicineId").value =
          medicine.medicine_id || "";
        document.getElementById("editMedicineName").value = medicine.name || "";
        document.getElementById("editMedicineMFG").value = formatDateForInput(
          medicine.MFG || ""
        );
        document.getElementById("editMedicineEXP").value = formatDateForInput(
          medicine.EXP || ""
        );
        document.getElementById("editMedicineAmount").value =
          medicine.amount || 0;
        document.getElementById("editMedicineUnit").value = medicine.unit || "";
        document.getElementById("editMedicinePrice").value =
          medicine.price || 0;

        $("#editMedicineModal").modal("show");
      })
      .catch((error) => {
        showAlert(
          "Không thể tải thông tin thuốc. Vui lòng thử lại sau.",
          "danger"
        );
      });
  };

  window.showDeleteModal = function (medicineId, medicineName) {
    document.getElementById("deleteMedicineId").value = medicineId;
    document.getElementById("deleteMedicineName").textContent = medicineName;
    $("#deleteMedicineModal").modal("show");
  };
});
