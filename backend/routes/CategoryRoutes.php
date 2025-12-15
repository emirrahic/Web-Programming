<?php
require_once __DIR__ . '/../services/CategoryService.php';
require_once __DIR__ . '/../middleware/JWTMiddleware.php';

class CategoryRoutes {
    private $categoryService;
    
    public function __construct() {
        $this->categoryService = new CategoryService();
    }
    
    public function registerRoutes() {
        /**
         * @OA\Get(
         *   path="/categories",
         *   tags={"categories"},
         *   summary="Get all categories",
         *   @OA\Response(response=200, description="List of categories")
         * )
         */
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

        /**
         * @OA\Get(
         *   path="/categories/{id}",
         *   tags={"categories"},
         *   summary="Get category by ID",
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="Category found"),
         *   @OA\Response(response=404, description="Category not found")
         * )
         */
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

        /**
         * @OA\Get(
         *   path="/categories/{id}/books",
         *   tags={"categories"},
         *   summary="Get books by category",
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="List of books in category")
         * )
         */
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

        /**
         * @OA\Post(
         *   path="/categories",
         *   tags={"categories"},
         *   summary="Create a new category (librarian/admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json",
         *     @OA\Schema(
         *       required={"name"},
         *       @OA\Property(property="name", type="string", example="Fiction"),
         *       @OA\Property(property="description", type="string", example="Fictional works")
         *     )
         *   )),
         *   @OA\Response(response=201, description="Category created"),
         *   @OA\Response(response=400, description="Validation error"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden")
         * )
         */
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

        /**
         * @OA\Put(
         *   path="/categories/{id}",
         *   tags={"categories"},
         *   summary="Update a category (librarian/admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json",
         *     @OA\Schema(
         *       @OA\Property(property="name", type="string"),
         *       @OA\Property(property="description", type="string")
         *     )
         *   )),
         *   @OA\Response(response=200, description="Category updated"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="Category not found")
         * )
         */
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

        /**
         * @OA\Delete(
         *   path="/categories/{id}",
         *   tags={"categories"},
         *   summary="Delete a category (admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="Category deleted"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="Category not found")
         * )
         */
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
