from App._init_ import db
from App.Models.Staff import Staff
import requests
from flask import current_app

def CreateStaff(data):
    # Bước 1: Gọi API tới Identity Service để tạo tài khoản
    identity_payload = {
        "userName": data["username"],
        "passWord": data["password"],
        "phoneNumber": data["phone_number"]
    }

    try:
        response = requests.post("https://konggateway.hospitalmicroservices.live/api/v0/account/register/user/staff", json=identity_payload)
    except requests.exceptions.RequestException as e:
        current_app.logger.error(f"Failed to connect to Identity Service: {e}")
        raise Exception("Không thể kết nối đến dịch vụ tài khoản")

    if response.status_code != 200:
        raise Exception("Tạo tài khoản thất bại: " + response.text)

    identity_data = response.json()

    staff_data = {
        "staff_id": identity_data["id"],     # Lưu để liên kết tài khoản
        "username": identity_data["userName"],
        "fullname": data["fullname"],
        "identity_card": data["identity_card"],
        "birth_date": data.get("birth_date"),  # Có thể không có, nên dùng get
        "gender": data["gender"],
        "email": data["email"],
        "phone_number": identity_data["phoneNumber"]
    }

    staff = Staff(**staff_data)
    db.session.add(staff)
    db.session.commit()

    return staff

def UpdateStaffByIdentity(identity_card, data):
    staff = Staff.query.filter_by(identity_card=identity_card).first()
    if not staff:
        return None

    allowed_fields = {'email', 'phone_number'}
    for key, value in data.items():
        if key in allowed_fields:
            setattr(staff, key, value)

    try:
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        print(f"Lỗi khi cập nhật bác sĩ: {e}")
        return None

    return staff

def GetStaffById(staff_id):
    return Staff.query.get(staff_id)

def GetStaffByIdentity(staff_id):
    return Staff.query.filter_by(identity_card=staff_id).first()

def GetAllStaff(page, limit):
    offset = (page - 1) * limit
    staff_list = Staff.query.offset(offset).limit(limit).all()
    total = Staff.query.count()
    return {
        "page": page,
        "limit": limit,
        "total": total,
        "total_pages": (total + limit - 1) // limit,
        "data": [s.ToDict() for s in staff_list]
    }
