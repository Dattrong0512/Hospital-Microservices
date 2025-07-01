from flask import Blueprint, request, jsonify
from App.Controllers import AppointmentController
from flasgger import swag_from

appointment_bp = Blueprint("appointment", __name__)

@appointment_bp.route("/", methods=["POST"])
@swag_from(r"APIDocuments/CreateAppointment.yaml")
def Create():
    data = request.json
    appointment = AppointmentController.CreateAppointment(data)
    if isinstance(appointment, dict) and "error" in appointment:
        # Trả về lỗi
        return jsonify(appointment), 400
    return jsonify(appointment.ToDict()), 201

@appointment_bp.route("/<int:appointment_id>", methods=["PUT"])
@swag_from(r"APIDocuments/UpdateAppointment.yaml")
def UpdateAppointment(appointment_id):
    data = request.json
    appointment = AppointmentController.UpdateAppointment(appointment_id, data)
    if not appointment:
        return jsonify({"error": "Appointment not found"}), 404
    return jsonify(appointment.ToDict())

@appointment_bp.route("/<int:appointment_id>", methods=["GET"])
@swag_from(r"APIDocuments/GetAppointmentById.yaml")
def GetAppointmentByID(appointment_id):
    appointment = AppointmentController.GetAppointmentById(appointment_id)
    if not appointment:
        return jsonify({"error": "Appointment not found"}), 404
    return jsonify(appointment.ToDict())

@appointment_bp.route("/byPatientIdentity/<string:identity_card>", methods=["GET"])
@swag_from(r"APIDocuments/GetAppointmentByPatientIdentity.yaml")
def GetAppointmentByPatientIdentity(identity_card):
    try:
        page = int(request.args.get("page", 1))
        limit = int(request.args.get("limit", 10))
    except ValueError:
        return jsonify({"error": "Invalid page or limit parameter"}), 400
    appointment = AppointmentController.GetAppointmentByPatientIdentity(identity_card, page, limit)
    return jsonify(appointment), 200

@appointment_bp.route("/availableDoctors", methods=["POST"])
@swag_from(r"APIDocuments/GetAvailableDoctors.yaml")
def GetAvailableDoctors():
    data = request.json
    doctors = AppointmentController.GetAvailableDoctors(data["doctor_department"], data["appointment_date"], data["started_time"])
    return jsonify(doctors)

@appointment_bp.route("/", methods=["GET"])
@swag_from(r"APIDocuments/GetAllAppointments.yaml")
def ListAll():
    try:
        page = int(request.args.get("page", 1))
        limit = int(request.args.get("limit", 10))
    except ValueError:
        return jsonify({"error": "Invalid page or limit parameter"}), 400
    appointment = AppointmentController.GetAllAppointments(page, limit)
    return jsonify(appointment), 200
