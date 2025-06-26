using NotificationService.Models;

namespace NotificationService.Services
{
    public interface INotificationService
    {
        Task SendAppointmentNotificationAsync(string recipientEmail, string recipientType,
                                              string doctorName, string patientName,
                                              DateTime appointmentDateTime, string appointmentId);
        Task SendPrescriptionReadyNotificationAsync(string patientEmail, string patientName,
                                                    string prescriptionDetails, string prescriptionId);

        Task<Notification?> GetNotificationByIdAsync(string id);
    }
}