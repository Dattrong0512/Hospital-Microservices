using System.ComponentModel.DataAnnotations;

namespace NotificationService.Models
{
    /// <summary>
    /// Đại diện cho yêu cầu gửi thông báo khi đơn thuốc đã sẵn sàng.
    /// </summary>
    /// <remarks>
    /// Model này được sử dụng khi một hệ thống khác muốn thông báo cho bệnh nhân
    /// rằng đơn thuốc của họ đã được chuẩn bị và sẵn sàng để lấy.
    /// </remarks>
    public class PrescriptionNotificationRequest
    {
        /// <summary>
        /// Địa chỉ email của bệnh nhân sẽ nhận thông báo đơn thuốc.
        /// </summary>
        /// <example>patient@example.com</example>
        [Required(ErrorMessage = "Patient email is required.")]
        [EmailAddress(ErrorMessage = "Invalid patient email format.")]
        public string PatientEmail { get; set; } = string.Empty;

        /// <summary>
        /// Họ và tên đầy đủ của bệnh nhân.
        /// </summary>
        /// <example>Nguyễn Văn B</example>
        [Required(ErrorMessage = "Patient name is required.")]
        public string PatientName { get; set; } = string.Empty;

        /// <summary>
        /// Chi tiết về đơn thuốc, bao gồm tên thuốc, liều lượng, v.v.
        /// </summary>
        /// <example>Paracetamol 500mg x 10 viên, Amoxicillin 250mg x 7 ngày, Uống sau ăn.</example>
        [Required(ErrorMessage = "Prescription details are required.")]
        public string PrescriptionDetails { get; set; } = string.Empty;
    }
}