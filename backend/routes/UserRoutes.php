<?php
require_once __DIR__ . '/../services/UserService.php';

class UserRoutes {
    private $userService;
    
    public function __construct() {
        $this->userService = new UserService();
    }
    
    public function registerRoutes() {
        /**
         * @OA\Get(
         *   path="/users",
         *   tags={"users"},
         *   summary="Get all users (admin only)",
         *   security={{"api_key": {}}},
         *   @OA\Response(response=200, description="List of users"),
         *   @OA\Response(response=403, description="Forbidden")
         * )
         */
        Flight::route('GET /users', function() {
            try {
                if (!isAdmin()) {
                    throw new Exception('Access denied', 403);
                }
                
                $users = $this->userService->getAll();
                // Remove sensitive data
                $users = array_map(function($user) {
                    unset($user['password_hash']);
                    return $user;
                }, $users);
                
                Flight::json([
                    'success' => true,
                    'data' => $users
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
         *   path="/users/{id}",
         *   tags={"users"},
         *   summary="Get user by ID",
         *   security={{"api_key": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="User found"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="User not found")
         * )
         */
        Flight::route('GET /users/@id', function($id) {
            try {
                // Only allow users to view their own profile, or admins to view any profile
                if (!isAdmin() && $_SESSION['user']['id'] != $id) {
                    throw new Exception('Access denied', 403);
                }
                
                $user = $this->userService->getById($id);
                unset($user['password_hash']);
                
                Flight::json([
                    'success' => true,
                    'data' => $user
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
         *   path="/users/register",
         *   tags={"users"},
         *   summary="Register a new user",
         *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json",
         *     @OA\Schema(
         *       required={"username", "email", "password"},
         *       @OA\Property(property="username", type="string", example="johndoe"),
         *       @OA\Property(property="email", type="string", format="email", example="john@example.com"),
         *       @OA\Property(property="password", type="string", format="password", example="password123"),
         *       @OA\Property(property="first_name", type="string", example="John"),
         *       @OA\Property(property="last_name", type="string", example="Doe")
         *     )
         *   )),
         *   @OA\Response(response=201, description="User registered"),
         *   @OA\Response(response=400, description="Validation error")
         * )
         */
        Flight::route('POST /users/register', function() {
            try {
                $data = Flight::request()->data->getData();
                $user = $this->userService->register($data);
                unset($user['password_hash']);
                
                Flight::json([
                    'success' => true,
                    'message' => 'User registered successfully',
                    'data' => $user
                ], 201);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
        });

        /**
         * @OA\Post(
         *   path="/users/login",
         *   tags={"users"},
         *   summary="User login",
         *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json",
         *     @OA\Schema(
         *       required={"email", "password"},
         *       @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
         *       @OA\Property(property="password", type="string", format="password", example="admin123")
         *     )
         *   )),
         *   @OA\Response(response=200, description="Login successful"),
         *   @OA\Response(response=401, description="Invalid credentials")
         * )
         */
        Flight::route('POST /users/login', function() {
            try {
                $data = Flight::request()->data->getData();
                $user = $this->userService->login($data['email'], $data['password']);
                
                if ($user) {
                    // Set user session
                    $_SESSION['user'] = $user;
                    unset($user['password_hash']);
                    
                    Flight::json([
                        'success' => true,
                        'message' => 'Login successful',
                        'data' => $user
                    ]);
                } else {
                    throw new Exception('Invalid email or password', 401);
                }
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() ?: 400);
            }
        });

        /**
         * @OA\Post(
         *   path="/users/logout",
         *   tags={"users"},
         *   summary="User logout",
         *   security={{"api_key": {}}},
         *   @OA\Response(response=200, description="Logout successful")
         * )
         */
        Flight::route('POST /users/logout', function() {
            session_destroy();
            Flight::json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        });

        /**
         * @OA\Put(
         *   path="/users/{id}",
         *   tags={"users"},
         *   summary="Update user profile",
         *   security={{"api_key": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json",
         *     @OA\Schema(
         *       @OA\Property(property="username", type="string"),
         *       @OA\Property(property="email", type="string", format="email"),
         *       @OA\Property(property="current_password", type="string", format="password"),
         *       @OA\Property(property="new_password", type="string", format="password"),
         *       @OA\Property(property="first_name", type="string"),
         *       @OA\Property(property="last_name", type="string")
         *     )
         *   )),
         *   @OA\Response(response=200, description="User updated"),
         *   @OA\Response(response=400, description="Validation error"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="User not found")
         * )
         */
        Flight::route('PUT /users/@id', function($id) {
            try {
                // Only allow users to update their own profile, or admins to update any profile
                if (!isAdmin() && $_SESSION['user']['id'] != $id) {
                    throw new Exception('Access denied', 403);
                }
                
                $data = Flight::request()->data->getData();
                $user = $this->userService->update($id, $data);
                unset($user['password_hash']);
                
                // Update session if current user updated their own profile
                if ($_SESSION['user']['id'] == $id) {
                    $_SESSION['user'] = array_merge($_SESSION['user'], $user);
                }
                
                Flight::json([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'data' => $user
                ], 200);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], $e->getCode() ?: 400);
            }
        });

        /**
         * @OA\Delete(
         *   path="/users/{id}",
         *   tags={"users"},
         *   summary="Delete a user (admin only)",
         *   security={{"api_key": {}}},
         *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *   @OA\Response(response=200, description="User deleted"),
         *   @OA\Response(response=403, description="Forbidden"),
         *   @OA\Response(response=404, description="User not found")
         * )
         */
        Flight::route('DELETE /users/@id', function($id) {
            try {
                if (!isAdmin()) {
                    throw new Exception('Access denied', 403);
                }
                
                // Prevent deleting own account
                if ($_SESSION['user']['id'] == $id) {
                    throw new Exception('Cannot delete your own account', 400);
                }
                
                $this->userService->delete($id);
                
                Flight::json([
                    'success' => true,
                    'message' => 'User deleted successfully'
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

// Helper functions
function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}
?>
