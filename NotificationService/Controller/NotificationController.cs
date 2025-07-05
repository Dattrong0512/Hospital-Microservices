using Microsoft.AspNetCore.Mvc;
using NotificationService.Models;
using NotificationService.Services;

namespace NotificationService.Controllers
{
    [ApiController]
    [ApiVersion("0")]
    [Route("api/v{version:apiVersion}/[controller]")]
    public class NotificationController : ControllerBase
    {
        private readonly INotificationService _notificationService;
        private readonly ILogger<NotificationController> _logger;

        public NotificationController(INotificationService notificationService, ILogger<NotificationController> logger)
        {
            _notificationService = notificationService;
            _logger = logger;
        }

        /// <summary>
        /// Gửi thông báo nhắc nhở lịch khám cho bệnh nhân và bác sĩ.
        /// </summary>
        /// <remarks>
        /// Phương thức này dùng để gửi thông báo email nhắc nhở lịch hẹn cho cả bệnh nhân và bác sĩ.
        /// Nó nhận một đối tượng NotificationRequest chứa thông tin cần thiết về lịch hẹn.
        ///
        /// Ví dụ Request Body:
        ///
        ///     POST /api/v0/Notification/send-appointment
        ///     {
        ///        "patientEmail": "nguyenvana@example.com",
        ///        "doctorEmail": "dr.binh@example.com",
        ///        "patientName": "Nguyễn Văn A",
        ///        "doctorName": "Bác sĩ Bình",
        ///        "appointmentDateTime": "2025-07-04T10:00:00Z"
        ///     }
        ///
        /// </remarks>
        /// <param name="request">Thông tin lịch hẹn, bao gồm email, tên của bệnh nhân và bác sĩ, và thời gian cuộc hẹn.</param>
        /// <returns>Trả về Http 200 OK nếu thông báo được gửi và ghi log thành công.
        /// Trả về Http 400 Bad Request nếu dữ liệu đầu vào không hợp lệ.</returns>
        [HttpPost("send-appointment")]
        [ProducesResponseType(StatusCodes.Status200OK)]
        [ProducesResponseType(StatusCodes.Status400BadRequest)]
        public async Task<IActionResult> SendAppointmentNotification([FromBody] NotificationRequest request)
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(ModelState);
            }

            _logger.LogInformation("Received request to send appointment notification");

            await _notificationService.SendAppointmentNotificationAsync(
                request.PatientEmail, "PATIENT", request.DoctorName,
                request.PatientName, request.AppointmentDateTime
            );

            // Giả định bạn muốn gửi cho cả bác sĩ nếu email có
            if (!string.IsNullOrEmpty(request.DoctorEmail))
            {
                await _notificationService.SendAppointmentNotificationAsync(
                    request.DoctorEmail, "DOCTOR", request.DoctorName,
                    request.PatientName, request.AppointmentDateTime
                );
            }
            
            return Ok("Appointment notifications sent and logged.");
        }

        /// <summary>
        /// Gửi thông báo đơn thuốc đã sẵn sàng cho bệnh nhân.
        /// </summary>
        /// <remarks>
        /// API này dùng để thông báo cho bệnh nhân rằng đơn thuốc của họ đã sẵn sàng để lấy.
        ///
        /// Ví dụ Request Body:
        ///
        ///     POST /api/v0/Notification/send-prescription-ready
        ///     {
        ///        "patientEmail": "nguyenvana@example.com",
        ///        "patientName": "Nguyễn Văn A",
        ///        "prescriptionDetails": "Paracetamol 500mg x 10 viên, Amoxicillin 250mg x 7 ngày"
        ///     }
        ///
        /// </remarks>
        /// <param name="request">Thông tin đơn thuốc, email và tên bệnh nhân.</param>
        /// <returns>Trả về Http 200 OK nếu gửi thành công, Http 400 Bad Request nếu dữ liệu không hợp lệ.</returns>
        [HttpPost("send-prescription-ready")]
        [ProducesResponseType(StatusCodes.Status200OK)]
        [ProducesResponseType(StatusCodes.Status400BadRequest)]
        public async Task<IActionResult> SendPrescriptionReadyNotification([FromBody] PrescriptionNotificationRequest request)
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(ModelState);
            }

            _logger.LogInformation("Received request to send prescription ready notification for patient: {PatientEmail}", request.PatientEmail);

            await _notificationService.SendPrescriptionReadyNotificationAsync(
                request.PatientEmail,
                request.PatientName,
                request.PrescriptionDetails
            );

            return Ok("Prescription ready notification sent and logged.");
        }

        /// <summary>
        /// Lấy thông tin chi tiết một thông báo theo Id.
        /// </summary>
        /// <remarks>
        /// API này cho phép truy xuất thông tin chi tiết của một thông báo đã gửi dựa trên Id duy nhất của nó.
        /// Nếu không tìm thấy thông báo, hệ thống sẽ trả về lỗi 404 Not Found.
        /// </remarks>
        /// <param name="id">Id duy nhất của thông báo cần truy xuất.</param>
        /// <returns>Trả về đối tượng Notification nếu tìm thấy, Http 404 Not Found nếu không tồn tại.</returns>
        [HttpGet("{id}")]
        [ProducesResponseType(StatusCodes.Status200OK, Type = typeof(Notification))]
        [ProducesResponseType(StatusCodes.Status404NotFound)]
        public async Task<ActionResult<Notification>> GetNotification(string id)
        {
            var notification = await _notificationService.GetNotificationByIdAsync(id);

            if (notification == null)
            {
                _logger.LogWarning("Notification with Id {Id} not found.", id);
                return NotFound();
            }

            _logger.LogInformation("Successfully retrieved notification with Id {Id}.", id);
            return notification;
        }
    }
}