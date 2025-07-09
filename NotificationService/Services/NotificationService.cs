using MailKit.Net.Smtp;
using MailKit.Security;
using Microsoft.Extensions.Options;
using MimeKit;
using MongoDB.Driver;
using NotificationService.Models;
using Microsoft.Extensions.Logging;
using System;
using System.Linq;

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
            if (mongoDBSettings?.Value == null)
            {
                throw new ArgumentNullException(nameof(mongoDBSettings), "MongoDB settings are not configured.");
            }
            if (string.IsNullOrEmpty(mongoDBSettings.Value.ConnectionString) || string.IsNullOrEmpty(mongoDBSettings.Value.DatabaseName) || string.IsNullOrEmpty(mongoDBSettings.Value.CollectionName))
            {
                //_logger.LogError("MongoDB settings are incomplete. ConnectionString, DatabaseName, or CollectionName is missing.");
                throw new InvalidOperationException("Incomplete MongoDB settings configuration.");
            }

            var mongoClient = new MongoClient(mongoDBSettings.Value.ConnectionString);
            var mongoDatabase = mongoClient.GetDatabase(mongoDBSettings.Value.DatabaseName);

            // Kiểm tra và tạo collection nếu chưa tồn tại
            var collectionNames = mongoDatabase.ListCollectionNames().ToList();
            if (!collectionNames.Contains(mongoDBSettings.Value.CollectionName))
            {
                mongoDatabase.CreateCollection(mongoDBSettings.Value.CollectionName);
            }

            _notificationsCollection = mongoDatabase.GetCollection<Notification>(
                mongoDBSettings.Value.CollectionName);
            
            if (mailSettings?.Value == null)
            {
                throw new ArgumentNullException(nameof(mailSettings), "Mail settings are not configured.");
            }
            _mailSettings = mailSettings.Value;
            _logger = logger;
        }

        public async Task SendAppointmentNotificationAsync(
            string recipientEmail,
            string recipientType,
            string doctorName,
            string patientName,
            DateTime appointmentDateTime)
        {
            string subject;
            string content;

            // Xây dựng nội dung email dựa trên loại người nhận
            if (recipientType.Equals("PATIENT", StringComparison.OrdinalIgnoreCase))
            {
                subject = "Nhắc nhở lịch khám của bạn";
                content = $"Kính gửi {patientName},\n\n" +
                          $"Lịch hẹn khám bệnh của bạn với Bác sĩ {doctorName} đã được xác nhận.\n" +
                          $"Thời gian: {appointmentDateTime:dd/MM/yyyy HH:mm}\n\n" +
                          "Vui lòng đến đúng giờ. Nếu có bất kỳ thay đổi nào, chúng tôi sẽ thông báo lại.\n" +
                          "Trân trọng,\nBệnh viện ABC";
            }
            else if (recipientType.Equals("DOCTOR", StringComparison.OrdinalIgnoreCase))
            {
                subject = "Nhắc nhở Lịch khám của bạn";
                content = $"Kính gửi Bác sĩ {doctorName},\n\n" +
                          $"Bạn có một lịch hẹn khám bệnh mới đã được xác nhận:\n" +
                          $"Bệnh nhân: {patientName}\n" +
                          $"Thời gian: {appointmentDateTime:dd/MM/yyyy HH:mm}\n\n" +
                          "Vui lòng chuẩn bị và có mặt đúng giờ.\n" +
                          "Trân trọng,\nBan Quản lý Bệnh viện ABC";
            }
            else
            {
                _logger.LogWarning("Invalid recipient type '{RecipientType}' for appointment notification to {RecipientEmail}. Notification skipped.", recipientType, recipientEmail);
                return;
            }

            await SendAndLogNotification(recipientEmail, recipientType, subject, content);
        }

        public async Task SendPrescriptionReadyNotificationAsync(
            string patientEmail,
            string patientName,
            string prescriptionDetails)
        {
            var subject = "Thông báo: Đơn thuốc của bạn đã sẵn sàng!";
            var content = $"Kính gửi bệnh nhân {patientName},\n\n" +
                          $"Đơn thuốc của bạn đã được chuẩn bị và sẵn sàng để lấy. " +
                          $"Chi tiết đơn thuốc: {prescriptionDetails}\n\n" +
                          $"Vui lòng đến quầy thuốc để nhận đơn thuốc của bạn. Cảm ơn bạn!\n" +
                          "Bệnh viện ABC";

            await SendAndLogNotification(patientEmail, "PATIENT", subject, content);
        }

        private async Task SendAndLogNotification(
            string recipientEmail,
            string recipientType,
            string subject,
            string content)
        {
            // Khởi tạo đối tượng Notification để lưu trữ
            var notification = new Notification
            {
                RecipientEmail = recipientEmail,
                RecipientType = recipientType,
                Subject = subject,
                Content = content,
                SentAt = DateTime.UtcNow
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
                // Gửi email
                await smtp.SendAsync(email);
                await smtp.DisconnectAsync(true);

                notification.SentSuccessfully = true;
                _logger.LogInformation("Email sent successfully to: {RecipientEmail} for type: {RecipientType}. Subject: {Subject}", recipientEmail, recipientType, subject);
            }
            catch (Exception ex)
            {
                notification.SentSuccessfully = false;
                notification.ErrorMessage = ex.Message;
                _logger.LogError(ex, "Failed to send email to: {RecipientEmail} for type: {RecipientType}. Error: {ErrorMessage}", recipientEmail, recipientType, ex.Message);
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