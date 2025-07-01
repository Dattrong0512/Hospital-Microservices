from flask import Blueprint, request, jsonify
from App.Controllers import PrescriptionDetailsController
from flasgger import swag_from

details_bp = Blueprint('prescription_details', __name__)

@details_bp.route('/', methods=['POST'])
@swag_from(r"APIDocuments/CreatePrescriptionDetail.yaml")
def CreatePrescriptionDetail():
    data = request.get_json()
    
    detail = PrescriptionDetailsController.CreatePrescriptionDetail(data)
    if not detail:
        return jsonify({"error": "Failed to create prescription detail"}), 500
    if isinstance(detail, dict) and "error" in detail:
        return jsonify(detail), 400
    return jsonify(detail.ToDict()), 201

@details_bp.route('/', methods=['PUT'])
@swag_from(r"APIDocuments/UpdatePrescriptionDetail.yaml")
def UpdatePrescriptionDetail():
    data = request.get_json()
    if not data or 'prescription_id' not in data or 'medicine_id' not in data:
        return jsonify({"error": "Invalid data provided"}), 400
    
    prescription_id = data['prescription_id']
    medicine_id = data['medicine_id']
    
    detail = PrescriptionDetailsController.UpdatePrescriptionDetail(prescription_id, medicine_id, data)
    if not detail:
        return jsonify({"error": "Failed to update prescription detail"}), 500
    
    return jsonify(detail.ToDict()), 200

@details_bp.route('/<int:prescription_id>', methods=['GET'])
@swag_from(r"APIDocuments/GetAllPrescriptionDetails.yaml")
def GetPrescriptionDetails(prescription_id):
    try:
        page = int(request.args.get('page', 1))
        limit = int(request.args.get('limit', 10))
    except ValueError:
        return jsonify({"error": "Invalid page or limit parameter"}), 400
    details = PrescriptionDetailsController.GetPrescriptionDetails(prescription_id, page, limit)
    if not details:
        return jsonify({"error": "No details found for this prescription"}), 404
    
    return jsonify(details), 200

@details_bp.route('/', methods=['DELETE'])
@swag_from(r"APIDocuments/DeletePrescriptionDetail.yaml")
def DeletePrescriptionDetail():
    data = request.get_json()
    if not data or 'prescription_id' not in data or 'medicine_id' not in data:
        return jsonify({"error": "Invalid data provided"}), 400
    
    prescription_id = data['prescription_id']
    medicine_id = data['medicine_id']
    
    detail = PrescriptionDetailsController.DeletePrescriptionDetail(prescription_id, medicine_id)
    if not detail:
        return jsonify({"error": "Failed to delete prescription detail"}), 500
    
    return jsonify(detail.ToDict()), 200