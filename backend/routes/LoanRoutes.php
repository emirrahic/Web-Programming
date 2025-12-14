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
        /**
         * @OA\Get(
         *   path="/loans",
         *   tags={"loans"},
         *   summary="Get all loans (librarian/admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active", "returned", "overdue"})),
         *   @OA\Parameter(name="user_id", in="query", @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="List of loans"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden")
         * )
         */
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

        /**
         * @OA\Get(
         *   path="/loans/{id}",
         *   tags={"loans"},
         *   summary="Get loan by ID",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="Loan found"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="Loan not found")
         * )
         */
        Flight::route('GET /loans/@id', function($id) {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                $loan = $this->loanService->getById($id);
                
                // Only allow the user who borrowed the book or a librarian to view the loan
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

        /**
         * @OA\Get(
         *   path="/loans/user/{user_id}",
         *   tags={"loans"},
         *   summary="Get loans by user ID",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="user_id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active", "returned", "overdue"})),
         *   @OA\Response(response=200, description="List of user's loans"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden")
         * )
         */
        Flight::route('GET /loans/user/@user_id', function($userId) {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                
                // Users can only view their own loans unless they are librarians
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

        /**
         * @OA\Get(
         *   path="/loans/my-loans",
         *   tags={"loans"},
         *   summary="Get current user's loans",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active", "returned", "overdue"})),
         *   @OA\Response(response=200, description="List of user's loans"),
         *   @OA\Response(response=401, description="Unauthorized")
         * )
         */
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

        /**
         * @OA\Post(
         *   path="/loans",
         *   tags={"loans"},
         *   summary="Create a new loan",
         *   security={{"bearerAuth": {}}},
         *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json",
         *     @OA\Schema(
         *       required={"book_id"},
         *       @OA\Property(property="book_id", type="integer", example=1),
         *       @OA\Property(property="user_id", type="integer", example=1, description="Required if admin/librarian, otherwise uses current user"),
         *       @OA\Property(property="due_date", type="string", format="date", example="2023-12-31")
         *     )
         *   )),
         *   @OA\Response(response=201, description="Loan created"),
         *   @OA\Response(response=400, description="Validation error"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden")
         * )
         */
        Flight::route('POST /loans', function() {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                $data = Flight::request()->data->getData();
                
                // If not a librarian, can only create loans for themselves
                if (!JWTMiddleware::isLibrarian()) {
                    $data['user_id'] = $currentUser->id;
                } elseif (!isset($data['user_id'])) {
                    throw new Exception('User ID is required', 400);
                }
                
                // Check if the book is available
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

        /**
         * @OA\Post(
         *   path="/loans/{id}/return",
         *   tags={"loans"},
         *   summary="Return a book (librarian/admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="Book returned successfully"),
         *   @OA\Response(response=400, description="Validation error"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="Loan not found")
         * )
         */
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

        /**
         * @OA\Get(
         *   path="/loans/overdue",
         *   tags={"loans"},
         *   summary="Get all overdue loans (librarian/admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\Response(response=200, description="List of overdue loans"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden")
         * )
         */
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

        /**
         * @OA\Put(
         *   path="/loans/{id}/extend",
         *   tags={"loans"},
         *   summary="Extend loan due date",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Parameter(name="days", in="query", @OA\Schema(type="integer", default=14, description="Number of days to extend the loan")),
         *   @OA\Response(response=200, description="Loan extended successfully"),
         *   @OA\Response(response=400, description="Validation error"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="Loan not found")
         * )
         */
        Flight::route('PUT /loans/@id/extend', function($id) {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                $days = (int)(Flight::request()->query['days'] ?? 14);
                
                // Get the loan to check permissions
                $loan = $this->loanService->getById($id);
                
                // Only the borrower or a librarian can extend the loan
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
