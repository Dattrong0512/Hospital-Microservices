using NotificationService.Models;

namespace NotificationService.Services
{
    public interface INotificationService
    {
        Task SendAppointmentNotificationAsync(string recipientEmail, string recipientType,
                                              string doctorName, string patientName,
                                              DateTime appointmentDateTime);
        Task SendPrescriptionReadyNotificationAsync(string patientEmail, string patientName,
                                                    string prescriptionDetail);

        Task<Notification?> GetNotificationByIdAsync(string id);
    }
}