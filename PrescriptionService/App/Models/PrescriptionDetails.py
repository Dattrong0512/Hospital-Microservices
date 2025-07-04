from App._init_ import db
from sqlalchemy import CheckConstraint

class PrescriptionDetails(db.Model):
    __tablename__ = 'PrescriptionDetails'
    prescription_id = db.Column(db.Integer, db.ForeignKey('Prescription.prescription_id'), nullable=False)
    medicine_id = db.Column(db.Integer, nullable=False)
    amount = db.Column(db.Integer, nullable=False)
    note = db.Column(db.String(100), nullable=True)
    __table_args__ = (
        db.PrimaryKeyConstraint('prescription_id', 'medicine_id'),
    )

    def ToDict(self):
        return {
            "prescription_id": self.prescription_id,
            "medicine_id": self.medicine_id,
            "amount": self.amount,
            "note": self.note
        }
