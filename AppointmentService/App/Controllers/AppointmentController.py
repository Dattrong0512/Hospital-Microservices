from App._init_ import db
from App.Models.Appointment import Appointment
from App.Services import PatientClient, DoctorClient
from sqlalchemy import desc
from App.Message.Publisher import RabbitMQPublisher

def CreateAppointment(data):
    #Kiểm tra bệnh nhân có tồn tại không
    patient_id = data.get('patient_id')
    patient_response = PatientClient.GetPatientById(patient_id)
    if patient_response is None:
        return {"error": "Không thể kết nối đến Patient Service"}
    if not patient_response.found:
        return {"error": "Không tìm thấy bệnh nhân"}

    #Kiểm tra bác sĩ có tồn tại và có rảnh không.
    busy_doctor_ids = GetBusyDoctors(data['date'], data['started_time'])
    doctor_id = data.get('doctor_id')
    if doctor_id in busy_doctor_ids:
        return {"error": "Bác sĩ đã có cuộc hẹn vào thời gian này"}
    doctor_response = DoctorClient.GetDoctorById(doctor_id)
    if doctor_response is None:
        return {"error": "Không thể kết nối đến Doctor Service"}
    if not doctor_response.found:
        return {"error": "Không tìm thấy bác sĩ"}
    
    #Tạo cuộc hẹn
    appointment = Appointment(**data)
    db.session.add(appointment)
    db.session.commit()

    #Gửi thông báo đến notification service
    publisher = RabbitMQPublisher()
    publisher.send_message({
        "type": "Appointment",
        "patient_fullname": patient_response.fullname,
        "patient_email": patient_response.email,
        "doctor_fullname": doctor_response.fullname,
        "doctor_email": doctor_response.email,
        "date": data["date"],
        "started_time": data["started_time"]
    })
    return appointment

def UpdateAppointment(appointment_id, data):
    appointment = Appointment.query.get(appointment_id)
    if not appointment:
        return None
    
    allowed_fields = {'status', 'description', 'date', 'started_time'}
    for key, value in data.items():
        if key in allowed_fields:
            setattr(appointment, key, value)

    try:
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        print(f"Lỗi khi cập nhật cuộc hẹn: {e}")
        return None
    
    return appointment

def GetAppointmentById(appointment_id):
    appointment = Appointment.query.get(appointment_id)
    if not appointment:
        return None
    return appointment

def GetAppointmentByPatientIdentity(identity_card, page, limit):
    patient_response = PatientClient.GetPatientByIdentity(identity_card)
    if patient_response is None:
        return {"error": "Không thể kết nối đến Patient Service"}
    if not patient_response.found:
        return {"error": "Không tìm thấy bệnh nhân"}
    
    patient_id = patient_response.id
    offset = (page - 1) * limit
    total_appointments = Appointment.query.filter_by(patient_id=patient_id).count()
    appointments = Appointment.query.order_by(desc(Appointment.appointment_date)).filter_by(patient_id=patient_id).offset(offset).limit(limit).all()
    return {
        "total": total_appointments,
        "page": page,
        "limit": limit,
        "total_pages": (total_appointments + limit - 1) // limit,
        "data": [a.ToDict() for a in appointments]
    }

def GetBusyDoctors(appointment_date, started_time):
    busy_doctors = (
        Appointment.query
        .with_entities(Appointment.doctor_id)
        .filter_by(date=appointment_date, started_time=started_time)
        .distinct()
        .all())
    busy_doctor_ids = [doctor_id for (doctor_id,) in busy_doctors]
    return busy_doctor_ids

def GetAvailableDoctors(doctor_department, appointment_date, started_time):
    busy_doctor_ids = GetBusyDoctors(appointment_date, started_time)
    doctor_response = DoctorClient.GetAvailableDoctors(busy_doctor_ids, doctor_department)
    if doctor_response is None:
        return {"error": "Không thể kết nối đến Doctor Service"}

    available_doctors = []
    for doc in doctor_response.doctors:
        available_doctors.append({
            "doctor_id": doc.doctor_id,
            "identity_card": doc.identity_card,
            "username": doc.username,
            "fullname": doc.fullname,
            "birth_date": doc.birth_date,
            "gender": doc.gender,
            "department": doc.department,
            "email": doc.email,
            "phone_number": doc.phone_number
        })

    return available_doctors

def GetAllAppointments(page, limit):
    offset = (page - 1) * limit
    total_appointments = Appointment.query.count()
    appointments = Appointment.query.order_by(desc(Appointment.date)).offset(offset).limit(limit).all()
    return {
        "total": total_appointments,
        "page": page,
        "limit": limit,
        "total_pages": (total_appointments + limit - 1) // limit,
        "data": [a.ToDict() for a in appointments]
    }
