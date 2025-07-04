from App._init_ import db
from App.Models.PrescriptionDetails import PrescriptionDetails
from App.Services import MedicineClient

def CreatePrescriptionDetail(data):
    if not data:
        return {"error": "Không có dữ liệu"}
    medicine_id = data['medicine_id']
    medicine_response = MedicineClient.GetMedicineById(medicine_id)
    if medicine_response is None:
        return {"error": "Không thể kết nối đến Medicine Service"}
    if not medicine_response.found:
        return {"error": "Không tìm thấy thuốc"}
    
    new_amount = medicine_response.amount -  data['amount']
    if new_amount < 0:
        return {"error": "Không đủ số lượng thuốc"}
    update_response = MedicineClient.UpdateMedicine(medicine_id, new_amount)
    if update_response is None:
        return {"error": "Không thể kết nối đến Medicine Service"}
    if not update_response.found:
        return {"error": "Không tìm thấy thuốc"}
    detail = PrescriptionDetails(**data)
    db.session.add(detail)
    db.session.commit()
    return detail

def UpdatePrescriptionDetail(prescription_id, medicine_id, data):
    detail = PrescriptionDetails.query.filter_by(prescription_id=prescription_id, medicine_id=medicine_id).first()
    if not detail:
        return None
    medicine_response = MedicineClient.GetMedicineById(medicine_id)
    if medicine_response is None:
        return {"error": "Không thể kết nối đến Medicine Service"}
    if not medicine_response.found:
        return {"error": "Không tìm thấy thuốc"}
    update_amount = medicine_response.amount - (data['amount'] - detail.amount)
    update_response = MedicineClient.UpdateMedicine(medicine_id, update_amount)
    if update_response is None:
        return {"error": "Không thể kết nối đến Medicine Service"}
    if not update_response.found:
        return {"error": "Không tìm thấy thuốc"}
    allowed_fields = {'amount', 'note'}
    for key, value in data.items():
        if key in allowed_fields:
            setattr(detail, key, value)

    try:
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        print(f"Lỗi khi cập nhật chi tiết đơn thuốc: {e}")
        return None

    return detail

def GetPrescriptionDetails(prescription_id, page, limit):
    offset = (page - 1) * limit
    details = PrescriptionDetails.query.filter_by(prescription_id=prescription_id).offset(offset).limit(limit).all()
    total_count = PrescriptionDetails.query.filter_by(prescription_id=prescription_id).count()
    return {
        "page": page,
        "limit": limit,
        "total": total_count,
        "total_pages": (total_count + limit - 1) // limit,
        "data": [detail.ToDict() for detail in details]
    }
    
def DeletePrescriptionDetail(prescription_id, medicine_id):
    detail = PrescriptionDetails.query.filter_by(prescription_id=prescription_id, medicine_id=medicine_id).first()
    if not detail:
        return None

    medicine_response = MedicineClient.GetMedicineById(medicine_id)
    if medicine_response is None:
        return {"error": "Không thể kết nối đến Medicine Service"}
    if not medicine_response.found:
        return {"error": "Không tìm thấy thuốc"}
    update_amount = medicine_response.amount + detail.amount
    update_response = MedicineClient.UpdateMedicine(medicine_id, update_amount)
    if update_response is None:
        return {"error": "Không thể kết nối đến Medicine Service"}
    if not update_response.found:
        return {"error": "Không tìm thấy thuốc"}

    try:
        db.session.delete(detail)
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        print(f"Lỗi khi xóa chi tiết đơn thuốc: {e}")
        return None

    return detail