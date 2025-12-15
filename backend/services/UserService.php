<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/UserDao.php';

class UserService extends BaseService {
    public function __construct() {
        parent::__construct(new UserDao());
    }

    protected function validate($user) {
        if (empty($user['email'])) {
            throw new Exception("Email is required");
        }
        if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Valid email is required");
        }
        if (isset($user['password']) && strlen($user['password']) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }
    }

    public function getUserByEmail($email) {
        return $this->dao->getUserByEmail($email);
    }

    public function login($email, $password) {
        $user = $this->getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        return null;
    }

    public function register($userData) {
        // Map frontend fields to database columns
        $dbData = [
            'email' => $userData['email'],
            'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
            'role' => 'user' // Default to user
        ];

        // Combine names if present, otherwise use username or email part
        if (isset($userData['first_name']) && isset($userData['last_name'])) {
            $dbData['name'] = $userData['first_name'] . ' ' . $userData['last_name'];
        } elseif (isset($userData['username'])) {
            $dbData['name'] = $userData['username'];
        } else {
            $dbData['name'] = explode('@', $userData['email'])[0];
        }

        // Handle role if provided (admin can register other admins)
        if (isset($userData['role']) && $userData['role'] === 'admin') {
            $dbData['role'] = 'admin';
        }

        // Add user and get ID
        $id = $this->add($dbData);
        
        // Return the full user object
        return $this->getById($id);
    }
}
