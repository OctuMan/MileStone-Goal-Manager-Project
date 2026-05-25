<?php
class user {
    private $user_id;
    private $username;
    private $email;
    private $password_hash;
    private $role;

    const STATUS_ACTIVE = 1;
    const STATUS_PENDING = 0;
    const ROLE_ADMIN = 1;
    const ROLE_EDITOR = 2;
    const ROLE_USER = 3;

    public function __construct(array $data) {
        $this->user_id = $data['user_id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->email    = $data['email'] ?? null;
        $this->password_hash = $data['password_hash'] ?? $data['password'] ?? null; 
        $this->role     = $data['role'] ?? 'user';
    }
    public function getId() {
        return $this->user_id;
    }
   
    public function getUsername(){
        return htmlspecialchars($this-> username);
    }
    public function getEmail(){
        return filter_var($this-> email, FILTER_SANITIZE_EMAIL);
    }
    public function verifyPassword($input){
            return password_verify($input, $this->password_hash);
    }
    public function isAdmin(){
            return $this->role === "admin";
    }
    
}
