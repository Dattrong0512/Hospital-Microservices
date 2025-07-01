from concurrent import futures
import grpc
from App.Models.Doctor import Doctor
from App.Controllers import DoctorController
from App.Services import doctor_pb2_grpc, doctor_pb2
from App.Utils.Converters import DoctorToProto

class DoctorServiceServicer(doctor_pb2_grpc.DoctorServiceServicer):
    def __init__(self, app):
        self.app = app

    def GetDoctorById(self, request, context):
        with self.app.app_context():
            doctor = DoctorController.GetDoctorById(request.doctor_id)
            if not doctor:
                return doctor_pb2.DoctorResponse(found=False)
            return DoctorToProto(doctor)
        
    def GetAvailableDoctors(self, request, context):
        with self.app.app_context():
            doctors = DoctorController.GetAvailableDoctors(request.excluded_id, request.department)
            if not doctors:
                return doctor_pb2.AvailableDoctorsResponse(doctors=[])
            proto_list = [DoctorToProto(doc) for doc in doctors]
            return doctor_pb2.AvailableDoctorsResponse(doctors=proto_list)

def serve(app):
    server = grpc.server(futures.ThreadPoolExecutor(max_workers=10))
    doctor_pb2_grpc.add_DoctorServiceServicer_to_server(DoctorServiceServicer(app), server)
    server.add_insecure_port('[::]:50053')
    server.start()
    print("🟢 Doctor gRPC server running on port 50053")
    server.wait_for_termination()
