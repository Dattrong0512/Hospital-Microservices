from concurrent import futures
import grpc
from App.Controllers import MedicineController
from App.Services import medicine_pb2_grpc, medicine_pb2
from App.Utils.Converters import MedicineToProto

class MedicineServiceServicer(medicine_pb2_grpc.MedicineServiceServicer):
    def __init__(self, app):
        self.app = app

    def GetMedicineById(self, request, context):
        with self.app.app_context():
            medicine = MedicineController.GetMedicineById(request.medicine_id)
            if not medicine:
                return medicine_pb2.MedicineResponse(found=False)
            return MedicineToProto(medicine)
        
    def UpdateMedicine(self, request, context):
        with self.app.app_context():
            medicine = MedicineController.UpdateAmount(request.medicine_id, request.amount)
            if not medicine:
                return medicine_pb2.MedicineResponse(found=False)
            return MedicineToProto(medicine)

def serve(app):
    server = grpc.server(futures.ThreadPoolExecutor(max_workers=10))
    medicine_pb2_grpc.add_MedicineServiceServicer_to_server(MedicineServiceServicer(app), server)
    server.add_insecure_port('[::]:50052')
    server.start()
    print("🟢 Medicine gRPC server running on port 50052")
    server.wait_for_termination()
