// NotificationService/Models/Notification.cs
using MongoDB.Bson;
using MongoDB.Bson.Serialization.Attributes;

namespace NotificationService.Models
{
    public class Notification
    {
        [BsonId]
        [BsonRepresentation(BsonType.ObjectId)]
        public string Id { get; set; } = null!;
        [BsonElement("recipientEmail")]
        public string RecipientEmail { get; set; } = null!;

        [BsonElement("recipientType")]
        public string RecipientType { get; set; } = null!;

        [BsonElement("subject")]
        public string Subject { get; set; } = null!;

        [BsonElement("content")]
        public string Content { get; set; } = null!;

        [BsonElement("sentAt")]
        public DateTime SentAt { get; set; }

        [BsonElement("sentSuccessfully")]
        public bool SentSuccessfully { get; set; }

        [BsonElement("errorMessage")]
        public string? ErrorMessage { get; set; }
    }
}