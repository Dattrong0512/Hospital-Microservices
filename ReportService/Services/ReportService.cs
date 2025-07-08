// Services/ReportService.cs
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using ReportService.Data;
using ReportService.Models;
using System.Globalization;
using System.ComponentModel.DataAnnotations.Schema;
using System.Threading.Tasks;
using System.Collections.Generic;
using System;
using System.Linq;

namespace ReportService.Services
{
    public class ReportService : IReportService
    {
        private readonly AppointmentDbContext _appointmentDb;
        private readonly PrescriptionDbContext _prescriptionDb;
        private readonly MedicineDbContext _medicineDb;
        private readonly ILogger<ReportService> _logger;

        public ReportService(AppointmentDbContext appointmentDb, PrescriptionDbContext prescriptionDb, MedicineDbContext medicineDb, ILogger<ReportService> logger)
        {
            _appointmentDb = appointmentDb;
            _prescriptionDb = prescriptionDb;
            _medicineDb = medicineDb;
            _logger = logger;
        }
        // Thống kê số lượng bệnh nhân duy nhất theo tháng
        public async Task<List<MonthlyPatientStats>> GetMonthlyPatientStatisticsAsync(int? year = null, int? month = null)
        {
            _logger.LogInformation("Generating monthly patient statistics from Appointment table.");

            try
            {
                var query = _appointmentDb.Appointments.AsQueryable();
                if (year.HasValue)
                    query = query.Where(a => a.Date.Year == year.Value);
                if (month.HasValue)
                    query = query.Where(a => a.Date.Month == month.Value);

                var stats = await query
                    .GroupBy(a => new { a.Date.Year, a.Date.Month })
                    .Select(g => new MonthlyPatientStats
                    {
                        Year = g.Key.Year,
                        Month = g.Key.Month,
                        PatientCount = g.Select(a => a.PatientId).Distinct().Count()
                    })
                    .OrderBy(s => s.Year)
                    .ThenBy(s => s.Month)
                    .ToListAsync();

                _logger.LogInformation("Generated {Count} monthly patient statistics entries from Appointment table.", stats.Count);
                return stats;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error getting monthly patient statistics from Appointment table.");
                throw;
            }
        }

        // Thống kê số lượng thuốc theo từng tên thuốc của từng tháng/năm
        public async Task<List<MonthlyPrescriptionStats>> GetMonthlyPrescriptionStatisticsAsync(int? year = null, int? month = null)
        {
            _logger.LogInformation("Generating monthly medicine statistics from Prescription, PrescriptionDetail, and Appointment tables.");

            try
            {
                // Lấy dữ liệu từ từng DB context
                var prescriptionDetails = await _prescriptionDb.PrescriptionDetails.ToListAsync();
                var prescriptions = await _prescriptionDb.Prescriptions.ToListAsync();
                var appointments = await _appointmentDb.Appointments.ToListAsync();
                var medicines = await _medicineDb.Medicines.ToListAsync();

                // Join trên bộ nhớ
                var stats = (from pd in prescriptionDetails
                             join p in prescriptions on pd.PrescriptionId equals p.PrescriptionId
                             join a in appointments on p.AppointmentId equals a.AppointmentId
                             join m in medicines on pd.MedicineId equals m.MedicineId
                             where (!year.HasValue || a.Date.Year == year.Value)
                                && (!month.HasValue || a.Date.Month == month.Value)
                             group new { pd, a, m } by new { a.Date.Year, a.Date.Month, m.Name } into g
                             select new MonthlyPrescriptionStats
                             {
                                 Year = g.Key.Year,
                                 Month = g.Key.Month,
                                 MedicineName = g.Key.Name,
                                 TotalAmount = g.Sum(x => x.pd.Amount)
                             })
                            .OrderBy(s => s.Year)
                            .ThenBy(s => s.Month)
                            .ThenBy(s => s.MedicineName)
                            .ToList();

                _logger.LogInformation("Generated {Count} monthly medicine statistics entries.", stats.Count);
                return stats;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error getting monthly prescription statistics.");
                throw;
            }
        }
    }
}