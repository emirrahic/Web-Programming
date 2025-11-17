<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/UserDao.php';

class UserService extends BaseService {
    public function __construct() {
        parent::__construct(new UserDao());
    }

    protected function validate($user) {
        if (empty($user['username'])) {
            throw new Exception("Username is required");
        }
        if (strlen($user['username']) < 3 || strlen($user['username']) > 50) {
            throw new Exception("Username must be between 3 and 50 characters");
        }
        if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Valid email is required");
        }
        if (strlen($user['password']) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }
        if (!in_array(strtolower($user['role']), ['admin', 'librarian', 'member'])) {
            throw new Exception("Invalid user role");
        }
    }

    public function getUserByEmail($email) {
        return $this->dao->getUserByEmail($email);
    }

    public function login($email, $password) {
        $user = $this->getUserByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            return $user;
        }
        return null;
    }

    public function register($userData) {
        $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        unset($userData['password']);
        return $this->add($userData);
    }
}
