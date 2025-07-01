from App._init_ import db
from sqlalchemy import CheckConstraint

class Medicine(db.Model):
    __tablename__ = 'Medicine'
    medicine_id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    MFG = db.Column(db.Date, nullable=False)
    EXP = db.Column(db.Date, nullable=False)
    amount = db.Column(db.Integer, nullable=False)
    unit = db.Column(db.String(10), nullable=False)
    price = db.Column(db.Integer, nullable=False)

    __table_args__ = (
        CheckConstraint('MFG < EXP', name='check_mfg_less_than_exp'),
    )

    def ToDict(self):
        return {
            "medicine_id": self.medicine_id,
            "name": self.name,
            "MFG": self.MFG.strftime('%d-%m-%Y') if self.MFG else None,
            "EXP": self.EXP.strftime('%d-%m-%Y') if self.EXP else None,
            "amount": self.amount,
            "unit": self.unit,
            "price": self.price,
        }
