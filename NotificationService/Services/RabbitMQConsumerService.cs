using Microsoft.Extensions.Hosting;
using Microsoft.Extensions.Logging;
using Microsoft.Extensions.Configuration;
using RabbitMQ.Client;
using RabbitMQ.Client.Events;
using System;
using System.Text;
using System.Text.Json;
using System.Threading;
using System.Threading.Tasks;
using NotificationService.Models;
using NotificationService.Services;
namespace NotificationService.Services
{
    public class RabbitMQConsumerService : BackgroundService
    {
        private readonly ILogger<RabbitMQConsumerService> _logger;
        private readonly IConfiguration _configuration;
        private readonly INotificationService _notificationService; // Khai báo NotificationService
        private IConnection _connection;
        private IModel _channel;
        private readonly string _queueName;
        private readonly string _exchangeName = "notification_exchange";
        private readonly string _routingKey = "appointment"; // Có thể cần điều chỉnh nếu có nhiều routing key

        public RabbitMQConsumerService(
            ILogger<RabbitMQConsumerService> logger,
            IConfiguration configuration,
            INotificationService notificationService // Inject NotificationService vào constructor
            )
        {
            _logger = logger;
            _configuration = configuration;
            _notificationService = notificationService; // Khởi tạo
            _queueName = _configuration["RabbitMQ:QueueName"] ?? "appointment_queue";
        }

