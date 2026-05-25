<?php


class authManager
{
    private $db;

    public function __construct($databaseConnection)
    {
        $this->db = $databaseConnection;
    }

   public function attemptLogin($email, $password)
{
    $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) return false;

    // Create the user object from the database row
    $user = new User($row);
if ($user->verifyPassword($password)) {
    return $user; 
} else {
    // This will tell us if the password check failed INSIDE the class
    error_log("Password verify failed for: " . $email);
    return false;
}

    return false;
}

    public function registerNewUser(user $user, $pass){
    $email = $user -> getEmail();
    $username = $user-> getUsername();
    $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $this->db->prepare("INSERT INTO `users` (`username`, `email`, `password_hash`, `status`, `role_id`) VALUES (?, ?,?, ?, ?);");
    return $stmt->execute([$username, $email, $hashedPassword, User::STATUS_ACTIVE, User::ROLE_USER]);
    
    }

    public function isEmailTaken($email){
        $stmt = $this->db->prepare("SELECT user_id FROM users where email = ? LIMIT 1");
        $stmt-> execute([$email]);
        return (bool) $stmt -> fetch();
    }
     public function isUsernameTaken($username){
        $stmt = $this->db->prepare("SELECT user_id FROM users where username = ? LIMIT 1");
        $stmt-> execute([$username]);
        return (bool) $stmt -> fetch();
    }
    public function saveRememberToken($userId, $token) {
    // 1. Hash the token for database storage (Security best practice)
    $tokenHash = hash('sha256', $token);
    
    // 2. Set an expiration date (30 days from now)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

    // 3. Insert into your user_tokens table
    $stmt = $this->db->prepare("
        INSERT INTO user_tokens (user_id, token_hash, expires_at) 
        VALUES (?, ?, ?)
    ");
    
    return $stmt->execute([$userId, $tokenHash, $expiresAt]);
}

public function loginWithToken($token) {
    $tokenHash = hash('sha256', $token);
    
    // Check if the token exists and hasn't expired
    $stmt = $this->db->prepare("
        SELECT user_id FROM user_tokens 
        WHERE token_hash = ? AND expires_at > NOW() 
        LIMIT 1
    ");
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Token is valid! Fetch the full user data
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$row['user_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return new User($userData);
    }
    return false;
}
}
