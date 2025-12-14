<?php
require_once __DIR__ . '/../services/BookService.php';
require_once __DIR__ . '/../middleware/JWTMiddleware.php';

class BookRoutes {
    private $bookService;
    
    public function __construct() {
        $this->bookService = new BookService();
    }
    
    public function registerRoutes() {
        /**
         * @OA\Get(
         *   path="/books",
         *   tags={"books"},
         *   summary="Get all books",
         *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
         *   @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
         *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
         *   @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=10)),
         *   @OA\Response(response=200, description="List of books")
         * )
         */
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

        /**
         * @OA\Get(
         *   path="/books/{id}",
         *   tags={"books"},
         *   summary="Get book by ID",
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="Book found"),
         *   @OA\Response(response=404, description="Book not found")
         * )
         */
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

        /**
         * @OA\Post(
         *   path="/books",
         *   tags={"books"},
         *   summary="Create a new book (librarian/admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json",
         *     @OA\Schema(
         *       required={"title", "author_id", "isbn"},
         *       @OA\Property(property="title", type="string", example="Sample Book"),
         *       @OA\Property(property="author_id", type="integer", example=1),
         *       @OA\Property(property="isbn", type="string", example="1234567890123"),
         *       @OA\Property(property="description", type="string"),
         *       @OA\Property(property="publication_year", type="integer", example=2023),
         *       @OA\Property(property="publisher", type="string"),
         *       @OA\Property(property="pages", type="integer"),
         *       @OA\Property(property="language", type="string"),
         *       @OA\Property(property="total_copies", type="integer", example=1),
         *       @OA\Property(property="available_copies", type="integer", example=1)
         *     )
         *   )),
         *   @OA\Response(response=201, description="Book created"),
         *   @OA\Response(response=400, description="Validation error"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden")
         * )
         */
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

        /**
         * @OA\Put(
         *   path="/books/{id}",
         *   tags={"books"},
         *   summary="Update a book (librarian/admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json",
         *     @OA\Schema(
         *       @OA\Property(property="title", type="string"),
         *       @OA\Property(property="author_id", type="integer"),
         *       @OA\Property(property="isbn", type="string"),
         *       @OA\Property(property="description", type="string"),
         *       @OA\Property(property="publication_year", type="integer"),
         *       @OA\Property(property="publisher", type="string"),
         *       @OA\Property(property="pages", type="integer"),
         *       @OA\Property(property="language", type="string"),
         *       @OA\Property(property="total_copies", type="integer"),
         *       @OA\Property(property="available_copies", type="integer")
         *     )
         *   )),
         *   @OA\Response(response=200, description="Book updated"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="Book not found")
         * )
         */
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

        /**
         * @OA\Delete(
         *   path="/books/{id}",
         *   tags={"books"},
         *   summary="Delete a book (admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="Book deleted"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="Book not found")
         * )
         */
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

        /**
         * @OA\Get(
         *   path="/books/search",
         *   tags={"books"},
         *   summary="Search books",
         *   @OA\Parameter(name="q", in="query", required=true, @OA\Schema(type="string")),
         *   @OA\Response(response=200, description="List of matching books")
         * )
         */
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
