<?php
require_once __DIR__ . '/../services/AuthorService.php';
require_once __DIR__ . '/../middleware/JWTMiddleware.php';

class AuthorRoutes {
    private $authorService;
    
    public function __construct() {
        $this->authorService = new AuthorService();
    }
    
    public function registerRoutes() {
      
        Flight::route('GET /authors', function() {
            try {
                $authors = $this->authorService->getAll();
                Flight::json([
                    'success' => true,
                    'data' => $authors
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        });

       
        Flight::route('GET /authors/@id', function($id) {
            try {
                $author = $this->authorService->getById($id);
                Flight::json([
                    'success' => true,
                    'data' => $author
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() === 404 ? 404 : 400);
            }
        });

        
        Flight::route('GET /authors/@id/books', function($id) {
            try {
                $books = $this->authorService->getBooksByAuthor($id);
                Flight::json([
                    'success' => true,
                    'data' => $books
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() === 404 ? 404 : 400);
            }
        });

       
        Flight::route('POST /authors', function() {
            if (!JWTMiddleware::requireLibrarian()) {
                return;
            }
            
            try {
                $data = Flight::request()->data->getData();
                $author = $this->authorService->add($data);
                Flight::json([
                    'success' => true,
                    'message' => 'Author created successfully',
                    'data' => $author
                ], 201);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
        });

        Flight::route('PUT /authors/@id', function($id) {
            if (!JWTMiddleware::requireLibrarian()) {
                return;
            }
            
            try {
                $data = Flight::request()->data->getData();
                $author = $this->authorService->update($id, $data);
                Flight::json([
                    'success' => true,
                    'message' => 'Author updated successfully',
                    'data' => $author
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() === 404 ? 404 : 400);
            }
        });

      
        Flight::route('DELETE /authors/@id', function($id) {
            if (!JWTMiddleware::requireAdmin()) {
                return;
            }
            
            try {
                $this->authorService->delete($id);
                Flight::json([
                    'success' => true,
                    'message' => 'Author deleted successfully'
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() === 404 ? 404 : 400);
            }
        });
    }
}
?>
