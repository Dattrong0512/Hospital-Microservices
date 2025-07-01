from App._init_ import db
from App.Models.Patient import Patient

def CreatePatient(data):
    patient = Patient(**data)
    db.session.add(patient)
    db.session.commit()
    return patient
 
def UpdatePatientByIdentity(identity_card, data):
    patient = Patient.query.filter_by(identity_card=identity_card).first()
    if not patient:
        return None

    allowed_fields = {'email', 'medical_history', 'address', 'phone_number'}
    for key, value in data.items():
        if key in allowed_fields:
            setattr(patient, key, value)

    try:
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        print(f"Lỗi khi cập nhật bệnh nhân: {e}")
        return None

    return patient

def GetPatientByIdentity(identity_card):
   return Patient.query.filter_by(identity_card=identity_card).first()

def GetPatientById(patient_id):
    return Patient.query.get(patient_id)

def GetAllPatients(page, limit):
    offset = (page - 1) * limit
    patients = Patient.query.offset(offset).limit(limit).all()
    total = Patient.query.count()

    return {
        "page": page,
        "limit": limit,
        "total": total,
        "total_pages": (total + limit - 1) // limit,
        "data": [p.ToDict() for p in patients]
    }