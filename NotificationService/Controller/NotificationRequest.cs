using System;
using System.ComponentModel.DataAnnotations;

namespace NotificationService.Models
{
    /// <summary>
    /// Đại diện cho yêu cầu gửi thông báo lịch hẹn.
    /// </summary>
    public class NotificationRequest
    {
        /// <summary>
        /// Địa chỉ email của bệnh nhân.
        /// </summary>
        /// <example>patient@example.com</example>
        [Required(ErrorMessage = "Patient email is required.")]
        [EmailAddress(ErrorMessage = "Invalid patient email format.")]
        public string PatientEmail { get; set; } = string.Empty;

        /// <summary>
        /// Địa chỉ email của bác sĩ.
        /// </summary>
        /// <example>doctor@example.com</example>
        [Required(ErrorMessage = "Doctor email is required.")]
        [EmailAddress(ErrorMessage = "Invalid doctor email format.")]
        public string DoctorEmail { get; set; } = string.Empty;

        /// <summary>
        /// Họ và tên đầy đủ của bệnh nhân.
        /// </summary>
        /// <example>Nguyễn Thị B</example>
        [Required(ErrorMessage = "Patient name is required.")]
        public string PatientName { get; set; } = string.Empty;

        /// <summary>
        /// Họ và tên đầy đủ của bác sĩ.
        /// </summary>
        /// <example>Bác sĩ Trần C</example>
        [Required(ErrorMessage = "Doctor name is required.")]
        public string DoctorName { get; set; } = string.Empty;

        /// <summary>
        /// Thời gian và ngày của cuộc hẹn (theo định dạng ISO 8601).
        /// </summary>
        /// <example>2025-07-04T10:30:00Z</example>
        [Required(ErrorMessage = "Appointment date and time is required.")]
        public DateTime AppointmentDateTime { get; set; }
    }
}