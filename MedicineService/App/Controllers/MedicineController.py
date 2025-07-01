from App._init_ import db
from App.Models.Medicine import Medicine

def CreateMedicine(data):
    medicine = Medicine(**data)
    db.session.add(medicine)
    db.session.commit()
    return medicine

def DeleteMedicine(medicine_id):
    medicine = Medicine.query.get(medicine_id)
    if not medicine:
        return None

    db.session.delete(medicine)
    db.session.commit()
    return medicine

def UpdateMedicine(medicine_id, data):
    medicine = Medicine.query.get(medicine_id)
    if not medicine:
        return None

    allowed_fields = {'amount', 'price', 'unit', 'MFG', 'EXP'}
    for key, value in data.items():
        if key in allowed_fields:
            setattr(medicine, key, value)

    try:
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        print(f"Lỗi khi cập nhật thuốc: {e}")
        return None

    return medicine

def UpdateAmount(medicine_id, amount):
    medicine = Medicine.query.get(medicine_id)
    if not medicine:
        return None
    setattr(medicine, "amount", amount)
    try:
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        print(f"Lỗi khi cập nhật thuốc: {e}")
        return None
    return medicine

def GetMedicineById(medicine_id):
    return Medicine.query.get(medicine_id)

def GetMedicinesByName(name, page, limit):
    offset = (page - 1) * limit
    medicines = Medicine.query.filter(Medicine.name.ilike(f'%{name}%')).offset(offset).limit(limit).all()
    total = Medicine.query.filter(Medicine.name.ilike(f'%{name}%')).count()
    return {
        "page": page,
        "limit": limit,
        "total": total,
        "total_pages": (total + limit - 1) // limit,
        "data": [m.ToDict() for m in medicines]
    }

def GetMedicineNearExpiry(page, limit):
    from datetime import datetime, timedelta
    threshold_date = datetime.now() + timedelta(days=7)
    offset = (page - 1) * limit
    medicines = Medicine.query.filter(Medicine.EXP <= threshold_date).offset(offset).limit(limit).all()
    total = Medicine.query.filter(Medicine.EXP <= threshold_date).count()
    return {
        "page": page,
        "limit": limit,
        "total": total,
        "total_pages": (total + limit - 1) // limit,
        "data": [m.ToDict() for m in medicines]
    }

def GetAllMedicines(page, limit):
    offset = (page - 1) * limit
    medicines = Medicine.query.offset(offset).limit(limit).all()
    total = Medicine.query.count()
    return {
        "page": page,
        "limit": limit,
        "total": total,
        "total_pages": (total + limit - 1) // limit,
        "data": [m.ToDict() for m in medicines]
    }
