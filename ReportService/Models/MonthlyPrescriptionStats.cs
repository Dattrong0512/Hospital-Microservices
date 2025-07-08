namespace ReportService.Models
{
    public class MonthlyPrescriptionStats
    {
        public int Year { get; set; }
        public int Month { get; set; }
        public string MedicineName { get; set; } = string.Empty;
        public long TotalAmount { get; set; }
    }
}