using Microsoft.Extensions.Hosting;
using Microsoft.Extensions.Logging;
using RabbitMQ.Client;
using RabbitMQ.Client.Events;
using System.Text;
using System.Threading;
using System.Threading.Tasks;
using NotificationService.Messages;
using System.Text.Json;
using System;

namespace NotificationService.Services
{
    public class RabbitMQConsumerService : BackgroundService
    {
        private readonly ILogger<RabbitMQConsumerService> _logger;
        private readonly INotificationService _notificationService;
        private readonly IConnectionFactory _connectionFactory;
        private IConnection _connection;
        private IModel _channel;
        private const string QUEUE_NAME = "appointment_queue";
        //private const string QUEUE_NAME1 = "prescription_queue";
        private const string EXCHANGE_NAME = "notification_exchange";
        //private const string ROUTING_KEY1 = "prescription";
        private const string ROUTING_KEY = "appointment";

        public RabbitMQConsumerService(
            ILogger<RabbitMQConsumerService> logger,
            INotificationService notificationService,
            IConnectionFactory connectionFactory)
        {
            _logger = logger;
            _notificationService = notificationService;
            _connectionFactory = connectionFactory;

            InitializeRabbitMQ();
        }

        private void InitializeRabbitMQ()
        {
            try
            {
                _connection = _connectionFactory.CreateConnection();
                _channel = _connection.CreateModel();

                _channel.ExchangeDeclare(exchange: EXCHANGE_NAME, type: ExchangeType.Direct, durable: true);
                _channel.QueueDeclare(queue: QUEUE_NAME,
                                     durable: true,
                                     exclusive: false,
                                     autoDelete: false,
                                     arguments: null);
                _channel.QueueBind(queue: QUEUE_NAME,
                                  exchange: EXCHANGE_NAME,
                                  routingKey: ROUTING_KEY);

                _logger.LogInformation("RabbitMQConsumerService connected and configured successfully.");
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to connect to RabbitMQ or configure channel. Check RabbitMQ connection string in appsettings.");
            }
        }

        protected override Task ExecuteAsync(CancellationToken stoppingToken)
        {

            stoppingToken.ThrowIfCancellationRequested();

            if (_channel == null)
            {
                _logger.LogError("RabbitMQ channel is not initialized. Consumer cannot start.");
                return Task.CompletedTask;
            }

            var consumer = new AsyncEventingBasicConsumer(_channel);
            consumer.Received += async (model, ea) =>
            {
                _logger.LogInformation("DEBUG: RECEIVED - Entered consumer.Received event handler."); 
                var body = ea.Body.ToArray();
                var messageContent = Encoding.UTF8.GetString(body);
                _logger.LogInformation($" [x] Received message: {messageContent}");

                try
                {
                    var reminder = JsonSerializer.Deserialize<AppointmentReminderMessage>(messageContent, new JsonSerializerOptions { PropertyNameCaseInsensitive = true });
                    if (reminder == null)
                    {
                        _logger.LogWarning("Received null or invalid AppointmentReminderMessage. Not acknowledging and discarding.");
                        _channel.BasicNack(ea.DeliveryTag, false, false);
                        return;
                    }

                    // Gửi thông báo cho bệnh nhân
                    if (!string.IsNullOrEmpty(reminder.PatientEmail))
                    {
                        _logger.LogInformation("Sending patient notification for Appointment Type: {MessageType}, Date: {AppointmentDateTime}", reminder.MessageType, reminder.AppointmentDateTime); // Đã sửa placeholder
                        await _notificationService.SendAppointmentNotificationAsync(
                            reminder.PatientEmail,
                            "PATIENT",
                            reminder.DoctorName,
                            reminder.PatientName,
                            reminder.AppointmentDateTime
                        );
                    }
                    else
                    {
                        _logger.LogInformation("Patient email is empty for Appointment Type: {MessageType}. Skipping patient notification.", reminder.MessageType); // Đã sửa placeholder
                    }

                    // Gửi thông báo cho bác sĩ
                    if (!string.IsNullOrEmpty(reminder.DoctorEmail))
                    {
                        _logger.LogInformation("Sending doctor notification for Appointment Type: {MessageType}, Date: {AppointmentDateTime}", reminder.MessageType, reminder.AppointmentDateTime); // Đã sửa placeholder
                        await _notificationService.SendAppointmentNotificationAsync(
                            reminder.DoctorEmail,
                            "DOCTOR",
                            reminder.DoctorName,
                            reminder.PatientName,
                            reminder.AppointmentDateTime
                        );
                    }
                    else
                    {
                        _logger.LogInformation("Doctor email is empty for Appointment Type: {MessageType}. Skipping doctor notification.", reminder.MessageType); // Đã sửa placeholder
                    }

                    _channel.BasicAck(ea.DeliveryTag, false);
                    _logger.LogInformation("Message processed and acknowledged for Appointment Type: {MessageType}", reminder.MessageType); // Đã sửa placeholder
                }
                catch (JsonException jsonEx)
                {
                    _logger.LogError(jsonEx, "Failed to deserialize message: {MessageContent}. This message will be discarded.", messageContent);
                    _channel.BasicNack(ea.DeliveryTag, false, false);
                }
                catch (Exception ex)
                {
                    _logger.LogError(ex, "Error processing RabbitMQ message: {MessageContent}", messageContent);
                    _channel.BasicNack(ea.DeliveryTag, false, true);
                }
            };

            _channel.BasicConsume(queue: QUEUE_NAME, autoAck: false, consumer: consumer);
            _logger.LogInformation($"DEBUG: CONSUME - Consumer started listening on queue: {QUEUE_NAME}");


            return Task.CompletedTask;
        }

        public override void Dispose()
        {
            _logger.LogInformation("RabbitMQConsumerService is stopping. Disposing resources.");
            _channel?.Close();
            _connection?.Close();
            _channel?.Dispose();
            _connection?.Dispose();
            base.Dispose();
        }
    }
}