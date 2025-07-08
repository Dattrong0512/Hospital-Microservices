using System;
using System.Text;
using System.Text.Json;
using RabbitMQ.Client;
using RabbitMQ.Client.Events;
using MailKit.Net.Smtp;
using MimeKit;
using System.Threading;

class Program
{
    static void Main(string[] args)
    {
        var amqpUrl = "amqps://errymwix:JCnl3C8qXN32qdFwK7B9hM-Dfn9SLrNb@armadillo.rmq.cloudamqp.com/errymwix";
        var exchangeName = "notification_exchange";
        var appointmentQueue = "appointment_queue";
        var prescriptionQueue = "prescription_queue";

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
                    if (!string.IsNullOrEmpty(appointment.Patient_email))
                    {
                        var subject = "Nhắc nhở lịch khám bệnh";
                        var content = $"Kính gửi {appointment.Patient_fullname},\n\nBạn có lịch hẹn khám với bác sĩ {appointment.Doctor_fullname} vào ngày {appointment.Date} lúc {appointment.Started_time} tại bệnh viện.\n\nVui lòng đến đúng giờ, mang theo giấy tờ cần thiết và tuân thủ các hướng dẫn của bệnh viện.\n\nTrân trọng,\nBệnh viện ABC";
                        SendEmail(appointment.Patient_email, subject, content);
                    }
                    else
                    {
                        Console.WriteLine(" [!] Appointment message thiếu Patient_email.");
                    }
                    // Gửi email cho bác sĩ
                    if (!string.IsNullOrEmpty(appointment.Doctor_email))
                    {
                        var subject = "Nhắc nhở lịch khám bệnh";
                        var content = $"Kính gửi bác sĩ {appointment.Doctor_fullname},\n\nBạn có lịch hẹn khám với bệnh nhân {appointment.Patient_fullname} vào ngày {appointment.Date} lúc {appointment.Started_time} tại bệnh viện.\n\nVui lòng chuẩn bị hồ sơ và thiết bị cần thiết cho ca khám này.\n\nTrân trọng,\nBệnh viện ABC";
                        SendEmail(appointment.Doctor_email, subject, content);
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

            Console.WriteLine($" [>] Đã gửi email tới {toEmail}");
        }
        catch (Exception ex)
        {
            Console.WriteLine($" [!] Lỗi gửi email tới {toEmail}: {ex.Message}");
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
    public string Patient_fullname { get; set; }
    public string Patient_email { get; set; }
    public string Doctor_fullname { get; set; }
    public string Doctor_email { get; set; }
    public string Date { get; set; }
    public string Started_time { get; set; }
}

public class PrescriptionNotificationMessage : NotificationMessageBase
{
    public string patient_name { get; set; }
    public string email { get; set; }
    public int no_days { get; set; }
}
