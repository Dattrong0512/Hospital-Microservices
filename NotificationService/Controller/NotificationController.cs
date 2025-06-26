using Microsoft.AspNetCore.Mvc;
using NotificationService.Models;
using NotificationService.Services;

namespace NotificationService.Controllers
{
    [ApiController]
    [Route("api/[controller]")]
    public class NotificationController : ControllerBase
    {
        private readonly INotificationService _notificationService;
        private readonly ILogger<NotificationController> _logger;

        public NotificationController(INotificationService notificationService, ILogger<NotificationController> logger)
        {
            _notificationService = notificationService;
            _logger = logger;
        }
        // Endpoint để thông báo xác nhận lịch khám
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

        // Endpoint để thông báo đơn thuốc đã sẵn sàng
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