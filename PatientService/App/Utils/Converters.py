from App.Services import patient_pb2

def PatientToProto(patient):
    return patient_pb2.PatientResponse(
        found=True,
        id=patient.id,
        identity_card=patient.identity_card,
        fullname=patient.fullname,
        birth_date=patient.birth_date.strftime('%d-%m-%Y') if patient.birth_date else "",
        gender=patient.gender,
        address=patient.address,
        phone_number=patient.phone_number,
        email=patient.email,
        medical_history=patient.medical_history
    )
