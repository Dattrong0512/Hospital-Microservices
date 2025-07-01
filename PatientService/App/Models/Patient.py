from App._init_ import db

class Patient(db.Model):
    __tablename__ = 'Patient'
    id = db.Column(db.Integer, primary_key=True)
    identity_card = db.Column(db.String(12), unique=True, nullable=False)
    fullname = db.Column(db.String(100), nullable=False)
    birth_date = db.Column(db.Date)
    gender = db.Column(db.String(10))
    address = db.Column(db.String(200)) 
    phone_number = db.Column(db.String(15), unique=True, nullable=False)  
    email = db.Column(db.String(100), unique=True, nullable=False)  
    medical_history = db.Column(db.Text)


    def ToDict(self):
        return {
            "id": self.id,
            "identity_card": self.identity_card,
            "fullname": self.fullname,
            "birth_date": self.birth_date.strftime('%d-%m-%Y') if self.birth_date else None,
            "gender": self.gender,
            "address": self.address,
            "phone_number": self.phone_number,
            "email": self.email,
            "medical_history": self.medical_history
        }
