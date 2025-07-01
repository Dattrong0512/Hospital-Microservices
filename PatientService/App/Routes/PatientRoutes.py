from flask import Blueprint, request, jsonify
from App.Controllers import PatientController
from flasgger import swag_from

patient_bp = Blueprint("patient", __name__)
@swag_from(r"APIDocuments/CreatePatient.yaml")
@patient_bp.route("/", methods=["POST"])
def Create():
    data = request.json
    patient = PatientController.CreatePatient(data)
    return jsonify(patient.ToDict()), 201

@patient_bp.route("/<string:identity_card>", methods=["PUT"])
@swag_from(r"APIDocuments/UpdatePatient.yaml")
def Update(identity_card):
    data = request.json
    patient = PatientController.UpdatePatientByIdentity(identity_card, data)
    if not patient:
        return jsonify({"error": "Patient not found"}), 404
    return jsonify(patient.ToDict())

@patient_bp.route("/byId/<int:patient_id>", methods=["GET"])
@swag_from(r"APIDocuments/GetPatientByID.yaml")
def GetPatientById(patient_id):
    patient = PatientController.GetPatientById(patient_id)
    if not patient:
        return jsonify({"error": "Patient not found"}), 404
    return jsonify(patient.ToDict())

@patient_bp.route("/byIdentity/<string:identity_card>", methods=["GET"])
@swag_from(r"APIDocuments/GetPatientByIdentity.yaml")
def GetPatientByIdentity(identity_card):
    patient = PatientController.GetPatientByIdentity(identity_card)
    if not patient:
        return jsonify({"error": "Patient not found"}), 404
    return jsonify(patient.ToDict())

@patient_bp.route("/", methods=["GET"])
@swag_from(r"APIDocuments/GetAllPatients.yaml")
def ListAll():
    try:
        page = int(request.args.get("page", 1))
        limit = int(request.args.get("limit", 1))
    except ValueError:
        return {"error": "page và limit phải là số"}, 400

    result = PatientController.GetAllPatients(page, limit)
    return jsonify(result)
