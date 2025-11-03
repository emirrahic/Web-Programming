<?php
require_once __DIR__ . '/BaseDao.php';

class UserDao extends BaseDao {
    public function __construct() {
        parent::__construct("Users");
    }

    public function getUserByEmail($email) {
        return $this->queryUnique("SELECT * FROM Users WHERE email = :email", ["email" => $email]);
    }

    public function getUsersByRole($role) {
        return $this->query("SELECT * FROM Users WHERE role = :role", ["role" => $role]);
    }
}
?>
