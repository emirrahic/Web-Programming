<?php
require_once __DIR__ . '/../services/AuthorService.php';
require_once __DIR__ . '/../middleware/JWTMiddleware.php';

class AuthorRoutes {
    private $authorService;
    
    public function __construct() {
        $this->authorService = new AuthorService();
    }
    
    public function registerRoutes() {
        /**
         * @OA\Get(
         *   path="/authors",
         *   tags={"authors"},
         *   summary="Get all authors",
         *   @OA\Response(response=200, description="List of authors")
         * )
         */
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

        /**
         * @OA\Get(
         *   path="/authors/{id}",
         *   tags={"authors"},
         *   summary="Get author by ID",
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="Author found"),
         *   @OA\Response(response=404, description="Author not found")
         * )
         */
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

        /**
         * @OA\Get(
         *   path="/authors/{id}/books",
         *   tags={"authors"},
         *   summary="Get books by author",
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="List of books by author")
         * )
         */
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

        /**
         * @OA\Post(
         *   path="/authors",
         *   tags={"authors"},
         *   summary="Create a new author (librarian/admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json",
         *     @OA\Schema(
         *       required={"name"},
         *       @OA\Property(property="name", type="string", example="J.K. Rowling"),
         *       @OA\Property(property="biography", type="string", example="British author best known for the Harry Potter series.")
         *     )
         *   )),
         *   @OA\Response(response=201, description="Author created"),
         *   @OA\Response(response=400, description="Validation error"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden")
         * )
         */
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

        /**
         * @OA\Put(
         *   path="/authors/{id}",
         *   tags={"authors"},
         *   summary="Update an author (librarian/admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json",
         *     @OA\Schema(
         *       @OA\Property(property="name", type="string", example="Updated Author Name"),
         *       @OA\Property(property="biography", type="string")
         *     )
         *   )),
         *   @OA\Response(response=200, description="Author updated"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="Author not found")
         * )
         */
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

        /**
         * @OA\Delete(
         *   path="/authors/{id}",
         *   tags={"authors"},
         *   summary="Delete an author (admin only)",
         *   security={{"bearerAuth": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="Author deleted"),
         *   @OA\Response(response=401, description="Unauthorized"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="Author not found")
         * )
         */
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
