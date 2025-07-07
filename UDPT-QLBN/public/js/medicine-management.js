document.addEventListener("DOMContentLoaded", function () {
  // Biến lưu trạng thái hiện tại
  $(document).ready(function () {
    // Xử lý nút Thêm thuốc trong modal
    $("#btnAddMedicine")
      .off("click")
      .on("click", function () {
        // Hiển thị trạng thái loading
        const btn = $(this);
        const originalText = btn.html();
        btn.html(
          '<span class="spinner-border spinner-border-sm mr-1"></span> Đang thêm...'
        );
        btn.prop("disabled", true);

        // Gọi hàm thêm thuốc
        addMedicine();

        // Khôi phục nút sau 1 giây
        setTimeout(() => {
          btn.html(originalText);
          btn.prop("disabled", false);
        }, 1000);
      });

    // Xử lý nút Lưu thay đổi trong modal chỉnh sửa
    $("#btnSaveEdit")
      .off("click")
      .on("click", function () {
        // Hiển thị trạng thái loading
        const btn = $(this);
        const originalText = btn.html();
        btn.html(
          '<span class="spinner-border spinner-border-sm mr-1"></span> Đang lưu...'
        );
        btn.prop("disabled", true);

        // Gọi hàm cập nhật thuốc
        updateMedicine();

        // Khôi phục nút sau 1 giây
        setTimeout(() => {
          btn.html(originalText);
          btn.prop("disabled", false);
        }, 1000);
      });

    // Xử lý nút Xóa trong modal xác nhận xóa
    $("#confirmDeleteBtn")
      .off("click")
      .on("click", function () {
        // Hiển thị trạng thái loading
        const btn = $(this);
        const originalText = btn.html();
        btn.html(
          '<span class="spinner-border spinner-border-sm mr-1"></span> Đang xóa...'
        );
        btn.prop("disabled", true);

        // Gọi hàm xóa thuốc
        deleteMedicine();

        // Khôi phục nút sau 1 giây
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

  // Khởi tạo ứng dụng
  init();

  /**
   * Khởi tạo các chức năng của ứng dụng
   */
  function init() {
    // Tải dữ liệu thuốc mặc định
    loadMedicines();

    // Thiết lập các sự kiện
    setupEventListeners();

    // Khởi tạo các thành phần giao diện
    initializeDatepickers();

    console.log("✓ Khởi tạo quản lý thuốc hoàn tất");
  }

  /**
   * Thiết lập các sự kiện trong ứng dụng
   */
  function setupEventListeners() {
    // Sự kiện tìm kiếm
    const searchInput = document.getElementById("searchMedicine");
    if (searchInput) {
      let searchTimeout;

      // Tìm kiếm khi nhấn Enter
      searchInput.addEventListener("keyup", function (e) {
        if (e.key === "Enter" || e.keyCode === 13) {
          clearTimeout(searchTimeout);
          performSearch();
        } else {
          // Debounce search - tìm kiếm sau 500ms không gõ
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
            if (this.value.trim() !== searchTerm) {
              performSearch();
            }
          }, 500);
        }
      });

      // Tìm kiếm khi mất focus
      searchInput.addEventListener("blur", function () {
        if (this.value.trim() !== searchTerm) {
          performSearch();
        }
      });
    }

    // Hàm thực hiện tìm kiếm
    function performSearch() {
      const newSearchTerm = searchInput.value.trim();
      console.log("🔍 [SEARCH] Performing search with term:", newSearchTerm);

      searchTerm = newSearchTerm;
      currentPage = 1;
      viewingNearExpiry = false;
      resetFilters();
      loadMedicines();
    }

    document
      .getElementById("btn-near-expiry")
      .addEventListener("click", function () {
        console.log("⚠️ [NEAR_EXPIRY] Button clicked");

        viewingNearExpiry = true;
        searchTerm = "";
        currentPage = 1;
        resetFilters();

        // Clear search input
        const searchInput = document.getElementById("searchMedicine");
        if (searchInput) {
          searchInput.value = "";
        }

        loadMedicines();
      });

    // Sự kiện phân trang
    document.getElementById("prevPage").addEventListener("click", function (e) {
      e.preventDefault();
      if (currentPage > 1) {
        currentPage--;
        loadMedicines();
      }
    });

    document.getElementById("nextPage").addEventListener("click", function (e) {
      e.preventDefault();
      if (currentPage < totalPages) {
        currentPage++;
        loadMedicines();
      }
    });

    // Sự kiện sắp xếp cột
    document.querySelectorAll(".sortable").forEach((column) => {
      column.addEventListener("click", function () {
        const field = this.getAttribute("data-sort");

        // Nếu click vào cùng cột đang sắp xếp, đảo chiều
        if (field === sortField) {
          sortDirection = sortDirection === "asc" ? "desc" : "asc";
        } else {
          sortField = field;
          sortDirection = "asc";
        }

        // Cập nhật giao diện
        updateSortUI();

        // Tải lại dữ liệu
        loadMedicines();
      });
    });

    // Sự kiện lọc
    document
      .getElementById("apply-filter")
      .addEventListener("click", function () {
        currentFilters.expiry = document.getElementById("expiry-filter").value;
        currentFilters.amount = document.getElementById("amount-filter").value;
        currentPage = 1;
        console.log("✏️ Áp dụng bộ lọc:", currentFilters);
        loadMedicines();
      });

    document
      .getElementById("reset-filter")
      .addEventListener("click", function () {
        console.log("✏️ Reset bộ lọc");
        resetFilters();
        loadMedicines();
      });

    document
      .getElementById("clear-filters")
      .addEventListener("click", function () {
        console.log("✏️ Xóa tất cả bộ lọc");
        resetFilters();
        loadMedicines();
      });

    // Sự kiện xem thuốc sắp hết hạn
    document
      .getElementById("btn-near-expiry")
      .addEventListener("click", function () {
        viewingNearExpiry = true;
        searchTerm = "";
        currentPage = 1;
        resetFilters();
        document.getElementById("searchMedicine").value = "";
        loadMedicines();
      });

    // Thêm thuốc mới
    document
      .getElementById("btnAddMedicine")
      .addEventListener("click", function () {
        addMedicine();
      });

    // Sự kiện khi mở modal chi tiết
    // $("#viewMedicineModal").on("show.bs.modal", function (e) {
    //   const medicineId = e.relatedTarget.getAttribute("data-id");
    //   loadMedicineDetails(medicineId);
    // });

    // Sự kiện khi mở modal chỉnh sửa từ chi tiết
    // document
    //   .getElementById("btnEditMedicine")
    //   .addEventListener("click", function () {
    //     // Đóng modal chi tiết và mở modal chỉnh sửa
    //     $("#viewMedicineModal").modal("hide");
    //     $("#editMedicineModal").modal("show");

    //     // Tải thông tin thuốc vào form chỉnh sửa
    //     populateEditForm(selectedMedicineId);
    //   });

    // Lưu chỉnh sửa thuốc
    document
      .getElementById("btnSaveEdit")
      .addEventListener("click", function () {
        updateMedicine();
      });

    // Mở modal xác nhận xóa thuốc
    // document
    //   .getElementById("btnDeleteMedicine")
    //   .addEventListener("click", function () {
    //     const medicineId = document.getElementById("editMedicineId").value;
    //     const medicineName = document.getElementById("editMedicineName").value;

    //     document.getElementById("deleteMedicineId").value = medicineId;
    //     document.getElementById("deleteMedicineName").textContent =
    //       medicineName;

    //     $("#editMedicineModal").modal("hide");
    //     $("#deleteMedicineModal").modal("show");
    //   });

    // Xác nhận xóa thuốc
    document
      .getElementById("confirmDeleteBtn")
      .addEventListener("click", function () {
        deleteMedicine();
      });

    document.querySelectorAll('[data-dismiss="modal"]').forEach((button) => {
      button.addEventListener("click", function () {
        const modalId = this.closest(".modal").id;
        $(`#${modalId}`).modal("hide");
      });
    });

    // Đảm bảo modal có thể đóng khi click nút X
    document.querySelectorAll(".modal .close").forEach((button) => {
      button.addEventListener("click", function () {
        const modalId = this.closest(".modal").id;
        $(`#${modalId}`).modal("hide");
      });
    });
  }

  /**
   * Khởi tạo date picker cho các trường ngày tháng
   */
  function initializeDatepickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach((input) => {
      input.addEventListener("click", function () {
        this.showPicker();
      });

      // Thêm sự kiện cho nút calendar
      const parent = input.closest(".datepicker-container");
      if (parent) {
        const calendarBtn = parent.querySelector(".input-group-text");
        if (calendarBtn) {
          calendarBtn.addEventListener("click", function () {
            input.showPicker();
          });
        }
      }
    });
  }

  /**
   * Tải danh sách thuốc từ API
   */
  function loadMedicines() {
    console.log("📡 Bắt đầu tải dữ liệu thuốc");
    console.log("📋 Trạng thái hiện tại:", {
      page: currentPage,
      limit: limit,
      searchTerm: searchTerm,
      viewingNearExpiry: viewingNearExpiry,
      filters: currentFilters,
    });

    showLoading(true);

    let url;
    let params = `page=${currentPage}&limit=${limit}`;

    // ✅ SỬA: URL mapping theo API Python
    if (viewingNearExpiry) {
      // Gọi API thuốc sắp hết hạn
      url = `/UDPT-QLBN/Medicine/api_getNearExpiryMedicines?${params}`;
      console.log("⚠️ [LOAD] Loading near expiry medicines");
    } else if (searchTerm && searchTerm.trim() !== "") {
      // Gọi API tìm kiếm thuốc theo tên
      url = `/UDPT-QLBN/Medicine/api_searchMedicines?query=${encodeURIComponent(
        searchTerm.trim()
      )}&${params}`;
      console.log("🔍 [LOAD] Searching medicines with term:", searchTerm);
    } else {
      // Gọi API lấy tất cả thuốc
      url = `/UDPT-QLBN/Medicine/api_getAllMedicines?${params}`;
      console.log("📋 [LOAD] Loading all medicines");
    }

    console.log("📡 Gọi API:", url);

    fetch(url)
      .then((response) => {
        console.log("📡 Response status:", response.status);

        if (!response.ok) {
          console.error("❌ API trả về lỗi:", response.status);
          throw new Error(`Lỗi HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        console.log("✅ Tải dữ liệu thuốc thành công:", data);

        // ✅ XỬ LÝ RESPONSE THEO FORMAT PYTHON API
        let processedData;

        if (data && typeof data === "object") {
          // Nếu có cấu trúc phân trang từ Python API
          if (data.hasOwnProperty("data") && Array.isArray(data.data)) {
            processedData = data;
            console.log("📊 [LOAD] Structured response with pagination");
          }
          // Nếu là array đơn giản
          else if (Array.isArray(data)) {
            processedData = {
              data: data,
              page: currentPage,
              limit: limit,
              total: data.length,
              total_pages: Math.ceil(data.length / limit),
            };
            console.log(
              "📊 [LOAD] Array response, created pagination structure"
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

        // Hiển thị thông tin về định dạng ngày nếu có dữ liệu
        if (processedData.data && processedData.data.length > 0) {
          const sample = processedData.data[0];
          console.log("📅 Mẫu định dạng ngày:", {
            MFG: sample.MFG,
            MFG_type: typeof sample.MFG,
            EXP: sample.EXP,
            EXP_type: typeof sample.EXP,
          });
        }

        // Cập nhật dữ liệu phân trang
        updatePaginationInfo(processedData);

        // Hiển thị dữ liệu
        displayMedicines(processedData.data);
      })
      .catch((error) => {
        console.error("❌ Lỗi khi tải dữ liệu thuốc:", error);
        showAlert(
          `Không thể tải dữ liệu thuốc. Lỗi: ${error.message}`,
          "danger"
        );
      })
      .finally(() => {
        console.log("📡 Hoàn thành tải dữ liệu thuốc");
        showLoading(false);
      });
  }

  /**
   * Hiển thị danh sách thuốc
   * @param {Array} medicines Danh sách thuốc
   */
  function displayMedicines(medicines) {
    console.log("📋 Hiển thị danh sách thuốc:", medicines);
    const tbody = document.querySelector("#medicinesTable tbody");
    const noResultsRow = document.getElementById("no-results-row");

    // Xóa dữ liệu cũ (trừ hàng thông báo không có kết quả)
    const rows = tbody.querySelectorAll("tr:not(#no-results-row)");
    rows.forEach((row) => row.remove());

    // Kiểm tra nếu không có dữ liệu
    if (!medicines || medicines.length === 0) {
      console.warn("⚠️ Không có dữ liệu thuốc để hiển thị");
      noResultsRow.style.display = "table-row";
      return;
    }

    // Lọc dữ liệu theo bộ lọc hiện tại
    console.log("🔍 Bắt đầu lọc dữ liệu với bộ lọc:", currentFilters);
    const filteredMedicines = filterMedicines(medicines);
    console.log("🔍 Kết quả sau khi lọc:", filteredMedicines);

    // Hiển thị thông báo nếu không có kết quả
    if (filteredMedicines.length === 0) {
      console.warn("⚠️ Không tìm thấy thuốc phù hợp với bộ lọc");
      noResultsRow.style.display = "table-row";
      return;
    }

    // Ẩn thông báo không có kết quả
    noResultsRow.style.display = "none";
    console.log("✅ Hiển thị", filteredMedicines.length, "thuốc phù hợp");

    // Hiển thị dữ liệu
    filteredMedicines.forEach((medicine) => {
      const row = document.createElement("tr");

      // Tính toán trạng thái hạn sử dụng
      const expStatus = getExpiryStatus(medicine.EXP);
      const expiryClass = getExpiryClass(expStatus);

      row.innerHTML = `
      <td>${medicine.medicine_id}</td>
      <td>
        <div class="d-flex align-items-center">
          <div>
            <div class="font-weight-bold">${medicine.name}</div>
            <div class="small ${expiryClass.text}">
              <span class="badge ${expiryClass.badge} mr-1">
                <i class="fas fa-clock mr-1"></i>${expStatus.text}
              </span>
            </div>
          </div>
        </div>
      </td>
      <td>${formatDate(medicine.MFG)}</td>
      <td>${formatDate(medicine.EXP)}</td>
      <td class="text-right">${medicine.amount.toLocaleString()}</td>
      <td>${medicine.unit}</td>
      <td class="text-right">${medicine.price.toLocaleString()} đ</td>
      <td>
        <div class="btn-group">
          <button type="button" class="btn btn-sm btn-outline-primary edit-medicine" data-id="${
            medicine.medicine_id
          }">
            <i class="fas fa-edit mr-1"></i> Sửa
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger ml-1 delete-medicine" data-id="${
            medicine.medicine_id
          }" data-name="${medicine.name}">
            <i class="fas fa-trash-alt mr-1"></i> Xóa
          </button>
        </div>
      </td>
    `;

      tbody.appendChild(row);

      // Thêm event listeners cho các nút hành động
      row
        .querySelector(".edit-medicine")
        .addEventListener("click", function () {
          const medicineId = this.getAttribute("data-id");
          populateEditForm(medicineId);
          $("#editMedicineModal").modal("show");
        });

      row
        .querySelector(".delete-medicine")
        .addEventListener("click", function () {
          const medicineId = this.getAttribute("data-id");
          const medicineName = this.getAttribute("data-name");

          document.getElementById("deleteMedicineId").value = medicineId;
          document.getElementById("deleteMedicineName").textContent =
            medicineName;

          $("#deleteMedicineModal").modal("show");
        });
    });
  }

  /**
   * Áp dụng bộ lọc vào danh sách thuốc
   * @param {Array} medicines Danh sách thuốc gốc
   * @return {Array} Danh sách thuốc đã lọc
   */
  function filterMedicines(medicines) {
    console.log("🔍 Bắt đầu lọc với:", currentFilters);

    // Kiểm tra đầu vào
    if (!medicines || !Array.isArray(medicines)) {
      console.error("❌ Dữ liệu đầu vào không hợp lệ:", medicines);
      return [];
    }

    if (!currentFilters.expiry && !currentFilters.amount) {
      console.log("ℹ️ Không có bộ lọc nào được áp dụng, giữ nguyên danh sách");
      return medicines;
    }

    const results = medicines.filter((medicine) => {
      let keepItem = true;

      // Lọc theo hạn sử dụng
      if (currentFilters.expiry) {
        const today = new Date();
        try {
          console.log(
            `🔍 Kiểm tra hạn thuốc ${medicine.medicine_id}, EXP:`,
            medicine.EXP
          );

          // Sử dụng hàm parseDate để xử lý ngày dd-mm-yyyy
          const expDate = parseDate(medicine.EXP);

          // Kiểm tra nếu ngày không hợp lệ
          if (!expDate) {
            console.warn(
              `⚠️ Thuốc ${medicine.medicine_id} có hạn sử dụng không hợp lệ:`,
              medicine.EXP
            );
            return false;
          }

          // Tính số ngày còn lại
          const daysRemaining = Math.floor(
            (expDate - today) / (1000 * 60 * 60 * 24)
          );
          console.log(
            `📅 Thuốc ${medicine.medicine_id} còn ${daysRemaining} ngày`
          );

          // Logic lọc
          if (
            currentFilters.expiry === "near" &&
            (daysRemaining < 0 || daysRemaining > 7)
          ) {
            console.log(
              `❌ Loại bỏ thuốc ${medicine.medicine_id}: không phải sắp hết hạn (${daysRemaining} ngày)`
            );
            keepItem = false;
          } else if (
            currentFilters.expiry === "expired" &&
            daysRemaining >= 0
          ) {
            console.log(
              `❌ Loại bỏ thuốc ${medicine.medicine_id}: chưa hết hạn`
            );
            keepItem = false;
          } else if (currentFilters.expiry === "valid" && daysRemaining < 0) {
            console.log(`❌ Loại bỏ thuốc ${medicine.medicine_id}: đã hết hạn`);
            keepItem = false;
          }
        } catch (error) {
          console.error(
            `❌ Lỗi khi xử lý hạn sử dụng cho thuốc ${medicine.medicine_id}:`,
            error
          );
          keepItem = false;
        }
      }

      // Lọc theo số lượng
      if (keepItem && currentFilters.amount) {
        console.log(
          `🔢 Kiểm tra số lượng thuốc ${medicine.medicine_id}, amount:`,
          medicine.amount
        );

        if (currentFilters.amount === "low" && medicine.amount >= 10) {
          console.log(
            `❌ Loại bỏ thuốc ${medicine.medicine_id}: số lượng không thấp (${medicine.amount})`
          );
          keepItem = false;
        } else if (currentFilters.amount === "out" && medicine.amount > 0) {
          console.log(
            `❌ Loại bỏ thuốc ${medicine.medicine_id}: còn hàng (${medicine.amount})`
          );
          keepItem = false;
        } else if (
          currentFilters.amount === "available" &&
          medicine.amount <= 0
        ) {
          console.log(`❌ Loại bỏ thuốc ${medicine.medicine_id}: hết hàng`);
          keepItem = false;
        }
      }

      if (keepItem) {
        console.log(
          `✅ Giữ lại thuốc ${medicine.medicine_id}: phù hợp với bộ lọc`
        );
      }

      return keepItem;
    });

    console.log(
      `🔍 Kết quả lọc: ${results.length}/${medicines.length} thuốc phù hợp`
    );
    return results;
  }

  /**
   * Lấy trạng thái hạn sử dụng của thuốc
   * @param {string} expDateStr Ngày hết hạn (dd-mm-yyyy)
   * @return {Object} Trạng thái hạn sử dụng
   */
  function getExpiryStatus(expDateStr) {
    const today = new Date();
    const expDate = parseDate(expDateStr);

    // Kiểm tra date có hợp lệ không
    if (!expDate) {
      console.warn("❗ Ngày hết hạn không hợp lệ:", expDateStr);
      return { code: "unknown", text: "Không xác định" };
    }

    // Tính số ngày còn lại
    const daysRemaining = Math.floor((expDate - today) / (1000 * 60 * 60 * 24));

    if (daysRemaining < 0) {
      return { code: "expired", text: "Đã hết hạn" };
    } else if (daysRemaining <= 7) {
      return { code: "near", text: `Còn ${daysRemaining} ngày` };
    } else {
      return { code: "valid", text: "Còn hạn" };
    }
  }

  /**
   * Lấy class CSS cho trạng thái hạn sử dụng
   * @param {Object} status Trạng thái hạn sử dụng
   * @return {Object} Các class CSS
   */
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

  /**
   * Cập nhật thông tin phân trang
   * @param {Object} data Dữ liệu từ API
   */
  function updatePaginationInfo(data) {
    // Cập nhật biến toàn cục
    totalPages = data.total_pages || 1;
    currentPage = data.page || 1;

    // Hiển thị thông tin phân trang
    const paginationInfo = document.getElementById("paginationInfo");
    if (paginationInfo) {
      paginationInfo.textContent = `Hiển thị ${data.data.length} trên ${data.total} kết quả`;
    }

    // Cập nhật UI phân trang
    updatePaginationUI();
  }

  /**
   * Cập nhật giao diện phân trang
   */
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

    // Tính toán phạm vi trang hiển thị
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

  /**
   * Cập nhật giao diện sắp xếp
   */
  function updateSortUI() {
    // Xóa tất cả các biểu tượng sắp xếp hiện tại
    document.querySelectorAll(".sortable i").forEach((icon) => {
      icon.className = "fas fa-sort text-muted ml-1";
    });

    // Thêm biểu tượng sắp xếp mới
    const column = document.querySelector(
      `.sortable[data-sort="${sortField}"]`
    );
    if (column) {
      const icon = column.querySelector("i");
      if (icon) {
        icon.className = `fas fa-sort-${
          sortDirection === "asc" ? "up" : "down"
        } text-primary ml-1`;
      }
    }
  }

  /**
   * Đặt lại các bộ lọc
   */
  function resetFilters() {
    console.log("🔄 Đặt lại tất cả bộ lọc");

    currentFilters = {
      expiry: "",
      amount: "",
    };

    // Đặt lại chế độ xem thuốc sắp hết hạn
    viewingNearExpiry = false;

    document.getElementById("expiry-filter").value = "";
    document.getElementById("amount-filter").value = "";

    console.log("✅ Đã reset bộ lọc:", currentFilters);
  }

  /**
   * Tải chi tiết thuốc từ API
   * @param {string} id ID của thuốc
   */
  function loadMedicineDetails(id) {
    // Lưu ID thuốc đang xem
    selectedMedicineId = id;

    const detailsContainer = document.getElementById("medicineDetails");
    detailsContainer.innerHTML = `
      <div class="text-center py-3">
        <div class="spinner-border text-primary" role="status">
          <span class="sr-only">Đang tải...</span>
        </div>
      </div>
    `;

    fetch(`/UDPT-QLBN/Medicine/api_getMedicineById/${id}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Lỗi HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((medicine) => {
        console.log("✅ Tải chi tiết thuốc thành công:", medicine);

        // Tính toán trạng thái hạn sử dụng
        const expStatus = getExpiryStatus(medicine.EXP);
        const expiryClass = getExpiryClass(expStatus);

        // Tạo nội dung chi tiết
        detailsContainer.innerHTML = `
          <h3 class="mb-3">${medicine.name}</h3>
          
          <div class="mb-4">
            <span class="badge ${expiryClass.badge}">
              <i class="fas fa-clock mr-1"></i> ${expStatus.text}
            </span>
          </div>
          
          <div class="table-responsive">
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <th class="bg-light">Mã thuốc</th>
                  <td>${medicine.medicine_id}</td>
                </tr>
                <tr>
                  <th class="bg-light">Ngày sản xuất</th>
                  <td>${formatDate(medicine.MFG)}</td>
                </tr>
                <tr>
                  <th class="bg-light">Hạn sử dụng</th>
                  <td class="${expiryClass.text}">${formatDate(
          medicine.EXP
        )}</td>
                </tr>
                <tr>
                  <th class="bg-light">Số lượng</th>
                  <td>${medicine.amount.toLocaleString()} ${medicine.unit}</td>
                </tr>
                <tr>
                  <th class="bg-light">Đơn giá</th>
                  <td>${medicine.price.toLocaleString()} đ</td>
                </tr>
              </tbody>
            </table>
          </div>
        `;
      })
      .catch((error) => {
        console.error("❌ Lỗi khi tải chi tiết thuốc:", error);
        detailsContainer.innerHTML = `
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle mr-1"></i> 
            Không thể tải thông tin thuốc. Vui lòng thử lại sau.
          </div>
        `;
      });
  }

  /**
   * Tải thông tin thuốc vào form chỉnh sửa
   * @param {string} id ID của thuốc
   */
  function populateEditForm(id) {
    console.log("📝 Đang tải thông tin thuốc để chỉnh sửa, ID:", id);

    // Hiển thị loading nếu cần
    document.getElementById("editMedicineForm").classList.add("loading");

    fetch(`/UDPT-QLBN/Medicine/api_getMedicineById/${id}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Lỗi HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((medicine) => {
        console.log("✅ Tải chi tiết thuốc thành công:", medicine);

        // Điền thông tin vào form
        document.getElementById("editMedicineId").value = medicine.medicine_id;
        document.getElementById("editMedicineName").value = medicine.name;
        document.getElementById("editMedicineMFG").value = formatDateForInput(
          medicine.MFG
        );
        document.getElementById("editMedicineEXP").value = formatDateForInput(
          medicine.EXP
        );
        document.getElementById("editMedicineAmount").value = medicine.amount;
        document.getElementById("editMedicineUnit").value = medicine.unit;
        document.getElementById("editMedicinePrice").value = medicine.price;

        // Bỏ loading state
        document.getElementById("editMedicineForm").classList.remove("loading");
      })
      .catch((error) => {
        console.error("❌ Lỗi khi tải thông tin thuốc:", error);
        showAlert(
          "Không thể tải thông tin thuốc. Vui lòng thử lại sau.",
          "danger"
        );
        $("#editMedicineModal").modal("hide");
      });
  }

  /**
   * Thêm thuốc mới
   */
  function addMedicine() {
    // Lấy dữ liệu từ form
    const name = document.getElementById("medicineName").value;
    const MFG = document.getElementById("medicineMFG").value;
    const EXP = document.getElementById("medicineEXP").value;
    const amount = document.getElementById("medicineAmount").value;
    const unit = document.getElementById("medicineUnit").value;
    const price = document.getElementById("medicinePrice").value;

    // Kiểm tra dữ liệu hợp lệ
    if (!validateMedicineForm("addMedicineForm")) {
      return;
    }

    // Chuẩn bị dữ liệu gửi lên server
    const medicineData = {
      name,
      MFG,
      EXP,
      amount: parseInt(amount),
      unit,
      price: parseFloat(price),
    };

    // Gửi request tạo thuốc mới
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
        console.log("✅ Thêm thuốc thành công:", data);

        // Đóng modal và reset form
        $("#addMedicineModal").modal("hide");
        document.getElementById("addMedicineForm").reset();

        // Thông báo thành công
        showAlert(`Đã thêm thuốc "${name}" thành công!`, "success");

        // Tải lại danh sách thuốc
        loadMedicines();
      })
      .catch((error) => {
        console.error("❌ Lỗi khi thêm thuốc:", error);
        showAlert(`Không thể thêm thuốc. Lỗi: ${error.message}`, "danger");
      });
  }

  /**
   * Cập nhật thông tin thuốc
   */
  function updateMedicine() {
    // Lấy dữ liệu từ form
    const id = document.getElementById("editMedicineId").value;
    const MFG = document.getElementById("editMedicineMFG").value;
    const EXP = document.getElementById("editMedicineEXP").value;
    const amount = document.getElementById("editMedicineAmount").value;
    const unit = document.getElementById("editMedicineUnit").value;
    const price = document.getElementById("editMedicinePrice").value;

    // Kiểm tra dữ liệu hợp lệ
    if (!validateMedicineForm("editMedicineForm")) {
      return;
    }

    // Chuẩn bị dữ liệu gửi lên server
    const medicineData = {
      MFG,
      EXP,
      amount: parseInt(amount),
      unit,
      price: parseFloat(price),
    };

    // Gửi request cập nhật thuốc
    fetch(`/UDPT-QLBN/Medicine/api_updateMedicine/${id}`, {
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
        console.log("✅ Cập nhật thuốc thành công:", data);

        // Đóng modal
        $("#editMedicineModal").modal("hide");

        // Thông báo thành công
        showAlert(`Đã cập nhật thuốc thành công!`, "success");

        // Tải lại danh sách thuốc
        loadMedicines();
      })
      .catch((error) => {
        console.error("❌ Lỗi khi cập nhật thuốc:", error);
        showAlert(`Không thể cập nhật thuốc. Lỗi: ${error.message}`, "danger");
      });
  }

  /**
   * Xóa thuốc
   */
  function deleteMedicine() {
    const id = document.getElementById("deleteMedicineId").value;
    const name = document.getElementById("deleteMedicineName").textContent;

    fetch(`/UDPT-QLBN/Medicine/api_deleteMedicine/${id}`, {
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
        console.log("✅ Xóa thuốc thành công:", data);

        // Đóng modal
        $("#deleteMedicineModal").modal("hide");

        // Thông báo thành công
        showAlert(`Đã xóa thuốc "${name}" thành công!`, "success");

        // Tải lại danh sách thuốc
        loadMedicines();
      })
      .catch((error) => {
        console.error("❌ Lỗi khi xóa thuốc:", error);
        showAlert(`Không thể xóa thuốc. Lỗi: ${error.message}`, "danger");

        // Đóng modal
        $("#deleteMedicineModal").modal("hide");
      });
  }

  /**
   * Kiểm tra dữ liệu form thuốc
   * @param {string} formId ID của form cần kiểm tra
   * @return {boolean} Dữ liệu form hợp lệ hay không
   */
  function validateMedicineForm(formId) {
    const form = document.getElementById(formId);

    // Đặt class was-validated để hiển thị thông báo lỗi
    form.classList.add("was-validated");

    // Kiểm tra các trường bắt buộc
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
        document.getElementById("medicineEXP").nextElementSibling.textContent =
          "Hạn sử dụng phải sau ngày sản xuất";
        isValid = false;
      }
    } else if (formId === "editMedicineForm") {
      const mfg = new Date(document.getElementById("editMedicineMFG").value);
      const exp = new Date(document.getElementById("editMedicineEXP").value);

      if (mfg >= exp) {
        document.getElementById("editMedicineEXP").classList.add("is-invalid");
        document.getElementById(
          "editMedicineEXP"
        ).nextElementSibling.textContent = "Hạn sử dụng phải sau ngày sản xuất";
        isValid = false;
      }
    }

    return isValid;
  }

  /**
   * Hiển thị thông báo
   * @param {string} message Nội dung thông báo
   * @param {string} type Loại thông báo (success, danger, warning, info)
   * @param {number} duration Thời gian hiển thị (ms)
   */
  function showAlert(message, type = "info", duration = 5000) {
    const alertContainer = document.getElementById("alert-container");

    // Tạo alert
    const alert = document.createElement("div");
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
      ${message}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    `;

    // Thêm alert vào container
    alertContainer.appendChild(alert);

    // Tự động ẩn alert sau thời gian chỉ định
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
   * Hiển thị/ẩn loading indicator
   * @param {boolean} show Hiển thị loading hay không
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
   * Parse date từ chuỗi dd-mm-yyyy thành đối tượng Date hợp lệ
   * @param {string} dateStr Chuỗi ngày định dạng dd-mm-yyyy
   * @return {Date|null} Đối tượng Date hoặc null nếu không hợp lệ
   */
  function parseDate(dateStr) {
    if (!dateStr) return null;

    // Kiểm tra định dạng dd-mm-yyyy
    const ddmmyyyyRegex = /^(\d{2})-(\d{2})-(\d{4})$/;
    const match = dateStr.match(ddmmyyyyRegex);

    if (match) {
      const [, day, month, year] = match;
      // Chuyển thành yyyy-mm-dd cho JS parse
      const isoDate = `${year}-${month}-${day}`;
      const date = new Date(isoDate);
      return isNaN(date.getTime()) ? null : date;
    }

    // Nếu là định dạng khác, thử parse trực tiếp
    const date = new Date(dateStr);
    return isNaN(date.getTime()) ? null : date;
  }

  /**
   * Format date từ chuỗi dd-mm-yyyy sang dd-mm-yyyy (giữ nguyên)
   * @param {string} dateStr Date string từ API (dd-mm-yyyy)
   * @return {string} Date string đã format (dd-mm-yyyy)
   */
  function formatDate(dateStr) {
    if (!dateStr) return "";

    // Nếu đã đúng định dạng dd-mm-yyyy, giữ nguyên
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) {
      return dateStr;
    }

    // Trường hợp khác, thử parse và format lại
    const date = parseDate(dateStr);
    if (date) {
      const day = String(date.getDate()).padStart(2, "0");
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const year = date.getFullYear();
      return `${day}-${month}-${year}`;
    }

    console.warn("❗ Không thể format ngày:", dateStr);
    return dateStr;
  }

  /**
   * Format date từ dd-mm-yyyy sang yyyy-mm-dd (cho input type="date")
   * @param {string} dateStr Date string từ API (dd-mm-yyyy)
   * @return {string} Date string cho input (yyyy-mm-dd)
   */
  function formatDateForInput(dateStr) {
    if (!dateStr) return "";

    // Xử lý định dạng dd-mm-yyyy
    const ddmmyyyyRegex = /^(\d{2})-(\d{2})-(\d{4})$/;
    const match = dateStr.match(ddmmyyyyRegex);

    if (match) {
      const [, day, month, year] = match;
      return `${year}-${month}-${day}`;
    }

    // Nếu đã đúng định dạng yyyy-mm-dd, trả về luôn
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
      return dateStr;
    }

    // Thử parse và format lại
    const date = parseDate(dateStr);
    if (date) {
      const day = String(date.getDate()).padStart(2, "0");
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const year = date.getFullYear();
      return `${year}-${month}-${day}`;
    }

    console.warn("❗ Không thể format ngày cho input:", dateStr);
    return "";
  }
});
