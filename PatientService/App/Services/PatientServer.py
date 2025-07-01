from concurrent import futures
import grpc
from App.Controllers import PatientController
from App.Services import patient_pb2_grpc, patient_pb2
from App.Utils.Converters import PatientToProto

class PatientServiceServicer(patient_pb2_grpc.PatientServiceServicer):
    def __init__(self, app):
        self.app = app

    def GetPatientById(self, request, context):
        with self.app.app_context():
            patient = PatientController.GetPatientById(request.patient_id)
            if not patient:
                return patient_pb2.PatientResponse(found=False)
            return PatientToProto(patient)
        
    def GetPatientByIdentity(self, request, context):
        with self.app.app_context():
            patient = PatientController.GetPatientByIdentity(request.identity_card)
            if not patient:
                return patient_pb2.PatientResponse(found=False)
            return PatientToProto(patient)

def serve(app):
    server = grpc.server(futures.ThreadPoolExecutor(max_workers=10))
    patient_pb2_grpc.add_PatientServiceServicer_to_server(PatientServiceServicer(app), server)
    server.add_insecure_port('[::]:50051')
    server.start()
    print("🟢 Patient gRPC server running on port 50051")
    server.wait_for_termination()
