<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTMiddleware {
    private static $secretKey;
    private static $algorithm = 'HS256';
    
    public static function init() {
        self::$secretKey = getenv('JWT_SECRET') ?: 'your-secret-key-change-this-in-production';
    }
    
    /**
     * Generate JWT token for a user
     * 
     * @param array $user User data
     * @return string JWT token
     */
    public static function generateToken($user) {
        self::init();
        
        $issuedAt = time();
        $expiration = $issuedAt + (int)(getenv('JWT_EXPIRATION') ?: 3600); // Default 1 hour
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expiration,
            'iss' => 'library-management-system',
            'data' => [
                'id' => $user['user_id'] ?? $user['id'], // Handle both formats
                'username' => $user['name'] ?? ($user['username'] ?? 'Unknown'),
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
        
        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }
    
    /**
     * Validate and decode JWT token
     * 
     * @param string $token JWT token
     * @return object Decoded token data
     * @throws Exception if token is invalid
     */
    public static function validateToken($token) {
        self::init();
        
        try {
            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            return $decoded;
        } catch (Exception $e) {
            throw new Exception('Invalid or expired token: ' . $e->getMessage(), 401);
        }
    }
    
    /**
     * Extract token from Authorization header
     * 
     * @return string|null Token or null if not found
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            
            // Check for Bearer token
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Middleware to authenticate requests
     * Adds user data to Flight request if token is valid
     */
    public static function authenticate() {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            Flight::json([
                'success' => false,
                'error' => 'No token provided'
            ], 401);
            return false;
        }
        
        try {
            $decoded = self::validateToken($token);
            
            // Store user data in Flight for access in routes
            Flight::set('user', $decoded->data);
            
            return true;
        } catch (Exception $e) {
            Flight::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 401);
            return false;
        }
    }
    
    /**
     * Middleware to check if user is admin
     */
    public static function requireAdmin() {
        if (!self::authenticate()) {
            return false;
        }
        
        $user = Flight::get('user');
        
        if (!$user || $user->role !== 'admin') {
            Flight::json([
                'success' => false,
                'error' => 'Admin access required'
            ], 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Middleware to check if user is librarian or admin
     */
    public static function requireLibrarian() {
        if (!self::authenticate()) {
            return false;
        }
        
        $user = Flight::get('user');
        
        if (!$user || ($user->role !== 'admin' && $user->role !== 'librarian')) {
            Flight::json([
                'success' => false,
                'error' => 'Librarian or admin access required'
            ], 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get current authenticated user
     * 
     * @return object|null User data or null
     */
    public static function getCurrentUser() {
        return Flight::get('user');
    }
    
    /**
     * Check if current user is admin
     * 
     * @return bool
     */
    public static function isAdmin() {
        $user = self::getCurrentUser();
        return $user && $user->role === 'admin';
    }
    
    /**
     * Check if current user is librarian or admin
     * 
     * @return bool
     */
    public static function isLibrarian() {
        $user = self::getCurrentUser();
        return $user && ($user->role === 'admin' || $user->role === 'librarian');
    }
}
?>
