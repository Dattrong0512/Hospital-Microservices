using ReportService.Models;
using System.Collections.Generic;
using System.Threading.Tasks;

namespace ReportService.Services
{
    public interface IReportService
    {
        Task<List<MonthlyPatientStats>> GetMonthlyPatientStatisticsAsync(int? year = null, int? month = null);
        Task<List<MonthlyPrescriptionStats>> GetMonthlyPrescriptionStatisticsAsync(int? year = null, int? month = null);
    }
}