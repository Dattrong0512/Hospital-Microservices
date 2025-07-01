from flask import Blueprint, request, jsonify
from App.Controllers import MedicineController
from flasgger import swag_from

medicine_bp = Blueprint("medicine", __name__)

@medicine_bp.route("/", methods=["POST"])
@swag_from(r"APIDocuments/CreateMedicine.yaml")
def Create():
    data = request.json
    medicine = MedicineController.CreateMedicine(data)
    return jsonify(medicine.ToDict()), 201

@medicine_bp.route("/<int:medicine_id>", methods=["PUT"])
@swag_from(r"APIDocuments/UpdateMedicine.yaml")
def Update(medicine_id):
    data = request.json
    medicine = MedicineController.UpdateMedicine(medicine_id, data)
    if not medicine:
        return jsonify({"error": "Medicine not found"}), 404
    return jsonify(medicine.ToDict())

@medicine_bp.route("/<int:medicine_id>", methods=["GET"])
@swag_from(r"APIDocuments/GetMedicineByID.yaml")
def Get(medicine_id):
    medicine = MedicineController.GetMedicineById(medicine_id)
    if not medicine:
        return jsonify({"error": "Medicine not found"}), 404
    return jsonify(medicine.ToDict())

@medicine_bp.route("/", methods=["GET"])
@swag_from(r"APIDocuments/GetAllMedicines.yaml")
def ListAll():
    try:
        page = int(request.args.get("page", 1))
        limit = int(request.args.get("limit", 10))
    except ValueError:
        return jsonify({"error": "Invalid page or limit"}), 400
    
    medicine = MedicineController.GetAllMedicines(page, limit)
    return jsonify(medicine), 200

@medicine_bp.route("/<string:name>", methods=["GET"])
@swag_from(r"APIDocuments/GetMedicineByName.yaml")
def GetByName(name):
    try:
        page = int(request.args.get("page", 1))
        limit = int(request.args.get("limit", 10))
    except ValueError:
        return jsonify({"error": "Invalid page or limit"}), 400
    medicines = MedicineController.GetMedicinesByName(name, page, limit)
    if not medicines:
        return jsonify({"error": "No medicines found"}), 404
    return jsonify(medicines), 200 

@medicine_bp.route("/near-expiry", methods=["GET"])
@swag_from(r"APIDocuments/GetMedicineNearExpiry.yaml")
def GetNearExpiry():
    try:
        page = int(request.args.get("page", 1))
        limit = int(request.args.get("limit", 10))
    except ValueError:
        return jsonify({"error": "Invalid page or limit"}), 400
    medicines = MedicineController.GetMedicineNearExpiry(page, limit)
    if not medicines:
        return jsonify({"error": "No medicines near expiry"}), 404
    return jsonify(medicines), 200

@medicine_bp.route("/<int:medicine_id>", methods=["DELETE"])
@swag_from(r"APIDocuments/DeleteMedicineByID.yaml")
def Delete(medicine_id):
    medicine = MedicineController.DeleteMedicine(medicine_id)
    if not medicine:
        return jsonify({"error": "Medicine not found"}), 404
    return jsonify({"message": "Medicine deleted successfully"}), 200
