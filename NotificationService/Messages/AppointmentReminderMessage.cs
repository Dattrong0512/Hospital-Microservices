using System;
using System.Text.Json.Serialization; 

namespace NotificationService.Messages
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
        [JsonIgnore]
        public DateTime AppointmentDateTime
        {
            get
            {
                if (DateTime.TryParse($"{Date} {StartedTime}", out DateTime result))
                {
                    return result;
                }
                Console.WriteLine($"Warning: Could not parse date '{Date}' and time '{StartedTime}' into a valid DateTime.");
                return DateTime.MinValue;
            }
        }
    }
}