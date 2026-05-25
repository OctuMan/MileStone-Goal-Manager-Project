<?php
session_start();

require_once 'connect.php';
require_once 'user.php';
require_once 'authManager.php';
require_once 'validator.php';


$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    sendResponse('error','No data received');
    exit;
}

$db = (new Database())->getConnection();
$auth = new AuthManager($db);


$action = $data['action'] ?? '';

function sendResponse($status, $message, $extra = [])
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(["status" => $status, "message" => $message], $extra));
    exit;
}



switch ($action) {

    case 'check_email':
        $errors = Validator::validateInputs(['email' => $data['email'] ?? '']);

        if (isset($errors['email'])) {
            sendResponse('invalid', $errors['email']);
        }
        if ($auth->isEmailTaken($data['email'])) {
            sendResponse('taken', 'Email already exists');
            
        }
        sendResponse('available', 'Email is new');
        break;

 case 'login':
    $user = $auth->attemptLogin($data['email'], $data['password']);
    
    if ($user) {
    session_regenerate_id(true); // Mandatory for security
    
    $_SESSION['user_id'] = $user->getId(); 
    $_SESSION['username'] = $user->getUsername();
    $_SESSION['user_role'] = $user->isAdmin() ? 'Admin' : 'User';
    $_SESSION['last_activity'] = time(); // To handle timeouts later
    if (isset($data['rememberMeStatus']) && $data['rememberMeStatus'] === true) {
            // Generate a secure, random string
            $rawToken = bin2hex(random_bytes(32)); 
            
            // Save the HASH of this token to the DB
            $auth->saveRememberToken($user->getId(), $rawToken);
            
            // Send the RAW token to the browser cookie
            setcookie(
                'remember_me', 
                $rawToken, 
                [
                    'expires' => time() + (86400 * 30),
                    'path' => '/',
                    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }
    sendResponse('success', 'Welcome back!');
} else {
        sendResponse('error', 'Invalid email or password');
    }
    break;

    case 'register':
        $validationErrors = Validator::validateInputs($data);
        if (!empty($validationErrors)) {
        sendResponse('validation_error', 'Please fix the errors', ['errors' => $validationErrors]);
    }
        if ($auth->isEmailTaken($data['email'])) {
            sendResponse('error_email', 'Email already exists');
        }
        if($auth -> isUsernameTaken($data['username'])){
            sendResponse('error_username', 'username already exists');
        }
        $newUser = new User($data);
        if ($auth->registerNewUser($newUser, $data['password'])) {
            sendResponse('success', 'Registration complete');
        } else {
            sendResponse('error', 'Server error during registration');
        }
        break;

    default:
        sendResponse("error", "Invalid action");
}








