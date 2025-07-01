from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from .config import Config

db = SQLAlchemy()

def CreateApp():
    app = Flask(__name__)
    app.config.from_object(Config)

    db.init_app(app)

    from .Routes.MedicineRoutes import medicine_bp
    app.register_blueprint(medicine_bp, url_prefix="/api/v0/medicines")

    return app