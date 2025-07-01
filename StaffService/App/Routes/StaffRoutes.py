from flask import Blueprint, request, jsonify
from App.Controllers import StaffController
from flasgger import swag_from

staff_bp = Blueprint("staff", __name__)

@staff_bp.route("/", methods=["POST"])
@swag_from(r"APIDocuments/CreateStaff.yaml")
def Create():
    data = request.json
    staff = StaffController.CreateStaff(data)
    return jsonify(staff.ToDict()), 201

@staff_bp.route("/<string:identity_card>", methods=["PUT"])
@swag_from(r"APIDocuments/UpdateStaff.yaml")
def Update(identity_card):
    data = request.json
    staff = StaffController.UpdateStaffByIdentity(identity_card, data)
    if not staff:
        return jsonify({"error": "Employee not found"}), 404
    return jsonify(staff.ToDict())

@staff_bp.route("/byIdentity/<string:identity_card>", methods=["GET"])
@swag_from(r"APIDocuments/GetStaffByIdentity.yaml")
def GetStaffByIdentity(identity_card):
    staff = StaffController.GetStaffByIdentity(identity_card)
    if not staff:
        return jsonify({"error": "Employee not found"}), 404
    return jsonify(staff.ToDict())

@staff_bp.route("/byID", methods=["POST"])
@swag_from(r"APIDocuments/GetStaffByID.yaml")
def GetStaffById():
    data = request.json
    staff_id = data.get("staff_id", None)
    staff = StaffController.GetStaffById(staff_id)
    if not staff:
        return jsonify({"error": "Employee not found"}), 404
    return jsonify(staff.ToDict())

@staff_bp.route("/", methods=["GET"])
@swag_from(r"APIDocuments/GetAllStaff.yaml")
def ListAll():
    try:
        page = int(request.args.get("page", 1))
        limit = int(request.args.get("limit", 10))
    except ValueError:
        return jsonify({"error": "Invalid page or limit parameter"}), 400
    staff = StaffController.GetAllStaff(page, limit)
    return jsonify(staff), 200
