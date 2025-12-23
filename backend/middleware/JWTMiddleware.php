<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTMiddleware {
    private static $secretKey;
    private static $algorithm = 'HS256';
    
    public static function init() {
        self::$secretKey = getenv('JWT_SECRET') ?: 'your-secret-key-change-this-in-production';
    }
    
   
    public static function generateToken($user) {
        self::init();
        
        $issuedAt = time();
        $expiration = $issuedAt + (int)(getenv('JWT_EXPIRATION') ?: 3600); 
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expiration,
            'iss' => 'library-management-system',
            'data' => [
                'id' => $user['user_id'] ?? $user['id'], 
                'username' => $user['name'] ?? ($user['username'] ?? 'Unknown'),
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
        
        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }
    
   
    public static function validateToken($token) {
        self::init();
        
        try {
            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            return $decoded;
        } catch (Exception $e) {
            throw new Exception('Invalid or expired token: ' . $e->getMessage(), 401);
        }
    }
    
  
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            
           
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
   
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
    
 
    public static function getCurrentUser() {
        return Flight::get('user');
    }
    
   
    public static function isAdmin() {
        $user = self::getCurrentUser();
        return $user && $user->role === 'admin';
    }
    
   
    public static function isLibrarian() {
        $user = self::getCurrentUser();
        return $user && ($user->role === 'admin' || $user->role === 'librarian');
    }
}
?>
