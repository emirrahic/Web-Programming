<?php
require_once __DIR__ . '/../services/BookService.php';
require_once __DIR__ . '/../middleware/JWTMiddleware.php';

class BookRoutes {
    private $bookService;
    
    public function __construct() {
        $this->bookService = new BookService();
    }
    
    public function registerRoutes() {
        
        Flight::route('GET /books', function() {
            try {
                $search = Flight::request()->query['search'] ?? '';
                $categoryId = Flight::request()->query['category_id'] ?? null;
                $page = (int)(Flight::request()->query['page'] ?? 1);
                $limit = (int)(Flight::request()->query['limit'] ?? 10);
                
                $result = $this->bookService->getAll($search, $categoryId, $page, $limit);
                Flight::json([
                    'success' => true,
                    'data' => $result['data'],
                    'pagination' => $result['pagination']
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        });

       
        Flight::route('GET /books/@id', function($id) {
            try {
                $book = $this->bookService->getById($id);
                Flight::json([
                    'success' => true,
                    'data' => $book
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() === 404 ? 404 : 400);
            }
        });

        
        Flight::route('POST /books', function() {
            if (!JWTMiddleware::requireLibrarian()) {
                return;
            }
            
            try {
                $data = Flight::request()->data->getData();
                $book = $this->bookService->add($data);
                Flight::json([
                    'success' => true,
                    'message' => 'Book created successfully',
                    'data' => $book
                ], 201);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
        });

      
        Flight::route('PUT /books/@id', function($id) {
            if (!JWTMiddleware::requireLibrarian()) {
                return;
            }
            
            try {
                $data = Flight::request()->data->getData();
                $book = $this->bookService->update($id, $data);
                Flight::json([
                    'success' => true,
                    'message' => 'Book updated successfully',
                    'data' => $book
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() === 404 ? 404 : 400);
            }
        });

      
        Flight::route('DELETE /books/@id', function($id) {
            if (!JWTMiddleware::requireAdmin()) {
                return;
            }
            
            try {
                $this->bookService->delete($id);
                Flight::json([
                    'success' => true,
                    'message' => 'Book deleted successfully'
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() === 404 ? 404 : 400);
            }
        });

        
        Flight::route('GET /books/search', function() {
            try {
                $query = Flight::request()->query['q'] ?? '';
                $books = $this->bookService->searchBooks($query);
                Flight::json([
                    'success' => true,
                    'data' => $books
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
        });
    }
}
?>
