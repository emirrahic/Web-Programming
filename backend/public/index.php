<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Set error reporting
if (getenv('APP_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Register Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Initialize FlightPHP
Flight::set('flight.views.path', __DIR__ . '/../frontend/views');

// Database configuration
$dbConfig = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'name' => getenv('DB_NAME') ?: 'library_db',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4'
];

// Initialize database connection
$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
$pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

// Register database connection with Flight
Flight::register('db', 'PDO', array($dsn, $dbConfig['user'], $dbConfig['pass']));

// CORS headers
Flight::before('start', function() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if (Flight::request()->method === 'OPTIONS') {
        Flight::json(null, 204);
        exit();
    }
});

// Error handling
Flight::map('error', function(Throwable $error) {
    // Log the error
    error_log($error->getMessage());
    
    // Return JSON response
    $response = [
        'success' => false,
        'error' => 'An error occurred',
    ];
    
    if (getenv('APP_DEBUG') === 'true') {
        $response['error'] = $error->getMessage();
        $response['trace'] = $error->getTraceAsString();
    }
    
    Flight::json($response, 500);
});

// 404 handler
Flight::map('notFound', function() {
    Flight::json([
        'success' => false,
        'error' => 'Endpoint not found'
    ], 404);
});

// Include route files
$routeFiles = glob(__DIR__ . '/../backend/routes/*.php');
foreach ($routeFiles as $file) {
    require_once $file;
    
    // Extract class name from filename
    $className = '\\' . basename($file, '.php');
    
    // Create instance and register routes
    if (class_exists($className)) {
        $routeInstance = new $className();
        if (method_exists($routeInstance, 'registerRoutes')) {
            $routeInstance->registerRoutes();
        }
    }
}

// API Documentation Route
Flight::route('GET /api-docs', function() {
    $openapi = \OpenApi\Generator::scan([__DIR__ . '/../backend/routes']);
    header('Content-Type: application/json');
    echo $openapi->toJson();
});

// Serve Swagger UI
Flight::route('GET /docs', function() {
    $swaggerUiPath = __DIR__ . '/../vendor/swagger-api/swagger-ui/dist';
    if (is_dir($swaggerUiPath)) {
        $swaggerFile = file_get_contents($swaggerUiPath . '/index.html');
        $swaggerFile = str_replace(
            'https://petstore.swagger.io/v2/swagger.json',
            '/api-docs',
            $swaggerFile
        );
        echo $swaggerFile;
    } else {
        echo 'Swagger UI is not installed. Run: composer require --dev zircote/swagger-php';
    }
});

// Root route - Serve the main SPA
Flight::route('GET /', function() {
    Flight::render('index.html');
});

// Start the application
Flight::start();
