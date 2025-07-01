from App._init_ import db
from sqlalchemy import CheckConstraint

class Prescription(db.Model):
    __tablename__ = 'Prescription'
    prescription_id = db.Column(db.Integer, primary_key=True)
    appointment_id = db.Column(db.Integer, nullable=False)
    status = db.Column(db.String(100), nullable=False)
    no_days = db.Column(db.Integer, nullable=False)

    def ToDict(self):
        return {
            "prescription_id": self.prescription_id,
            "appointment_id": self.appointment_id,
            "status": self.status,
            "no_days": self.no_days
        }
