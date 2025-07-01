from App.Services import medicine_pb2

def MedicineToProto(medicine):
    return medicine_pb2.MedicineResponse(
        found=True,
        medicine_id=medicine.medicine_id,
        name=medicine.name,
        MFG=medicine.MFG.strftime('%d-%m-%Y') if medicine.MFG else "",
        EXP=medicine.EXP.strftime('%d-%m-%Y') if medicine.EXP else "",
        amount=medicine.amount,
        price=medicine.price,
        unit=medicine.unit,
    )
