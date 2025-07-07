namespace NotificationService.Models
{
    public class RabbitMQSettings
    {
        public string? AmqpConnectionString { get; set; }

        public string? HostName { get; set; }
        public int? Port { get; set; }
        public string? UserName { get; set; }
        public string? Password { get; set; }
    }
}