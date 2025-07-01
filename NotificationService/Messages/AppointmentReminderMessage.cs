using System;

namespace NotificationService.Messages //1 message gửi cho cả2 role
{
    public class AppointmentReminderMessage
    {
        public string DoctorEmail { get; set; } = string.Empty;
        public string PatientEmail { get; set; } = string.Empty;
        public string DoctorName { get; set; } = string.Empty;
        public string PatientName { get; set; } = string.Empty;
        public DateTime AppointmentDateTime { get; set; }
        public string MessageType { get; set; } = "Appointment";
    }
}