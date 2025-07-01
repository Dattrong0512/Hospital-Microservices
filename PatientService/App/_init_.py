from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from .config import Config

db = SQLAlchemy()

def CreateApp():
    app = Flask(__name__)
    app.config.from_object(Config)

    db.init_app(app)

    from .Routes.PatientRoutes import patient_bp
    app.register_blueprint(patient_bp, url_prefix="/api/v0/patients")

    return app