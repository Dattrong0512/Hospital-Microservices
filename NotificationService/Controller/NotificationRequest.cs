using System.ComponentModel.DataAnnotations;

namespace NotificationService.Controllers
{
    public class NotificationRequest
    {
        [Required]
        [EmailAddress]
        public string PatientEmail { get; set; } = string.Empty;
        [Required]
        [EmailAddress]
        public string DoctorEmail { get; set; } = string.Empty;
        [Required]
        public string PatientName { get; set; } = string.Empty;
        [Required]
        public string DoctorName { get; set; } = string.Empty;
        [Required]
        public DateTime AppointmentDateTime { get; set; }
        [Required]
        public string AppointmentId { get; set; } = string.Empty;
    }
}