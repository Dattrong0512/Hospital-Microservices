import grpc, os
from App.Services import appointment_pb2_grpc, appointment_pb2
from dotenv import load_dotenv

load_dotenv()
APPOINTMENT_HOST = os.getenv('APPOINTMENT_gRPC_HOST', 'localhost')
APPOINTMNET_PORT = os.getenv('APPOINTMENT_gRPC_PORT', '50054')

def GetAppointmentById(appointment_id):
    try:
        with grpc.insecure_channel(f"{APPOINTMENT_HOST}:{APPOINTMNET_PORT}") as channel:
            stub = appointment_pb2_grpc.AppointmentServiceStub(channel)
            print(f"appointment_id = {appointment_id}, type = {type(appointment_id)}")
            request = appointment_pb2.AppointmentIdRequest(appointment_id=appointment_id)
            response = stub.GetAppointmentById(request)
            print(f"gRPC response: {response}")
            return response
    except grpc.RpcError as e:
        print(f"gRPC Error: {e}")
        return None