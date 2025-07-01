from App._init_ import db
from sqlalchemy import CheckConstraint

class Doctor(db.Model):
    __tablename__ = 'Doctor'
    doctor_id = db.Column(db.String, primary_key=True)
    username = db.Column(db.String(100), unique=True, nullable=False)
    fullname = db.Column(db.String(100), nullable=False)
    identity_card = db.Column(db.String(12), unique=True, nullable=False)
    birth_date = db.Column(db.Date)
    gender = db.Column(db.String(10))
    department = db.Column(db.String(200)) 
    phone_number = db.Column(db.String(15), unique=True, nullable=False)  
    email = db.Column(db.String(100), unique=True, nullable=False)  


    def ToDict(self):
        return {
            "doctor_id": self.doctor_id,
            "username": self.username,
            "fullname": self.fullname,
            "identity_card": self.identity_card,
            "birth_date": self.birth_date.strftime('%d-%m-%Y') if self.birth_date else None,
            "gender": self.gender,
            "department": self.department,
            "phone_number": self.phone_number,
            "email": self.email
        }
