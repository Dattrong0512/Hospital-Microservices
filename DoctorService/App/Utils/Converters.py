from App.Services import doctor_pb2

def DoctorToProto(doctor):
    return doctor_pb2.DoctorResponse(
        found=True,
        doctor_id=doctor.doctor_id,
        identity_card=doctor.identity_card,
        username=doctor.username,
        fullname=doctor.fullname,
        birth_date=doctor.birth_date.strftime('%d-%m-%Y') if doctor.birth_date else "",
        gender=doctor.gender,
        phone_number=doctor.phone_number,
        email=doctor.email,
        department=doctor.department
    )