        protected override async Task ExecuteAsync(CancellationToken stoppingToken)
        {
            _logger.LogInformation("RabbitMQ Consumer Service is starting.");

            stoppingToken.Register(() => _logger.LogInformation("RabbitMQ Consumer Service is stopping gracefully."));

            var amqpConnectionString = _configuration["RabbitMQ:AmqpConnectionString"];
            if (string.IsNullOrEmpty(amqpConnectionString))
            {
                _logger.LogError("RabbitMQ AmqpConnectionString is missing in configuration. Consumer cannot start.");
                return;
            }

            try
            {
                var factory = new ConnectionFactory
                {
                    Uri = new Uri(amqpConnectionString),
                    AutomaticRecoveryEnabled = true,
                    Ssl = new SslOption
                    {
                        Enabled = amqpConnectionString.StartsWith("amqps://", StringComparison.OrdinalIgnoreCase),
                        ServerName = new Uri(amqpConnectionString).Host,
                        AcceptablePolicyErrors = System.Net.Security.SslPolicyErrors.RemoteCertificateNameMismatch | System.Net.Security.SslPolicyErrors.RemoteCertificateChainErrors
                    }
                };

                _connection = factory.CreateConnection();
                _logger.LogInformation("RabbitMQ consumer connected successfully.");

                _channel = _connection.CreateModel();

                _channel.ExchangeDeclare(exchange: _exchangeName, type: ExchangeType.Direct, durable: true);
                _channel.QueueDeclare(queue: _queueName, durable: true, exclusive: false, autoDelete: false, arguments: null);
                _channel.QueueBind(queue: _queueName, exchange: _exchangeName, routingKey: _routingKey);

                _logger.LogInformation($"DEBUG: CONSUME - Consumer started listening on queue: {_queueName}");

                var consumer = new AsyncEventingBasicConsumer(_channel);
                consumer.Received += async (model, ea) =>
                {
                    try
                    {
                        var body = ea.Body.ToArray();
                        var message = Encoding.UTF8.GetString(body);
                        _logger.LogInformation($" [x] Received message: {message}");

                        var jsonOptions = new JsonSerializerOptions { PropertyNameCaseInsensitive = true };

                        var baseMessage = JsonSerializer.Deserialize<NotificationMessage>(message, jsonOptions);

                        if (baseMessage == null || string.IsNullOrEmpty(baseMessage.Type))
                        {
                            _logger.LogWarning($"Received message without a valid 'Type' property: {message}. Nacking without requeue.");
                            _channel.BasicNack(ea.DeliveryTag, multiple: false, requeue: false);
                            return;
                        }

                        switch (baseMessage.Type.ToLowerInvariant())
                        {
                            case "appointment":
                                var appointmentMessage = JsonSerializer.Deserialize<AppointmentNotificationMessage>(message, jsonOptions);
                                if (appointmentMessage != null)
                                {
                                    _logger.LogInformation($"Processing Appointment Notification: Patient={appointmentMessage.Patient_fullname}, Doctor={appointmentMessage.Doctor_fullname}, Date={appointmentMessage.Date}");

                                    // Parse Date string to DateTime
                                    if (!DateTime.TryParse(appointmentMessage.Date, out var appointmentDateTime))
                                    {
                                        _logger.LogWarning($"Invalid appointment date format: {appointmentMessage.Date}. Nacking without requeue.");
                                        _channel.BasicNack(ea.DeliveryTag, multiple: false, requeue: false);
                                        return;
                                    }

                                    // Gửi thông báo cho bệnh nhân
                                    if (!string.IsNullOrEmpty(appointmentMessage.Patient_email))
                                    {
                                        _logger.LogInformation("Sending patient notification for Appointment Type: Appointment, Date: {AppointmentDateTime}", appointmentDateTime);
                                        await _notificationService.SendAppointmentNotificationAsync(
                                            appointmentMessage.Patient_email,
                                            "PATIENT",
                                            appointmentMessage.Doctor_fullname,
                                            appointmentMessage.Patient_fullname,
                                            appointmentDateTime);
                                    }
                                    else
                                    {
                                        _logger.LogInformation("Patient email is empty for Appointment Type: Appointment. Skipping patient notification.");
                                    }

                                    // Gửi thông báo cho bác sĩ
                                    if (!string.IsNullOrEmpty(appointmentMessage.Doctor_email))
                                    {
                                        _logger.LogInformation("Sending doctor notification for Appointment Type: Appointment, Date: {AppointmentDateTime}", appointmentDateTime);
                                        await _notificationService.SendAppointmentNotificationAsync(
                                            appointmentMessage.Doctor_email,
                                            "DOCTOR",
                                            appointmentMessage.Doctor_fullname,
                                            appointmentMessage.Patient_fullname,
                                            appointmentDateTime);
                                    }
                                    else
                                    {
                                        _logger.LogInformation("Doctor email is empty for Appointment Type: Appointment. Skipping doctor notification.");
                                    }
                                    Console.WriteLine($"Sending appointment email to {appointmentMessage.Patient_email}");
                                }
                                else
                                {
                                    _logger.LogWarning($"Failed to deserialize Appointment message: {message}. Nacking without requeue.");
                                    _channel.BasicNack(ea.DeliveryTag, multiple: false, requeue: false);
                                }
                                break;

                            case "prescription":
                                var prescriptionMessage = JsonSerializer.Deserialize<PrescriptionNotificationMessage>(message, jsonOptions);
                                if (prescriptionMessage != null)
                                {
                                    _logger.LogInformation($"Processing Prescription Notification: Patient={prescriptionMessage.PatientFullname}, Email={prescriptionMessage.PatientEmail}, Detail={prescriptionMessage.PrescriptionDetail}");
                                    
                                    // Gửi thông báo đơn thuốc
                                    if (!string.IsNullOrEmpty(prescriptionMessage.PatientEmail))
                                    {
                                        _logger.LogInformation("Sending patient notification for Prescription Type: Prescription.");
                                        await _notificationService.SendPrescriptionReadyNotificationAsync(
                                            prescriptionMessage.PatientEmail,
                                            prescriptionMessage.PatientFullname,
                                            prescriptionMessage.PrescriptionDetail
                                        );
                                    }
                                    else
                                    {
                                        _logger.LogInformation("Patient email is empty for Prescription Type: Prescription. Skipping patient notification.");
                                    }

                                    Console.WriteLine($"Sending prescription email to {prescriptionMessage.PatientEmail} for {prescriptionMessage.PrescriptionDetail}");
                                }
                                else
                                {
                                    _logger.LogWarning($"Failed to deserialize Prescription message: {message}. Nacking without requeue.");
                                    _channel.BasicNack(ea.DeliveryTag, multiple: false, requeue: false);
                                }
                                break;

                            default:
                                _logger.LogWarning($"Unknown message type received: '{baseMessage.Type}'. Message: {message}. Nacking without requeue.");
                                _channel.BasicNack(ea.DeliveryTag, multiple: false, requeue: false);
                                break;
                        }

                        _channel.BasicAck(ea.DeliveryTag, multiple: false);
                        _logger.LogInformation($"Message acknowledged successfully for delivery tag: {ea.DeliveryTag}");
                    }
                    catch (JsonException jsonEx)
                    {
                        _logger.LogError(jsonEx, $"JSON Deserialization error for message: {Encoding.UTF8.GetString(ea.Body.ToArray())}");
                        _channel.BasicNack(ea.DeliveryTag, multiple: false, requeue: false);
                    }
                    catch (Exception ex)
                    {
                        _logger.LogError(ex, $"Error processing message for delivery tag {ea.DeliveryTag}. Requeuing: {Encoding.UTF8.GetString(ea.Body.ToArray())}");
                        _channel.BasicNack(ea.DeliveryTag, multiple: false, requeue: true);
                    }
                };

                _channel.BasicConsume(queue: _queueName, autoAck: false, consumer: consumer);

                _logger.LogInformation("RabbitMQ Consumer Service started listening successfully.");

                await Task.Delay(Timeout.Infinite, stoppingToken);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to connect or configure RabbitMQ. Consumer will not start.");
            }
        }

        public override async Task StopAsync(CancellationToken stoppingToken)
        {
            _logger.LogInformation("RabbitMQ Consumer Service is performing a graceful shutdown.");
            _channel?.Close();
            _connection?.Close();
            _logger.LogInformation("RabbitMQ connection closed.");
            await base.StopAsync(stoppingToken);
        }
    }
}