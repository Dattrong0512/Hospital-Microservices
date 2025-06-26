using MailKit.Net.Smtp;
using MailKit.Security;
using Microsoft.Extensions.Options;
using MimeKit;
using MongoDB.Driver;
using NotificationService.Models;

namespace NotificationService.Services
{
    public class NotificationService : INotificationService
    {
        private readonly IMongoCollection<Notification> _notificationsCollection;
        private readonly MailSettings _mailSettings;
        private readonly ILogger<NotificationService> _logger;

        public NotificationService(
            IOptions<MongoDBSettings> mongoDBSettings,
            IOptions<MailSettings> mailSettings,
            ILogger<NotificationService> logger)
        {
            var mongoClient = new MongoClient(mongoDBSettings.Value.ConnectionString);
            var mongoDatabase = mongoClient.GetDatabase(mongoDBSettings.Value.DatabaseName);
            _notificationsCollection = mongoDatabase.GetCollection<Notification>(
                mongoDBSettings.Value.CollectionName);
            _mailSettings = mailSettings.Value;
            _logger = logger;
        }

        public async Task SendAppointmentNotificationAsync(string recipientEmail, string recipientType,
                                                            string doctorName, string patientName,
                                                            DateTime appointmentDateTime, string appointmentId)
        {
            string subject;
            string content;

            if (recipientType.Equals("PATIENT", StringComparison.OrdinalIgnoreCase))
            {
                subject = "Nhắc nhở lịch khám của bạn";
                content = $"Kính gửi {patientName},\n\n" +
                          $"Lịch hẹn khám bệnh của bạn với Bác sĩ {doctorName} đã được xác nhận.\n" +
                          $"Thời gian: {appointmentDateTime:dd/MM/yyyy HH:mm}\n" +
                          $"Mã lịch hẹn: {appointmentId}\n\n" +
                          "Vui lòng đến đúng giờ. Nếu có bất kỳ thay đổi nào, chúng tôi sẽ thông báo lại.\n" +
                          "Trân trọng,\nBệnh viện ABC";
            }
            else if (recipientType.Equals("DOCTOR", StringComparison.OrdinalIgnoreCase))
            {
                subject = "Thông báo Lịch hẹn khám mới của bạn";
                content = $"Kính gửi Bác sĩ {doctorName},\n\n" +
                          $"Bạn có một lịch hẹn khám bệnh mới đã được xác nhận:\n" +
                          $"Bệnh nhân: {patientName}\n" +
                          $"Thời gian: {appointmentDateTime:dd/MM/yyyy HH:mm}\n" +
                          $"Mã lịch hẹn: {appointmentId}\n\n" +
                          "Vui lòng chuẩn bị và có mặt đúng giờ.\n" +
                          "Trân trọng,\nBan Quản lý Bệnh viện ABC";
            }
            else
            {
                _logger.LogWarning("Invalid recipient type '{RecipientType}' for appointment notification to {RecipientEmail}. Notification skipped.", recipientType, recipientEmail);
                return;
            }

            await SendAndLogNotification(recipientEmail, recipientType, subject, content, appointmentId);
        }

        public async Task SendPrescriptionReadyNotificationAsync(string patientEmail, string patientName,
                                                                 string prescriptionDetails, string prescriptionId)
        {
            var subject = "Thông báo: Đơn thuốc của bạn đã sẵn sàng!";
            var content = $"Kính gửi bệnh nhân {patientName},\n\n" +
                          $"Đơn thuốc của bạn đã được chuẩn bị và sẵn sàng để lấy. " +
                          $"Chi tiết đơn thuốc: {prescriptionDetails}\n\n" +
                          $"Vui lòng đến quầy thuốc để nhận đơn thuốc của bạn. Cảm ơn bạn!\n" +
                          "Bệnh viện ABC";

            await SendAndLogNotification(patientEmail, "PATIENT", subject, content, prescriptionId);
        }

        private async Task SendAndLogNotification(string recipientEmail, string recipientType,
                                                  string subject, string content, string relatedId)
        {
            var notification = new Notification
            {
                RecipientEmail = recipientEmail,
                RecipientType = recipientType,
                Subject = subject,
                Content = content,
                SentAt = DateTime.UtcNow,
                AppointmentId = relatedId
            };

            try
            {
                var email = new MimeMessage();
                email.From.Add(new MailboxAddress(_mailSettings.SenderName, _mailSettings.SenderEmail));
                email.To.Add(MailboxAddress.Parse(recipientEmail));
                email.Subject = subject;
                email.Body = new TextPart("plain") { Text = content };

                using var smtp = new SmtpClient();
                await smtp.ConnectAsync(_mailSettings.SmtpHost, _mailSettings.SmtpPort, SecureSocketOptions.StartTls);
                await smtp.AuthenticateAsync(_mailSettings.SmtpUsername, _mailSettings.SmtpPassword);
                await smtp.SendAsync(email);
                await smtp.DisconnectAsync(true);

                notification.SentSuccessfully = true;
                _logger.LogInformation("Email sent successfully to: {RecipientEmail}", recipientEmail);
            }
            catch (Exception ex)
            {
                notification.SentSuccessfully = false;
                _logger.LogError(ex, "Failed to send email to: {RecipientEmail}. Error: {ErrorMessage}", recipientEmail, ex.Message);
            }
            finally
            {
                await _notificationsCollection.InsertOneAsync(notification);
            }
        }

        public async Task<Notification?> GetNotificationByIdAsync(string id)
        {
            return await _notificationsCollection.Find(notification => notification.Id == id).FirstOrDefaultAsync();
        }
    }
}