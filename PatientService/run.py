from App._init_ import CreateApp
import threading
from App.Services.PatientServer import serve as grpc_serve
from flasgger import Swagger

app = CreateApp()
swagger = Swagger(app)

if __name__ == "__main__":
    grpc_thread = threading.Thread(target=grpc_serve, args=(app,))
    grpc_thread.daemon = True  # Đảm bảo thread sẽ tắt khi Flask tắt
    grpc_thread.start()

    app.run(host="0.0.0.0", port=5001, debug=False)