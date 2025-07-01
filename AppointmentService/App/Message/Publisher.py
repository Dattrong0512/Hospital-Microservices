import pika, json, os
from dotenv import load_dotenv

load_dotenv()
RABBITMQ_HOST = os.getenv('RABBITMQ_HOST', 'localhost')
RABBITMQ_PORT = int(os.getenv('RABBITMQ_PORT', '5672'))
RBBITMQ_URL = os.getenv('RABBITMQ_URL')

class RabbitMQPublisher:
    # def __init__(self, host=RABBITMQ_HOST, exchange='notification_exchange', routing_key='appointment'):
    def __init__(self, url=RBBITMQ_URL, exchange='notification_exchange', routing_key='appointment'):
        self.url = url
        # self.host = host
        self.exchange = exchange
        self.routing_key = routing_key

    def send_message(self, message_dict):
        try:
            # connection = pika.BlockingConnection(pika.ConnectionParameters(host=self.host, port=RABBITMQ_PORT))
            # channel = connection.channel()

            parameters = pika.URLParameters(self.url)
            connection = pika.BlockingConnection(parameters)
            channel = connection.channel()

            channel.exchange_declare(exchange=self.exchange, exchange_type='direct', durable=True)
            # Khai báo Queue (nếu chưa có)
            channel.queue_declare(queue='appointment_queue', durable=True)
            # Bind Queue với Exchange (nếu chưa có)
            channel.queue_bind(exchange=self.exchange, queue='appointment_queue', routing_key=self.routing_key)
            
            message_json = json.dumps(message_dict)
            channel.basic_publish(
                exchange=self.exchange,
                routing_key=self.routing_key,
                body=message_json,
                properties=pika.BasicProperties(
                    delivery_mode=2  # Persistent
                )
            )
            print(f"[x] Sent message: {message_json}")
            connection.close()
        except Exception as e:
            print(f"[!] Error sending message to RabbitMQ: {e}")
