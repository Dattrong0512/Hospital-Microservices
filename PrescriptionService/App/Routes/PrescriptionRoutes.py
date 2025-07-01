from flask import Blueprint, request, jsonify
from App.Controllers import PrescriptionController
from flasgger import swag_from

prescription_bp = Blueprint('prescriptions', __name__)

@prescription_bp.route('/', methods=['POST'])
@swag_from(r"APIDocuments/CreatePrescription.yaml")
def CreatePrescription():
    data = request.get_json()
    prescription = PrescriptionController.CreatePrescription(data)
    if not prescription:
        return jsonify({"error": "Failed to create prescription"}), 500
    if isinstance(prescription, dict) and "error" in prescription:
        return jsonify(prescription), 400

    return jsonify(prescription.ToDict()), 201

@prescription_bp.route('/<int:prescription_id>', methods=['PUT'])
@swag_from(r"APIDocuments/UpdatePrescription.yaml")
def UpdatePrescription(prescription_id):
    data = request.get_json()
    if not data:
        return jsonify({"error": "Invalid data provided"}), 400
    prescription = PrescriptionController.UpdatePrescription(prescription_id, data)
    if not prescription:
        return jsonify({"error": "Failed to update prescription"}), 500
    
    return jsonify(prescription.ToDict()), 200

@prescription_bp.route('/<int:prescription_id>', methods=['GET'])
@swag_from(r"APIDocuments/GetPrescriptionByID.yaml")
def GetPrescription(prescription_id):
    prescription = PrescriptionController.GetPrescriptionById(prescription_id)
    if not prescription:
        return jsonify({"error": "Prescription not found"}), 404
    
    return jsonify(prescription.ToDict()), 200

@prescription_bp.route('/', methods=['GET'])
@swag_from(r"APIDocuments/GetAllPrescriptions.yaml")
def GetAllPrescriptions():
    try:
        page = int(request.args.get('page', 1))
        limit = int(request.args.get('limit', 10))
    except ValueError:
        return jsonify({"error": "Invalid page or limit parameter"}), 400
    prescriptions = PrescriptionController.GetAllPrescriptions(page, limit)
    if not prescriptions:
        return jsonify({"error": "Prescription not found"}), 404
    
    return jsonify(prescriptions), 200