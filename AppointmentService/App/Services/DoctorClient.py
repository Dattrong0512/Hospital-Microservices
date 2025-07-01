import grpc, os
from App.Services import doctor_pb2, doctor_pb2_grpc
from dotenv import load_dotenv

load_dotenv()
DOCTOR_HOST = os.getenv('DOCTOR_gRPC_HOST', 'localhost')
DOCTOR_PORT = os.getenv('DOCTOR_gRPC_PORT', '50053')

def GetDoctorById(doctor_id):
    try:
        with grpc.insecure_channel(f"{DOCTOR_HOST}:{DOCTOR_PORT}") as channel:
            stub = doctor_pb2_grpc.DoctorServiceStub(channel)
            request = doctor_pb2.DoctorIdRequest(doctor_id=doctor_id)
            response = stub.GetDoctorById(request)
            return response
    except grpc.RpcError as e:
        print(f"gRPC Error: {e}")
        return None

def GetAvailableDoctors(excluded_id=None, department=None):
    try:
        with grpc.insecure_channel(f"{DOCTOR_HOST}:{DOCTOR_PORT}") as channel:
            stub = doctor_pb2_grpc.DoctorServiceStub(channel)
            request = doctor_pb2.AvailableDoctorsRequest(
                excluded_id=excluded_id,
                department=department
            )
            response = stub.GetAvailableDoctors(request)
            return response
    except grpc.RpcError as e:
        print(f"gRPC Error: {e}")
        return None