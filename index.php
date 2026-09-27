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
use Shopnear\DynamicModules\AdController;

if (PHP_SAPI !== 'cli') {
    $router = new Router();

    // Web Routes
    $router->addRoute('GET', '/', function ($lang) {
        ViewController::renderHome($lang);
    });

    $router->addRoute('GET', '/product/{store_slug}/{product_slug}', function ($lang, $storeSlug, $productSlug) {
        ViewController::renderProductDetail($lang, $storeSlug, $productSlug);
    });

    $router->addRoute('GET', '/ad/click/{campaign_id}', function ($lang, $campaignId) {
        AdController::recordClick((int)$campaignId);
        $redirectUrl = $_GET['redirect'] ?? "/{$lang}/";
        header("Location: {$redirectUrl}");
        exit;
    });

    $router->addRoute('POST', '/checkout', function ($lang) {
        header('Content-Type: application/json');
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $customerId = (int)($_POST['customer_id'] ?? 1);

        if (!$productId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing product_id']);
            return;
        }

        $cart = [['product_id' => $productId, 'quantity' => $quantity]];
        $result = OrderController::createOrder($customerId, $cart, 'demo_card');
        echo json_encode($result);
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

    $router->addRoute('POST', '/api/v1/orders/checkout', function ($lang) {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $customerId = (int)($input['customer_id'] ?? 0);
        $cart = $input['cart'] ?? [];
        $paymentMethod = $input['payment_method'] ?? 'card';

        if (!$customerId || empty($cart)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid customer_id or empty cart']);
            return;
        }

        $result = OrderController::createOrder($customerId, $cart, $paymentMethod);
        echo json_encode($result);
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
