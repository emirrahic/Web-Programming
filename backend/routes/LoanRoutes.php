<?php
require_once __DIR__ . '/../services/LoanService.php';
require_once __DIR__ . '/../services/BookService.php';
require_once __DIR__ . '/../middleware/JWTMiddleware.php';

class LoanRoutes {
    private $loanService;
    private $bookService;
    
    public function __construct() {
        $this->loanService = new LoanService();
        $this->bookService = new BookService();
    }
    
    public function registerRoutes() {
        
        Flight::route('GET /loans', function() {
            if (!JWTMiddleware::requireLibrarian()) {
                return;
            }
            
            try {
                $status = Flight::request()->query['status'] ?? null;
                $userId = Flight::request()->query['user_id'] ?? null;
                
                $loans = $this->loanService->getAll($status, $userId);
                
                Flight::json([
                    'success' => true,
                    'data' => $loans
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() ?: 500);
            }
        });

       
        Flight::route('GET /loans/@id', function($id) {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                $loan = $this->loanService->getById($id);
                
                
                if (!JWTMiddleware::isLibrarian() && $currentUser->id != $loan['user_id']) {
                    throw new Exception('Access denied', 403);
                }
                
                Flight::json([
                    'success' => true,
                    'data' => $loan
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() ?: 400);
            }
        });

      
        Flight::route('GET /loans/user/@user_id', function($userId) {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                
                
                if (!JWTMiddleware::isLibrarian() && $currentUser->id != $userId) {
                    throw new Exception('Access denied', 403);
                }
                
                $status = Flight::request()->query['status'] ?? null;
                $loans = $this->loanService->getLoansByUser($userId, $status);
                
                Flight::json([
                    'success' => true,
                    'data' => $loans
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() ?: 400);
            }
        });

       
        Flight::route('GET /loans/my-loans', function() {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                $status = Flight::request()->query['status'] ?? null;
                $loans = $this->loanService->getLoansByUser($currentUser->id, $status);
                
                Flight::json([
                    'success' => true,
                    'data' => $loans
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() ?: 400);
            }
        });

     
        Flight::route('POST /loans', function() {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                $data = Flight::request()->data->getData();
                
               
                if (!JWTMiddleware::isLibrarian()) {
                    $data['user_id'] = $currentUser->id;
                } elseif (!isset($data['user_id'])) {
                    throw new Exception('User ID is required', 400);
                }
                
                $book = $this->bookService->getById($data['book_id']);
                if (!$book || $book['available_copies'] <= 0) {
                    throw new Exception('Book is not available for loan', 400);
                }
                
                $loan = $this->loanService->borrowBook($data);
                
                Flight::json([
                    'success' => true,
                    'message' => 'Book borrowed successfully',
                    'data' => $loan
                ], 201);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() ?: 400);
            }
        });

     
        Flight::route('POST /loans/@id/return', function($id) {
            if (!JWTMiddleware::requireLibrarian()) {
                return;
            }
            
            try {
                $loan = $this->loanService->returnBook($id);
                
                Flight::json([
                    'success' => true,
                    'message' => 'Book returned successfully',
                    'data' => $loan
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() ?: 400);
            }
        });

       
        Flight::route('GET /loans/overdue', function() {
            if (!JWTMiddleware::requireLibrarian()) {
                return;
            }
            
            try {
                $loans = $this->loanService->getOverdueLoans();
                
                Flight::json([
                    'success' => true,
                    'data' => $loans
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() ?: 400);
            }
        });

       
        Flight::route('PUT /loans/@id/extend', function($id) {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                $days = (int)(Flight::request()->query['days'] ?? 14);
                
                
                $loan = $this->loanService->getById($id);
                
                
                if (!JWTMiddleware::isLibrarian() && $currentUser->id != $loan['user_id']) {
                    throw new Exception('Access denied', 403);
                }
                
                $updatedLoan = $this->loanService->extendLoan($id, $days);
                
                Flight::json([
                    'success' => true,
                    'message' => 'Loan extended successfully',
                    'data' => $updatedLoan
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() ?: 400);
            }
        });
    }
}
?>
