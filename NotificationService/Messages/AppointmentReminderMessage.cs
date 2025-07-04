using System;
using System.Text.Json.Serialization; 

namespace NotificationService.Messages //1 message gửi cho cả2 role
{
    public class AppointmentReminderMessage
    {
        [JsonPropertyName("type")]
        public string MessageType { get; set; } = string.Empty;

        [JsonPropertyName("patient_fullname")]
        public string PatientName { get; set; } = string.Empty;

        [JsonPropertyName("patient_email")]
        public string PatientEmail { get; set; } = string.Empty;

        [JsonPropertyName("doctor_fullname")]
        public string DoctorName { get; set; } = string.Empty;

        [JsonPropertyName("doctor_email")]
        public string DoctorEmail { get; set; } = string.Empty;

        [JsonPropertyName("date")]
        public string Date { get; set; } = string.Empty;

        [JsonPropertyName("started_time")]
        public string StartedTime { get; set; } = string.Empty;

        public DateTime AppointmentDateTime { get; set; }
    }
}