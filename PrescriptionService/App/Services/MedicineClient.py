import grpc, os
from App.Services import medicine_pb2_grpc, medicine_pb2
from dotenv import load_dotenv

load_dotenv()
MEDICINE_HOST = os.getenv('MEDICINE_gRPC_HOST', 'localhost')
MEDICINE_PORT = os.getenv('MEDICINE_gRPC_PORT', '50051')

def GetMedicineById(medicine_id):
    try:
        with grpc.insecure_channel(f"{MEDICINE_HOST}:{MEDICINE_PORT}") as channel:
            stub = medicine_pb2_grpc.MedicineServiceStub(channel)
            request = medicine_pb2.MedicineIdRequest(medicine_id=medicine_id)
            response = stub.GetMedicineById(request)
            return response
    except grpc.RpcError as e:
        print(f"gRPC Error: {e}")
        return None
    
def UpdateMedicine(medicine_id, amount):
    try:
        with grpc.insecure_channel(f"{MEDICINE_HOST}:{MEDICINE_PORT}") as channel:
            stub = medicine_pb2_grpc.MedicineServiceStub(channel)
            request = medicine_pb2.UpdateMedicineRequest(medicine_id=medicine_id, amount=amount)

            response = stub.UpdateMedicine(request)
            return response
    except grpc.RpcError as e:
        print(f"gRPC Error: {e}")
        return None