from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from .config import Config

db = SQLAlchemy()

def CreateApp():
    app = Flask(__name__)
    app.config.from_object(Config)

    db.init_app(app)

    from .Routes.PrescriptionRoutes import prescription_bp
    app.register_blueprint(prescription_bp, url_prefix="/api/v0/prescriptions")

    from .Routes.PrescriptionDetailsRoutes import details_bp
    app.register_blueprint(details_bp, url_prefix="/api/v0/prescription-details")

    return app