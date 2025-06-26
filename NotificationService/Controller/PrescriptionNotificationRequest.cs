// Controllers/PrescriptionNotificationRequest.cs
using System.ComponentModel.DataAnnotations;

namespace NotificationService.Controllers
{
    public class PrescriptionNotificationRequest
    {
        [Required]
        [EmailAddress]
        public string PatientEmail { get; set; } = string.Empty;
        [Required]
        public string PatientName { get; set; } = string.Empty;
        [Required]
        public string PrescriptionDetails { get; set; } = string.Empty;
        [Required]
        public string PrescriptionId { get; set; } = string.Empty;
    }
}