<?php
require_once __DIR__ . '/../services/CategoryService.php';
require_once __DIR__ . '/../middleware/JWTMiddleware.php';

class CategoryRoutes {
    private $categoryService;
    
    public function __construct() {
        $this->categoryService = new CategoryService();
    }
    
    public function registerRoutes() {
        
        Flight::route('GET /categories', function() {
            try {
                $categories = $this->categoryService->getAll();
                Flight::json([
                    'success' => true,
                    'data' => $categories
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        });

        
        Flight::route('GET /categories/@id', function($id) {
            try {
                $category = $this->categoryService->getById($id);
                Flight::json([
                    'success' => true,
                    'data' => $category
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() === 404 ? 404 : 400);
            }
        });

       
        Flight::route('GET /categories/@id/books', function($id) {
            try {
                $books = $this->categoryService->getBooksByCategory($id);
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

       
        Flight::route('POST /categories', function() {
            if (!JWTMiddleware::requireLibrarian()) {
                return;
            }
            
            try {
                $data = Flight::request()->data->getData();
                $category = $this->categoryService->add($data);
                Flight::json([
                    'success' => true,
                    'message' => 'Category created successfully',
                    'data' => $category
                ], 201);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
        });

       
        Flight::route('PUT /categories/@id', function($id) {
            if (!JWTMiddleware::requireLibrarian()) {
                return;
            }
            
            try {
                $data = Flight::request()->data->getData();
                $category = $this->categoryService->update($id, $data);
                Flight::json([
                    'success' => true,
                    'message' => 'Category updated successfully',
                    'data' => $category
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() === 404 ? 404 : 400);
            }
        });

     
        Flight::route('DELETE /categories/@id', function($id) {
            if (!JWTMiddleware::requireAdmin()) {
                return;
            }
            
            try {
                $this->categoryService->delete($id);
                Flight::json([
                    'success' => true,
                    'message' => 'Category deleted successfully'
                ], 200);
            } catch (Exception $e) {
                $statusCode = $e->getCode() === 404 ? 404 : 400;
                if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                    $statusCode = 400;
                }
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $statusCode);
            }
        });
    }
}
?>
