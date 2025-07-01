from App.Services import appointment_pb2

def AppointmentToProto(appointment):
    return appointment_pb2.AppointmentResponse(
        found=True,
        appointment_id=appointment.appointment_id,
        doctor_id=appointment.doctor_id,
        patient_id=appointment.patient_id,
        date=appointment.date.strftime('%d-%m-%Y') if appointment.date else "",
        status=appointment.status,
        description=appointment.description,
        started_time=appointment.started_time
    )
