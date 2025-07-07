using System;
using System.Threading.Tasks;

namespace NotificationService.Models
{
    public class NotificationMessage
    {
        public string Type { get; set; }
    }

    public class AppointmentNotificationMessage : NotificationMessage
    {
        public AppointmentNotificationMessage()
        {
            Type = "Appointment";
        }
        public string Patient_fullname { get; set; } = string.Empty;
        public string Patient_email { get; set; } = string.Empty;
        public string Doctor_fullname { get; set; } = string.Empty;
        public string Doctor_email { get; set; } = string.Empty;

        public string Date { get; set; } = string.Empty;
        public string Started_time { get; set; } = string.Empty;
    }

    public class PrescriptionNotificationMessage : NotificationMessage
    {
        public PrescriptionNotificationMessage()
        {
            Type = "Prescription";
        }
        public string PatientEmail { get; set; } = string.Empty;
        public string PatientFullname { get; set; } = string.Empty;
        public string PrescriptionDetail { get; set; } = string.Empty;
    }
}