<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/LoanDao.php';

class LoanService extends BaseService {
    private $bookService;
    private $userService;

    public function __construct() {
        parent::__construct(new LoanDao());
        $this->bookService = new BookService();
        $this->userService = new UserService();
    }

    protected function validate($loan) {
        if (empty($loan['book_id'])) {
            throw new Exception("Book ID is required");
        }
        if (empty($loan['user_id'])) {
            throw new Exception("User ID is required");
        }
        if (empty($loan['loan_date'])) {
            $loan['loan_date'] = date('Y-m-d');
        }
        if (empty($loan['due_date'])) {
            $loan['due_date'] = date('Y-m-d', strtotime('+14 days'));
        }
        
        // Check if book is available
        $availableCopies = $this->bookService->getAvailableCopies($loan['book_id']);
        if ($availableCopies <= 0) {
            throw new Exception("No available copies of this book");
        }

        // Check if user has too many active loans (e.g., max 5)
        $activeLoans = $this->dao->getActiveLoansByUser($loan['user_id']);
        if (count($activeLoans) >= 5) {
            throw new Exception("Maximum number of active loans (5) reached");
        }

        return $loan;
    }

    public function borrowBook($loanData) {
        $loan = $this->validate($loanData);
        $loan['return_date'] = null;
        $loan['status'] = 'borrowed';
        
        return $this->add($loan);
    }

    public function returnBook($loanId) {
        $loan = $this->getById($loanId);
        if (!$loan) {
            throw new Exception("Loan not found");
        }
        
        $updateData = [
            'status' => 'returned',
            'return_date' => date('Y-m-d')
        ];
        
        return $this->update($loanId, $updateData);
    }

    public function getActiveLoansByUser($userId) {
        return $this->dao->getActiveLoansByUser($userId);
    }

    public function getOverdueLoans() {
        return $this->dao->getOverdueLoans();
    }
}
