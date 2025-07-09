<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .dashboard-card {
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border: none;
    }
    .chart-container {
        position: relative;
        height: 400px;
    }
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
    }
    .stats-card-2 {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        border-radius: 15px;
    }
    .stats-card-3 {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
        border-radius: 15px;
    }
    .filter-section {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .no-data-message {
        text-align: center;
        padding: 40px;
        color: #6c757d;
    }
    .no-data-message i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }
</style>
</head>
<body>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-primary">
                <i class="fas fa-chart-bar me-2"></i>
                Dashboard
            </h2>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x mb-3"></i>
                    <h4 id="totalPatients">0</h4>
                    <p class="mb-0">Tổng bệnh nhân</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stats-card-2">
                <div class="card-body text-center">
                    <i class="fas fa-pills fa-2x mb-3"></i>
                    <h4 id="totalMedicines">0</h4>
                    <p class="mb-0">Loại thuốc bán</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stats-card-3">
                <div class="card-body text-center">
                    <i class="fas fa-money-bill-wave fa-2x mb-3"></i>
                    <h4 id="totalRevenue">0 VNĐ</h4>
                    <p class="mb-0">Doanh thu thuốc</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row">
        <!-- Patient Statistics -->
        <div class="col-lg-6 mb-4">
            <div class="card dashboard-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-friends me-2"></i>
                        Thống kê bệnh nhân theo tháng
                    </h5>
                </div>
                <div class="card-body">
                    <div class="filter-section">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="patientYearSelect" class="form-label">Chọn năm:</label>
                                <select class="form-select" id="patientYearSelect">
                                    <!-- Options will be populated by JavaScript -->
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="patientChart"></canvas>
                        <div id="patientNoData" class="no-data-message" style="display: none;">
                            <i class="fas fa-chart-bar"></i>
                            <h5>Không có dữ liệu</h5>
                            <p>Chưa có thông tin bệnh nhân cho năm này</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medicine Revenue Statistics -->
        <div class="col-lg-6 mb-4">
            <div class="card dashboard-card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-prescription-bottle-alt me-2"></i>
                        Doanh thu thuốc theo tháng
                    </h5>
                </div>
                <div class="card-body">
                    <div class="filter-section">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="medicineMonthSelect" class="form-label">Chọn tháng:</label>
                                <select class="form-select" id="medicineMonthSelect">
                                    <option value="1">Tháng 1</option>
                                    <option value="2">Tháng 2</option>
                                    <option value="3">Tháng 3</option>
                                    <option value="4">Tháng 4</option>
                                    <option value="5">Tháng 5</option>
                                    <option value="6">Tháng 6</option>
                                    <option value="7">Tháng 7</option>
                                    <option value="8">Tháng 8</option>
                                    <option value="9">Tháng 9</option>
                                    <option value="10">Tháng 10</option>
                                    <option value="11">Tháng 11</option>
                                    <option value="12">Tháng 12</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="medicineYearSelect" class="form-label">Chọn năm:</label>
                                <select class="form-select" id="medicineYearSelect">
                                    <!-- Options will be populated by JavaScript -->
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="medicineChart"></canvas>
                        <div id="medicineNoData" class="no-data-message" style="display: none;">
                            <i class="fas fa-pills"></i>
                            <h5>Không có dữ liệu</h5>
                            <p>Chưa có thông tin bán thuốc cho tháng này</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Medicine Details Table -->
    <div class="row">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Chi tiết thuốc bán chạy
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>STT</th>
                                    <th>Tên thuốc</th>
                                    <th>Số lượng bán</th>
                                    <th>Tổng tiền</th>
                                    <th>Tháng/Năm</th>
                                </tr>
                            </thead>
                            <tbody id="medicineTableBody">
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin me-2"></i>
                                        Đang tải dữ liệu...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Global data variables to store fetched data
    let patientData = [];
    let medicineData = [];
    let totalMedicinesCount = 0;

    // Function to fetch patient statistics
    function fetchPatientStatistics(year) {
        return fetch(`/UDPT-QLBN/Report/api_getMonthlyPatientStatistics/${year}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    patientData = data.data;
                } else {
                    patientData = [];
                }
                return patientData;
            })
            .catch(error => {
                console.error('Error fetching patient statistics:', error);
                patientData = [];
                return patientData;
            });
    }

    // Function to fetch prescription statistics
    function fetchPrescriptionStatistics(year, month) {
        return fetch(`/UDPT-QLBN/Report/api_getMonthlyPrescriptionStatistics/${year}/${month}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    medicineData = data.data;
                } else {
                    medicineData = [];
                }
                return medicineData;
            })
            .catch(error => {
                console.error('Error fetching prescription statistics:', error);
                medicineData = [];
                return medicineData;
            });
    }

    // Function to fetch total medicines count
    function fetchTotalMedicines() {
        return fetch(`/UDPT-QLBN/Report/api_getTotalMedicines`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    totalMedicinesCount = data.total;
                } else {
                    totalMedicinesCount = 0;
                }
                return totalMedicinesCount;
            })
            .catch(error => {
                console.error('Error fetching total medicines count:', error);
                totalMedicinesCount = 0;
                return totalMedicinesCount;
            });
    }

    // Initialize charts
    let patientChart;
    let medicineChart;

    // Populate year selectors
    function populateYearSelectors() {
        const years = [2025, 2024, 2023, 2022, 2021, 2020];

        const patientYearSelect = document.getElementById('patientYearSelect');
        const medicineYearSelect = document.getElementById('medicineYearSelect');

        years.forEach(year => {
            const option1 = new Option(year, year);
            const option2 = new Option(year, year);
            patientYearSelect.add(option1);
            medicineYearSelect.add(option2);
        });

        // Set 2025 as default year
        patientYearSelect.value = 2025;
        medicineYearSelect.value = 2025;
    }

    // Create patient chart
    function createPatientChart(year) {
        const ctx = document.getElementById('patientChart').getContext('2d');
        const noDataDiv = document.getElementById('patientNoData');
        const canvas = document.getElementById('patientChart');
        
        fetchPatientStatistics(year).then(() => {
            // Filter data for selected year
            const yearData = patientData.filter(item => item.year == year);
            
            if (yearData.length === 0) {
                // Show no data message
                canvas.style.display = 'none';
                noDataDiv.style.display = 'block';
                if (patientChart) {
                    patientChart.destroy();
                }
                return;
            }

            // Hide no data message and show chart
            canvas.style.display = 'block';
            noDataDiv.style.display = 'none';
        
            // Create array for all 12 months
            const monthlyData = new Array(12).fill(0);
            yearData.forEach(item => {
                monthlyData[item.month - 1] = item.patientCount;
            });

            if (patientChart) {
                patientChart.destroy();
            }

            patientChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
                    datasets: [{
                        label: 'Số lượng bệnh nhân',
                        data: monthlyData,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: `Thống kê bệnh nhân năm ${year}`
                        }
                    }
                }
            });
        }); // Đóng .then()
    }

    // Create medicine chart
    function createMedicineChart(month, year) {
        const ctx = document.getElementById('medicineChart').getContext('2d');
        const noDataDiv = document.getElementById('medicineNoData');
        const canvas = document.getElementById('medicineChart');
        
        fetchPrescriptionStatistics(year, month).then(() => {
            // Filter data for selected month and year
            const filteredData = medicineData.filter(item => 
                item.month == month && item.year == year
            );

            if (filteredData.length === 0) {
                // Show no data message
                canvas.style.display = 'none';
                noDataDiv.style.display = 'block';
                if (medicineChart) {
                    medicineChart.destroy();
                }
                return;
            }

            // Hide no data message and show chart
            canvas.style.display = 'block';
            noDataDiv.style.display = 'none';

            const labels = filteredData.map(item => item.medicineName);
            const data = filteredData.map(item => item.totalAmount);

            if (medicineChart) {
                medicineChart.destroy();
            }

            medicineChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số lượng bán',
                        data: data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 159, 64, 0.8)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 205, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `Thuốc bán chạy tháng ${month}/${year}`
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }); // Đóng .then()
    }

    // Update medicine table
    function updateMedicineTable(month, year) {
        fetchPrescriptionStatistics(year, month).then(() => {
            const filteredData = medicineData.filter(item => 
                item.month == month && item.year == year
            );

            const tbody = document.getElementById('medicineTableBody');
            tbody.innerHTML = '';

            if (filteredData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            <i class="fas fa-info-circle me-2"></i>
                            Không có dữ liệu thuốc cho tháng ${month}/${year}
                        </td>
                    </tr>
                `;
                return;
            }

            filteredData.forEach((item, index) => {
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.medicineName}</td>
                        <td><span class="badge bg-primary">${item.totalAmount}</span></td>
                        <td><span class="badge bg-success">${formatCurrency(item.totalPrice)}</span></td>
                        <td>${month}/${year}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        });
    }

    // Calculate total revenue
    function calculateTotalRevenue() {
        let totalRevenue = 0;
        console.log("Medicine Data:", medicineData); // Debug
        medicineData.forEach(item => {
            totalRevenue += item.totalPrice || 0;
        });
        console.log("Calculated Total Revenue:", totalRevenue); // Debug
        return totalRevenue;
    }

    // Format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + ' VNĐ';
    }

    // Update stats cards
    function updateStatsCards() {
        // Calculate total patients
        const totalPatients = patientData.reduce((sum, item) => sum + item.patientCount, 0);
        document.getElementById('totalPatients').textContent = totalPatients || 'Không có dữ liệu';

        // Update total medicines count
        document.getElementById('totalMedicines').textContent = totalMedicinesCount || 'Không có dữ liệu';

        // Calculate total revenue
        const totalRevenue = calculateTotalRevenue();
        document.getElementById('totalRevenue').textContent = totalRevenue > 0 ? formatCurrency(totalRevenue) : 'Không có dữ liệu';
    }

    // Event listeners
    document.getElementById('patientYearSelect').addEventListener('change', function() {
        createPatientChart(this.value);
    });

    document.getElementById('medicineMonthSelect').addEventListener('change', function() {
        const year = document.getElementById('medicineYearSelect').value;
        const month = this.value;
        fetchPrescriptionStatistics(year, month).then(() => {
            createMedicineChart(month, year);
            updateMedicineTable(month, year);
            updateStatsCards();
        });
    });

    document.getElementById('medicineYearSelect').addEventListener('change', function() {
        const month = document.getElementById('medicineMonthSelect').value;
        const year = this.value;
        fetchPrescriptionStatistics(year, month).then(() => {
            createMedicineChart(month, year);
            updateMedicineTable(month, year);
            updateStatsCards();
        });
    });
    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
        populateYearSelectors();
        
        const defaultYear = 2025;
        const currentMonth = new Date().getMonth() + 1;
        
        // Set current month as default
        document.getElementById('medicineMonthSelect').value = currentMonth;
        
        Promise.all([
            fetchPatientStatistics(defaultYear),
            fetchPrescriptionStatistics(defaultYear, currentMonth),
            fetchTotalMedicines()
        ]).then(() => {
            createPatientChart(defaultYear);
            createMedicineChart(currentMonth, defaultYear);
            updateMedicineTable(currentMonth, defaultYear);
            updateStatsCards();
        }).catch(error => {
            console.error('Error during initial data fetch:', error);
        });
    });
</script>