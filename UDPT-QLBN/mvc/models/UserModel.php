<?php
class UserModel {
    private $db;
    private $tempUsers = [
        [
            'username' => 'user@example.com', 
            'password' => 'user123', 
            'id' => 1, 
            'role' => 'user',
            'fullname' => 'Nguyễn Văn A'
        ],
        [
            'username' => 'staff@example.com', 
            'password' => 'staff123', 
            'id' => 2, 
            'role' => 'staff',
            'fullname' => 'Trần Thị B'
        ],
        [
            'username' => 'doctor@example.com', 
            'password' => 'doctor123', 
            'id' => 3, 
            'role' => 'doctor',
            'fullname' => 'Bác sĩ Lê C'
        ]
    ];
    
    public function __construct() {
        // Database connection would go here in a real implementation
        // $this->db = new DB();
    }
    
    public function authenticate($username, $password) {
        // Check against our temporary user accounts
        foreach ($this->tempUsers as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                return [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'fullname' => $user['fullname']
                ];
            }
        }
        
        // When no match is found
        return null;
    }
    
    public function getUserByUsername($username) {
        foreach ($this->tempUsers as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }
        return null;
    }
    
    public function getUserById($id) {
        foreach ($this->tempUsers as $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }
        return null;
    }
}