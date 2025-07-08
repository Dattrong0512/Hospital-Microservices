from App._init_ import db
from App.Models.Doctor import Doctor
import requests
from flask import current_app, jsonify


def CreateDoctor(data):
    # Bước 1: Gọi API tới Identity Service để tạo tài khoản
    identity_payload = {
        "userName": data["username"],
        "passWord": data["password"],
        "phoneNumber": data["phone_number"]
    }

    try:
        response = requests.post("http://identity.hospitalmicroservices.live/api/v0/account/register/user/doctor", json=identity_payload)
    except requests.exceptions.RequestException as e:
        current_app.logger.error(f"Failed to connect to Identity Service: {e}")
        raise Exception("Không thể kết nối đến dịch vụ tài khoản")

    if response.status_code != 200:
        raise Exception("Tạo tài khoản thất bại: " + response.text)

    identity_data = response.json()

    doctor_data = {
        "doctor_id": identity_data["id"],     # Lưu để liên kết tài khoản
        "username": identity_data["userName"],
        "fullname": data["fullname"],
        "identity_card": data["identity_card"],
        "birth_date": data.get("birth_date"),  # Có thể không có, nên dùng get
        "gender": data["gender"],
        "department": data["department"],
        "email": data["email"],
        "phone_number": identity_data["phoneNumber"]
    }

    doctor = Doctor(**doctor_data)
    db.session.add(doctor)
    db.session.commit()

    return doctor

def UpdateDoctorByIdentity(identity_card, data):
    doctor = Doctor.query.filter_by(identity_card=identity_card).first()
    if not doctor:
        return None

    allowed_fields = {'email', 'department', 'phone_number'}
    for key, value in data.items():
        if key in allowed_fields:
            setattr(doctor, key, value)

    try:
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        print(f"Lỗi khi cập nhật bác sĩ: {e}")
        return None

    return doctor

def GetDoctorById(doctor_id):
    return Doctor.query.get(doctor_id)

def GetDoctorByIdentity(doctor_id):
    return Doctor.query.filter_by(identity_card=doctor_id).first()

def GetDoctorsByDepartment(department, page, limit):
    offset = (page - 1) * limit
    total_doctors = Doctor.query.filter_by(department=department).count()
    doctors = Doctor.query.filter_by(department=department).offset(offset).limit(limit).all()
    return {
        "page": page,
        "limit": limit,
        "total": total_doctors,
        "total_pages": (total_doctors + limit - 1) // limit,
        "data": [d.ToDict() for d in doctors]
    }

def GetAvailableDoctors(exclude_ids, doctor_department):
    # Nếu danh sách exclude rỗng → trả tất cả
    if not exclude_ids:
        return Doctor.query.filter(Doctor.department==doctor_department).all()
    else:
        return Doctor.query.filter(~Doctor.doctor_id.in_(exclude_ids), Doctor.department==doctor_department).all()

def GetAllDoctors(page, limit):
    offset = (page - 1) * limit
    doctors = Doctor.query.offset(offset).limit(limit).all()
    total = Doctor.query.count()
    return {
        "page": page,
        "limit": limit,
        "total": total,
        "total_pages": (total + limit - 1) // limit,
        "data": [d.ToDict() for d in doctors]
    }
