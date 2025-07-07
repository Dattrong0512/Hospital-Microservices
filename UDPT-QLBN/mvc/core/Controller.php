<?php
class Controller {

    public function model($model){
        require_once "./mvc/models/".$model.".php";
        return new $model;
    }

    public function view($view, $data=[]){
        // Nếu là trang đăng nhập/đăng ký thì không cần layout
        if(strpos($view, "pages/auth/") === 0) {
            require_once "./mvc/views/".$view.".php";
            return;
        }
        
        // Xác định layout phù hợp dựa trên loại người dùng
        $layout = "main"; // Layout mặc định
        
        // Nếu là trang quản trị viên
        if(strpos($view, "pages/staff/") === 0) {
            $data["Page"] = str_replace("pages/", "", $view);
        } 
        // Nếu là trang bác sĩ
        else if(strpos($view, "pages/doctor/") === 0) {
            $data["Page"] = $view;
            $layout = "doctorlo"; // Layout riêng cho bác sĩ
        } 
        // Nếu là trang người dùng
        else if(strpos($view, "pages/user/") === 0) {
            $data["Page"] = str_replace("pages/", "", $view);
        }
        // Nếu là trang khác
        else {
            $data["Page"] = str_replace("pages/", "", $view);
        }
        
        // Load layout
        require_once "./mvc/views/layouts/".$layout.".php";
    }
}
?>