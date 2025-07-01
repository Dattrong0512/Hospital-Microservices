from concurrent import futures
import grpc
from App.Controllers import AppointmentController
from App.Services import appointment_pb2_grpc, appointment_pb2
from App.Utils.Converters import AppointmentToProto

class AppointmentServiceServicer(appointment_pb2_grpc.AppointmentServiceServicer):
    def __init__(self, app):
        self.app = app

    def GetAppointmentById(self, request, context):
        with self.app.app_context():
            appointment = AppointmentController.GetAppointmentById(request.appointment_id)
            if not appointment:
                return appointment_pb2.AppointmentResponse(found=False)
            return AppointmentToProto(appointment)

def serve(app):
    server = grpc.server(futures.ThreadPoolExecutor(max_workers=10))
    appointment_pb2_grpc.add_AppointmentServiceServicer_to_server(AppointmentServiceServicer(app), server)
    server.add_insecure_port('[::]:50054')
    server.start()
    print("🟢 Appointment gRPC server running on port 50054")
    server.wait_for_termination()
