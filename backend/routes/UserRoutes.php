<?php
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../middleware/JWTMiddleware.php';

class UserRoutes {
    private $userService;
    
    public function __construct() {
        $this->userService = new UserService();
    }
    
    public function registerRoutes() {
        
        Flight::route('GET /users', function() {
            if (!JWTMiddleware::requireAdmin()) {
                return;
            }
            
            try {
                $users = $this->userService->getAll();
                
                $users = array_map(function($user) {
                    unset($user['password']);
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

        
        Flight::route('GET /users/@id', function($id) {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                
                
                if (!JWTMiddleware::isAdmin() && $currentUser->id != $id) {
                    throw new Exception('Access denied', 403);
                }
                
                $user = $this->userService->getById($id);
                unset($user['password']);
                
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

      
        Flight::route('POST /users/register', function() {
            try {
                $data = Flight::request()->data->getData();
                $user = $this->userService->register($data);
                unset($user['password']);
                
                
                $token = JWTMiddleware::generateToken($user);
                
                Flight::json([
                    'success' => true,
                    'message' => 'User registered successfully',
                    'data' => $user,
                    'token' => $token
                ], 201);
            } catch (Exception $e) {
                Flight::json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
        });

        
        Flight::route('POST /users/login', function() {
            try {
                $data = Flight::request()->data->getData();
                $user = $this->userService->login($data['email'], $data['password']);
                
                if ($user) {
                    unset($user['password']);
                    
                    
                    $token = JWTMiddleware::generateToken($user);
                    
                    Flight::json([
                        'success' => true,
                        'message' => 'Login successful',
                        'data' => $user,
                        'token' => $token
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

       
        Flight::route('POST /users/logout', function() {
           
            Flight::json([
                'success' => true,
                'message' => 'Successfully logged out. Please remove the token from client.'
            ]);
        });

        
        Flight::route('GET /users/me', function() {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                $user = $this->userService->getById($currentUser->id);
                unset($user['password']);
                
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

       
        Flight::route('PUT /users/@id', function($id) {
            if (!JWTMiddleware::authenticate()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                
                
                if (!JWTMiddleware::isAdmin() && $currentUser->id != $id) {
                    throw new Exception('Access denied', 403);
                }
                
                $data = Flight::request()->data->getData();
                $user = $this->userService->update($id, $data);
                unset($user['password']);
                
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

    
        Flight::route('DELETE /users/@id', function($id) {
            if (!JWTMiddleware::requireAdmin()) {
                return;
            }
            
            try {
                $currentUser = JWTMiddleware::getCurrentUser();
                
               
                if ($currentUser->id == $id) {
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
?>
