<?php
class validator
{


    public static function validateInputs(array $data)
    {
        $errors = [];

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "A valid email address is required.";
        }
        if (empty($data['username']) || !preg_match('/^[a-zA-Z][a-zA-Z0-9]{3,14}$/', $data['username'])) {
            $errors['username'] = "Username must be 4-15 alphanumeric characters and start with a letter.";
        }

        if (empty($data['password']) || !preg_match("/^(?=.*[a-z])(?=.*\d)[A-Za-z\d]{12,}$/", $data['password'])) {
            $errors['password'] = "Password should be at least 12 characters including a number and a lowercase letter.";
        }

        return $errors;
    }
}
