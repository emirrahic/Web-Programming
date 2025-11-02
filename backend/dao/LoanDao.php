<?php
require_once __DIR__ . '/BaseDao.php';

class LoanDao extends BaseDao {
    public function __construct() {
        parent::__construct("Loan");
    }

    public function getActiveLoans() {
        return $this->query("SELECT l.*, u.name as user_name, b.title as book_title 
                           FROM Loan l 
                           JOIN Users u ON l.user_id = u.user_id 
                           JOIN Book b ON l.book_id = b.book_id 
                           WHERE l.status = 'borrowed'");
    }

    public function getUserLoans($userId) {
        return $this->query("SELECT l.*, b.title as book_title, a.name as author_name 
                           FROM Loan l 
                           JOIN Book b ON l.book_id = b.book_id 
                           JOIN Author a ON b.author_id = a.author_id 
                           WHERE l.user_id = :user_id 
                           ORDER BY l.loan_date DESC", 
                           ["user_id" => $userId]);
    }

    public function getOverdueLoans() {
        $today = date('Y-m-d');
        return $this->query("SELECT l.*, u.name as user_name, b.title as book_title, 
                           DATEDIFF(:today, l.loan_date) as days_overdue 
                           FROM Loan l 
                           JOIN Users u ON l.user_id = u.user_id 
                           JOIN Book b ON l.book_id = b.book_id 
                           WHERE l.status = 'borrowed' 
                           AND l.return_date IS NULL 
                           AND l.loan_date < DATE_SUB(:today, INTERVAL 14 DAY)", 
                           ["today" => $today]);
    }
}
?>
