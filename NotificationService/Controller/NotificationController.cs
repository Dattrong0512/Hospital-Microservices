using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.ApiExplorer;
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
        /// <param name="request">Thông tin lịch hẹn, email bệnh nhân và bác sĩ.</param>
        /// <returns>Trả về 200 nếu gửi thành công, 400 nếu dữ liệu không hợp lệ.</returns>
        [HttpPost("send-appointment")]
        [ProducesResponseType(StatusCodes.Status200OK)]
        [ProducesResponseType(StatusCodes.Status400BadRequest)]
        public async Task<IActionResult> SendAppointmentNotification([FromBody] NotificationRequest request)
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(ModelState);
            }

            _logger.LogInformation("Received request to send appointment notification for AppointmentId: {AppointmentId}", request.AppointmentId);

            await _notificationService.SendAppointmentNotificationAsync(
                request.PatientEmail, "PATIENT", request.DoctorName,
                request.PatientName, request.AppointmentDateTime, request.AppointmentId
            );

            await _notificationService.SendAppointmentNotificationAsync(
                request.DoctorEmail, "DOCTOR", request.DoctorName,
                request.PatientName, request.AppointmentDateTime, request.AppointmentId
            );

            return Ok("Appointment notifications sent and logged.");
        }

        /// <summary>
        /// Gửi thông báo đơn thuốc đã sẵn sàng cho bệnh nhân.
        /// </summary>
        /// <param name="request">Thông tin đơn thuốc, email và tên bệnh nhân.</param>
        /// <returns>Trả về 200 nếu gửi thành công, 400 nếu dữ liệu không hợp lệ.</returns>
        [HttpPost("send-prescription-ready")]
        [ProducesResponseType(StatusCodes.Status200OK)]
        [ProducesResponseType(StatusCodes.Status400BadRequest)]
        public async Task<IActionResult> SendPrescriptionReadyNotification([FromBody] PrescriptionNotificationRequest request)
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(ModelState);
            }

            _logger.LogInformation("Received request to send prescription ready notification for PrescriptionId: {PrescriptionId}", request.PrescriptionId);

            await _notificationService.SendPrescriptionReadyNotificationAsync(
                request.PatientEmail,
                request.PatientName,
                request.PrescriptionDetails,
                request.PrescriptionId
            );

            return Ok("Prescription ready notification sent and logged.");
        }

        /// <summary>
        /// Lấy thông tin chi tiết một thông báo theo Id.
        /// </summary>
        /// <param name="id">Id của thông báo.</param>
        /// <returns>Trả về Notification nếu tìm thấy, 404 nếu không tồn tại.</returns>
        [HttpGet("{id}")]
        [ProducesResponseType(StatusCodes.Status200OK, Type = typeof(Notification))]
        [ProducesResponseType(StatusCodes.Status404NotFound)]
        public async Task<ActionResult<Notification>> GetNotification(string id)
        {
            var notification = await _notificationService.GetNotificationByIdAsync(id);

            if (notification == null)
            {
                return NotFound();
            }

            return notification;
        }
    }
}