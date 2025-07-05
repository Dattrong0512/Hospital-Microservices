<?php
class User extends Controller {
    
    public function __construct() {
        // Check if user has proper role
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
            header("Location: /Auth/login");
            exit;
        }
    }
    
    public function dashboard() {
        $data = [
            'username' => $_SESSION['username'],
            'fullname' => $_SESSION['fullname']
        ];
        
        $this->view("pages/user/Dashboard", $data);
    }
}