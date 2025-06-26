// Services/ReportService.cs
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using ReportService.Data;
using ReportService.Models;
using System.Globalization;

namespace ReportService.Services
{
    public class ReportService : IReportService
    {
        private readonly ApplicationDbContext _dbContext;
        private readonly ILogger<ReportService> _logger;

        public ReportService(ApplicationDbContext dbContext, ILogger<ReportService> logger)
        {
            _dbContext = dbContext;
            _logger = logger;
        }
        // Phương thức Thống kê số lượng bệnh nhân theo tháng
        public async Task<List<MonthlyPatientStats>> GetMonthlyPatientStatisticsAsync()
        {
            _logger.LogInformation("Generating monthly patient statistics from PostgreSQL.");

            try
            {
                var stats = await _dbContext.Patients
                    .GroupBy(p => new { p.RegisteredAt.Year, p.RegisteredAt.Month })
                    .Select(g => new MonthlyPatientStats
                    {
                        Year = g.Key.Year,
                        Month = g.Key.Month,
                        PatientCount = g.Count()
                    })
                    .OrderBy(s => s.Year)
                    .ThenBy(s => s.Month)
                    .ToListAsync();

                foreach (var item in stats)
                {
                    item.MonthName = CultureInfo.CurrentCulture.DateTimeFormat.GetMonthName(item.Month);
                }

                _logger.LogInformation("Generated {Count} monthly patient statistics entries.", stats.Count);
                return stats;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error getting monthly patient statistics from PostgreSQL.");
                throw;
            }
        }

        // Thống kê số lượng đơn thuốc đã cấp theo tháng
        public async Task<List<MonthlyPrescriptionStats>> GetMonthlyPrescriptionStatisticsAsync()
        {
            _logger.LogInformation("Generating monthly prescription statistics from PostgreSQL.");

            try
            {
                var stats = await _dbContext.Prescriptions
                    .GroupBy(p => new { p.IssuedAt.Year, p.IssuedAt.Month })
                    .Select(g => new MonthlyPrescriptionStats
                    {
                        Year = g.Key.Year,
                        Month = g.Key.Month,
                        PrescriptionCount = g.Count()
                    })
                    .OrderBy(s => s.Year)
                    .ThenBy(s => s.Month)
                    .ToListAsync();

                foreach (var item in stats)
                {
                    item.MonthName = CultureInfo.CurrentCulture.DateTimeFormat.GetMonthName(item.Month);
                }

                _logger.LogInformation("Generated {Count} monthly prescription statistics entries.", stats.Count);
                return stats;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error getting monthly prescription statistics from PostgreSQL.");
                throw;
            }
        }
    }
}