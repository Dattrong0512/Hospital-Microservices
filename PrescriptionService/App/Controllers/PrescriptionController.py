from App._init_ import db
from App.Models.Prescription import Prescription
from App.Services import AppointmentClient
from App.Message.Publisher import RabbitMQPublisher

def CreatePrescription(data):
    if not data:
        return {"error": "Không có dữ liệu"}   
    appointment_id = data['appointment_id']
    appointment_response = AppointmentClient.GetAppointmentById(appointment_id)
    if appointment_response is None:
        return {"error": "Không thể kết nối đến Appointment Service"}
    if not appointment_response.found:
        return {"error": "Không tìm thấy cuộc hẹn"}

    #Tạo đơn thuốc
    prescription = Prescription(**data)
    db.session.add(prescription)
    db.session.commit()

    #Gửi thông báo đến notification service
    publisher = RabbitMQPublisher()
    publisher.send_message({
        "type": "prescription",
        "patient_id": appointment_response.patient_id,
        "appointment_id": appointment_id,
        "no_days": data['no_days']
    })
    return prescription

def UpdatePrescription(prescription_id, data):
    prescription = Prescription.query.get(prescription_id)
    if not prescription:
        return None

    allowed_fields = {'status'}
    for key, value in data.items():
        if key in allowed_fields:
            setattr(prescription, key, value)

    try:
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        print(f"Lỗi khi cập nhật đơn thuốc: {e}")
        return None

    return prescription

def GetPrescriptionByAppointmentId(appointment_id):
    return Prescription.query.filter_by(appointment_id=appointment_id).first()

def GetAllPrescriptions(page, limit):
    offset = (page - 1) * limit
    prescriptions = Prescription.query.offset(offset).limit(limit).all()
    total = Prescription.query.count()
    return {
        "page": page,
        "limit": limit,
        "total": total,
        "total_pages": (total + limit - 1) // limit,
        "data": [p.ToDict() for p in prescriptions]
    }
