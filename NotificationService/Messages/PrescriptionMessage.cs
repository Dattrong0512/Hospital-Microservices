using System.Text.Json.Serialization;

namespace NotificationService.Messages
{
    public class PrescriptionMessage
    {
        [JsonPropertyName("type")]
        public string Type { get; set; } // "PrescriptionReady"

        [JsonPropertyName("patient_email")]
        public string PatientEmail { get; set; }

        [JsonPropertyName("patient_fullname")]
        public string PatientFullname { get; set; }

        [JsonPropertyName("prescription_detail")]
        public string PrescriptionDetail { get; set; }
    }
}