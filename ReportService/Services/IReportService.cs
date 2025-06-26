using ReportService.Models;
using System.Collections.Generic;
using System.Threading.Tasks;

namespace ReportService.Services
{
    public interface IReportService
    {
        Task<List<MonthlyPatientStats>> GetMonthlyPatientStatisticsAsync();
        Task<List<MonthlyPrescriptionStats>> GetMonthlyPrescriptionStatisticsAsync();
    }
}