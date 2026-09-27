<?php
// Autoloader for Shopnear namespaces
spl_autoload_register(function ($class) {
    $prefixCore = 'Shopnear\\Core\\';
    $prefixModules = 'Shopnear\\DynamicModules\\';

    if (str_starts_with($class, $prefixCore)) {
        $relativeClass = substr($class, strlen($prefixCore));
        $file = __DIR__ . '/core/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    } elseif (str_starts_with($class, $prefixModules)) {
        $relativeClass = substr($class, strlen($prefixModules));
        $file = __DIR__ . '/app/dynamic-modules/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use Shopnear\Core\Router;
use Shopnear\DynamicModules\ViewController;
use Shopnear\DynamicModules\ApiController;
use Shopnear\DynamicModules\OrderController;
use Shopnear\DynamicModules\DemoPaymentController;

if (PHP_SAPI !== 'cli') {
    $router = new Router();

    // Web Routes
    $router->addRoute('GET', '/', function ($lang) {
        ViewController::renderHome($lang);
    });

    $router->addRoute('GET', '/product/{store_slug}/{product_slug}', function ($lang, $storeSlug, $productSlug) {
        ViewController::renderProductDetail($lang, $storeSlug, $productSlug);
    });

    // API Routes
    $router->addRoute('POST', '/api/v1/auth/login', function ($lang) {
        header('Content-Type: application/json');
        ApiController::handleAuthLogin();
    });

    $router->addRoute('GET', '/api/v1/products', function ($lang) {
        header('Content-Type: application/json');
        ApiController::handleGetProducts();
    });

    $router->addRoute('GET', '/api/v1/vendor/orders', function ($lang) {
        header('Content-Type: application/json');
        ApiController::handleVendorOrders();
    });

    $router->addRoute('POST', '/api/v1/payment/demo', function ($lang) {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        echo json_encode(DemoPaymentController::processPaymentSimulator($input));
    });

    // Dispatch request
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
}
