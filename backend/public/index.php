<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}


require_once __DIR__ . '/../middleware/JWTMiddleware.php';


if (getenv('APP_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}


require_once __DIR__ . '/../vendor/autoload.php';


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}


Flight::set('flight.views.path', __DIR__ . '/../frontend/views');


$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'name' => $_ENV['DB_NAME'] ?? 'projekat',
    'user' => $_ENV['DB_USER'] ?? 'root',
    'pass' => $_ENV['DB_PASS'] ?? '',
    'charset' => 'utf8mb4'
];


try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    
    Flight::register('db', 'PDO', array($dsn, $dbConfig['user'], $dbConfig['pass']));
} catch (PDOException $e) {
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

// Remove old CORS handler since we handled it at the top
/*
Flight::before('start', function() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if (Flight::request()->method === 'OPTIONS') {
        Flight::json(null, 204);
        exit();
    }
});
*/


Flight::map('error', function(Throwable $error) {
  
    error_log($error->getMessage());
    
    
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


Flight::map('notFound', function() {
    Flight::json([
        'success' => false,
        'error' => 'Endpoint not found'
    ], 404);
});


$routeFiles = glob(__DIR__ . '/../routes/*.php');
foreach ($routeFiles as $file) {
    require_once $file;
    
    
    $className = '\\' . basename($file, '.php');
    
   
    if (class_exists($className)) {
        $routeInstance = new $className();
        if (method_exists($routeInstance, 'registerRoutes')) {
            $routeInstance->registerRoutes();
        }
    }
}


Flight::route('GET /api-docs', function() {
    $openapi = \OpenApi\Generator::scan([__DIR__ . '/../backend/routes']);
    header('Content-Type: application/json');
    echo $openapi->toJson();
});


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


Flight::route('GET /', function() {
    Flight::render('index.html');
});


Flight::start();
