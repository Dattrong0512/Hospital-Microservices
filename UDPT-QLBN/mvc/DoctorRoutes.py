from flask import Blueprint, request, jsonify
from App.Controllers import DoctorController
from flasgger import swag_from

doctor_bp = Blueprint("doctor", __name__)

@doctor_bp.route("/", methods=["POST"])
@swag_from(r"APIDocuments/CreateDoctor.yaml")
def Create():
    data = request.json
    doctor = DoctorController.CreateDoctor(data)
    return jsonify(doctor.ToDict()), 201

@doctor_bp.route("/byIdentity/<string:identity_card>", methods=["PUT"])
@swag_from(r"APIDocuments/UpdateDoctor.yaml")
def Update(identity_card):
    data = request.json
    doctor = DoctorController.UpdateDoctorByIdentity(identity_card, data)
    if not doctor:
        return jsonify({"error": "Doctor not found"}), 404
    return jsonify(doctor.ToDict())

@doctor_bp.route("/<string:identity_card>", methods=["GET"])
@swag_from(r"APIDocuments/GetDoctorByIdentity.yaml")
def GetDoctorByIdentity(identity_card):
    doctor = DoctorController.GetDoctorByIdentity(identity_card)
    if not doctor:
        return jsonify({"error": "Doctor not found"}), 404
    return jsonify(doctor.ToDict())

@doctor_bp.route("/byID", methods=["POST"])
@swag_from(r"APIDocuments/GetDoctorByID.yaml")
def GetDoctorById():
    data = request.json
    doctor_id = data.get("doctor_id", None)
    doctor = DoctorController.GetDoctorById(doctor_id)
    if not doctor:
        return jsonify({"error": "Doctor not found"}), 404
    return jsonify(doctor.ToDict())

@doctor_bp.route("/byDepartment", methods=["POST"])
@swag_from(r"APIDocuments/GetDoctorsByDepartment.yaml")
def GetDoctorsByDepartment():
    try:
        page = int(request.args.get("page", 1))
        limit = int(request.args.get("limit", 10))
    except ValueError:
        return jsonify({"error": "Invalid page or limit parameter"}), 400
    data = request.json
    doctor_department = data.get("department", None)
    doctors = DoctorController.GetDoctorsByDepartment(doctor_department, page, limit)
    if not doctors:
        return jsonify({"error": "No doctors found in this department"}), 404
    return jsonify(doctors), 200

@doctor_bp.route("/available", methods=["POST"])
@swag_from(r"APIDocuments/GetAvailableDoctors.yaml")
def GetAvailableDoctors():
    data = request.json
    exclude_ids = data.get("excluded_ids", None)
    doctor_department = data.get("doctor_department", None)
    doctors = DoctorController.GetAvailableDoctors(exclude_ids, doctor_department)
    return jsonify([d.ToDict() for d in doctors]), 200

@doctor_bp.route("/", methods=["GET"])
@swag_from(r"APIDocuments/GetAllDoctors.yaml")
def ListAll():
    try:
        page = int(request.args.get("page", 1))
        limit = int(request.args.get("limit", 10))
    except ValueError:
        return jsonify({"error": "Invalid page or limit parameter"}), 400
    doctors = DoctorController.GetAllDoctors(page, limit)
    return doctors
