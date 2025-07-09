using System;
using System.Text;
using System.Text.Json;
using RabbitMQ.Client;
using RabbitMQ.Client.Events;
using MailKit.Net.Smtp;
using MimeKit;
using System.Threading;
using MongoDB.Bson;
using MongoDB.Bson.Serialization.Attributes;
using MongoDB.Driver;

class Program
{
    static MongoClient mongoClient;
    static IMongoCollection<EmailLog> emailLogCollection;

    static void Main(string[] args)
    {
        var amqpUrl = "amqps://errymwix:JCnl3C8qXN32qdFwK7B9hM-Dfn9SLrNb@armadillo.rmq.cloudamqp.com/errymwix";
        var exchangeName = "notification_exchange";
        var appointmentQueue = "appointment_queue";
        var prescriptionQueue = "prescription_queue";

        // MongoDB config
        var mongoUsername = "doadmin";
        var mongoPassword = "NfO605M3o87yw1V2";
        var mongoHost = "notification-mongo-256fe619.mongo.ondigitalocean.com";
        var mongoDatabase = "admin";
        var mongoCollection = "email_logs";
        var mongoConnectionString = $"mongodb+srv://{mongoUsername}:{mongoPassword}@{mongoHost}/{mongoDatabase}?retryWrites=true&w=majority";

        mongoClient = new MongoClient(mongoConnectionString);
        var db = mongoClient.GetDatabase(mongoDatabase);
        emailLogCollection = db.GetCollection<EmailLog>(mongoCollection);

        var factory = new ConnectionFactory() { Uri = new Uri(amqpUrl) };
        using var connection = factory.CreateConnection();
        using var channel = connection.CreateModel();

        // Khai báo exchange và 2 queue, bind từng queue với routing key tương ứng
        channel.ExchangeDeclare(exchange: exchangeName, type: ExchangeType.Direct, durable: true);
        channel.QueueDeclare(queue: appointmentQueue, durable: true, exclusive: false, autoDelete: false, arguments: null);
        channel.QueueDeclare(queue: prescriptionQueue, durable: true, exclusive: false, autoDelete: false, arguments: null);
        channel.QueueBind(queue: appointmentQueue, exchange: exchangeName, routingKey: "appointment");
        channel.QueueBind(queue: prescriptionQueue, exchange: exchangeName, routingKey: "prescription");

        // Consumer cho appointment_queue
        var appointmentConsumer = new EventingBasicConsumer(channel);
        appointmentConsumer.Received += (model, ea) =>
        {
            var body = ea.Body.ToArray();
            var message = Encoding.UTF8.GetString(body);
            Console.WriteLine($" [x] Received from appointment_queue: {message}");
            try
            {
                var appointment = JsonSerializer.Deserialize<AppointmentNotificationMessage>(message, new JsonSerializerOptions { PropertyNameCaseInsensitive = true });
                if (appointment != null)
                {
                    // Gửi email cho bệnh nhân
                    if (!string.IsNullOrEmpty(appointment.patient_email))
                    {
                        var subject = "Nhắc nhở lịch khám bệnh";
                        var content = $"Kính gửi {appointment.patient_fullname},\n\nBạn có lịch hẹn khám với bác sĩ {appointment.doctor_fullname} vào ngày {appointment.date} lúc {appointment.started_time} tại bệnh viện.\n\nVui lòng đến đúng giờ, mang theo giấy tờ cần thiết và tuân thủ các hướng dẫn của bệnh viện.\n\nTrân trọng,\nBệnh viện ABC";
                        SendEmail(appointment.patient_email, subject, content);
                    }
                    else
                    {
                        Console.WriteLine(" [!] Appointment message thiếu Patient_email.");
                    }
                    // Gửi email cho bác sĩ
                    if (!string.IsNullOrEmpty(appointment.doctor_email))
                    {
                        var subject = "Nhắc nhở lịch khám bệnh";
                        var content = $"Kính gửi bác sĩ {appointment.doctor_fullname},\n\nBạn có lịch hẹn khám với bệnh nhân {appointment.patient_fullname} vào ngày {appointment.date} lúc {appointment.started_time} tại bệnh viện.\n\nVui lòng chuẩn bị hồ sơ và thiết bị cần thiết cho ca khám này.\n\nTrân trọng,\nBệnh viện ABC";
                        SendEmail(appointment.doctor_email, subject, content);
                    }
                    else
                    {
                        Console.WriteLine(" [!] Appointment message thiếu Doctor_email.");
                    }
                }
                else
                {
                    Console.WriteLine(" [!] Appointment message không hợp lệ.");
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine($" [!] Error parsing or sending email (appointment): {ex.Message}");
            }
        };
        channel.BasicConsume(queue: appointmentQueue, autoAck: true, consumer: appointmentConsumer);

        // Consumer cho prescription_queue
        var prescriptionConsumer = new EventingBasicConsumer(channel);
        prescriptionConsumer.Received += (model, ea) =>
        {
            var body = ea.Body.ToArray();
            var message = Encoding.UTF8.GetString(body);
            Console.WriteLine($" [x] Received from prescription_queue: {message}");
            try
            {
                var prescription = JsonSerializer.Deserialize<PrescriptionNotificationMessage>(message, new JsonSerializerOptions { PropertyNameCaseInsensitive = true });
                if (prescription != null && !string.IsNullOrEmpty(prescription.email))
                {
                    var subject = "Thông báo: Đơn thuốc của bạn đã sẵn sàng!";
                    var content = $"Kính gửi {prescription.patient_name},\n\nĐơn thuốc của bạn đã được chuẩn bị và sẵn sàng để lấy tại quầy thuốc của bệnh viện.\n\nSố ngày sử dụng thuốc: {prescription.no_days} ngày.\n\nVui lòng đến quầy thuốc để nhận đơn thuốc của bạn. Cảm ơn bạn!\n\nTrân trọng,\nBệnh viện ABC";
                    SendEmail(prescription.email, subject, content);
                }
                else
                {
                    Console.WriteLine(" [!] Prescription message thiếu email.");
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine($" [!] Error parsing or sending email (prescription): {ex.Message}");
            }
        };
        channel.BasicConsume(queue: prescriptionQueue, autoAck: true, consumer: prescriptionConsumer);

        Console.WriteLine($" [*] Waiting for messages in queues '{appointmentQueue}' and '{prescriptionQueue}' (exchange '{exchangeName}'). Service will keep running. Press Ctrl+C to exit.");
        while (true) { Thread.Sleep(1000); }
    }

    static void SendEmail(string toEmail, string subject, string content)
    {
        var log = new EmailLog
        {
            Recipient = toEmail,
            Subject = subject,
            Content = content,
            SentAt = DateTime.UtcNow
        };
        try
        {
            var email = new MimeMessage();
            email.From.Add(new MailboxAddress("ABC Hospital App", "kantruong11@gmail.com"));
            email.To.Add(MailboxAddress.Parse(toEmail));
            email.Subject = subject;
            email.Body = new TextPart("plain") { Text = content };

            using var smtp = new SmtpClient();
            smtp.Connect("smtp.gmail.com", 587, MailKit.Security.SecureSocketOptions.StartTls);
            smtp.Authenticate("kantruong11@gmail.com", "jvwq swtn icua jlde");
            smtp.Send(email);
            smtp.Disconnect(true);

            log.SentSuccessfully = true;
            log.ErrorMessage = null;
            Console.WriteLine($" [>] Đã gửi email tới {toEmail}");
        }
        catch (Exception ex)
        {
            log.SentSuccessfully = false;
            log.ErrorMessage = ex.Message;
            Console.WriteLine($" [!] Lỗi gửi email tới {toEmail}: {ex.Message}");
        }
        finally
        {
            try
            {
                emailLogCollection.InsertOne(log);
            }
            catch (Exception ex)
            {
                Console.WriteLine($" [!] Lỗi lưu log email vào MongoDB: {ex.Message}");
            }
        }
    }
}

// Các class mapping với message JSON
public class NotificationMessageBase
{
    public string Type { get; set; }
}

public class AppointmentNotificationMessage : NotificationMessageBase
{
    public string patient_fullname { get; set; }
    public string patient_email { get; set; }
    public string doctor_fullname { get; set; }
    public string doctor_email { get; set; }
    public string date { get; set; }
    public string started_time { get; set; }
}

public class PrescriptionNotificationMessage : NotificationMessageBase
{
    public string patient_name { get; set; }
    public string email { get; set; }
    public int no_days { get; set; }
}

// Thêm class lưu log email
public class EmailLog
{
    [BsonId]
    [BsonRepresentation(BsonType.ObjectId)]
    public string Id { get; set; }
    [BsonElement("recipient")]
    public string Recipient { get; set; }
    [BsonElement("subject")]
    public string Subject { get; set; }
    [BsonElement("content")]
    public string Content { get; set; }
    [BsonElement("sentAt")]
    public DateTime SentAt { get; set; }
    [BsonElement("sentSuccessfully")]
    public bool SentSuccessfully { get; set; }
    [BsonElement("errorMessage")]
    public string ErrorMessage { get; set; }
}
