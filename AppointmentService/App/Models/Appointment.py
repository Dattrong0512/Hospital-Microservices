from App._init_ import db

class Appointment(db.Model):
    __tablename__ = 'Appointment'
    appointment_id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, nullable=False)
    doctor_id = db.Column(db.String, nullable=False)
    status = db.Column(db.String(12), nullable=False)
    description = db.Column(db.String(100))
    date = db.Column(db.Date, nullable=False)
    started_time = db.Column(db.String(10), nullable=False)

    def ToDict(self):
        return {
            "appointment_id": self.appointment_id,
            "patient_id": self.patient_id,
            "doctor_id": self.doctor_id,
            "status": self.status,
            "description": self.description,
            "date": self.date.strftime('%d-%m-%Y') if self.date else None,
            "started_time": self.started_time
        }
