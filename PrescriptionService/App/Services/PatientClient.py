import grpc, os
from App.Services import patient_pb2, patient_pb2_grpc
from dotenv import load_dotenv

load_dotenv()
PATIENT_HOST = os.getenv('PATIENT_gRPC_HOST', 'localhost')
PATIENT_PORT = os.getenv('PATIENT_gRPC_PORT', '50051')

def GetPatientById(patient_id):
    try:
        with grpc.insecure_channel(f"{PATIENT_HOST}:{PATIENT_PORT}") as channel:
            stub = patient_pb2_grpc.PatientServiceStub(channel)
            request = patient_pb2.PatientIdRequest(patient_id=patient_id)
            response = stub.GetPatientById(request)
            return response
    except grpc.RpcError as e:
        print(f"gRPC Error: {e}")
        return None

def GetPatientByIdentity(identity_card):
    try:
        with grpc.insecure_channel(f"{PATIENT_HOST}:{PATIENT_PORT}") as channel:
            stub = patient_pb2_grpc.PatientServiceStub(channel)
            request = patient_pb2.PatientIdentityRequest(identity_card=identity_card)
            response = stub.GetPatientByIdentity(request)
            return response
    except grpc.RpcError as e:
        print(f"gRPC Error: {e}")
        return None