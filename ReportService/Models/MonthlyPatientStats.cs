namespace ReportService.Models
{
    public class MonthlyPatientStats
    {
        public int Year { get; set; }
        public int Month { get; set; }
        public string MonthName { get; set; } = string.Empty;
        public long PatientCount { get; set; }
    }
}