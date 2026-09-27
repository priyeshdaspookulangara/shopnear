<?php
namespace Shopnear\DynamicModules;

use Shopnear\Core\Database;
use Shopnear\Core\ApiAuth;

class ApiController
{
    public static function handleAuthLogin(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT u.*, v.id as vendor_id FROM users u LEFT JOIN vendors v ON u.id = v.user_id WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $tokenPayload = [
                'user_id' => $user['id'],
                'role' => $user['role'],
                'vendor_id' => $user['vendor_id']
            ];
            $token = ApiAuth::generateToken($tokenPayload);
            echo json_encode([
                'status' => 'success',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'vendor_id' => $user['vendor_id']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
        }
    }

    public static function handleGetProducts(): void
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT p.*, v.store_name, v.store_slug FROM products p JOIN vendors v ON p.vendor_id = v.id ORDER BY p.id DESC");
        echo json_encode(['status' => 'success', 'products' => $stmt->fetchAll()]);
    }

    public static function handleVendorOrders(): void
    {
        $token = ApiAuth::getBearerToken();
        $payload = $token ? ApiAuth::verifyToken($token) : null;

        if (!$payload || empty($payload['vendor_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized or missing vendor_id in token']);
            return;
        }

        $vendorId = (int)$payload['vendor_id'];
        $orders = OrderController::getVendorOrders($vendorId);
        echo json_encode(['status' => 'success', 'vendor_id' => $vendorId, 'orders' => $orders]);
    }
}
