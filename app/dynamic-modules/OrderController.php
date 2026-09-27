<?php
namespace Shopnear\DynamicModules;

use Shopnear\Core\Database;
use PDO;
use Exception;

class OrderController
{
    /**
     * Process checkout and create atomic split-payment escrow ledger entries
     */
    public static function createOrder(int $customerId, array $cartItems, string $paymentMethod): array
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $totalAmount = 0;
            $vendorItemsMap = [];

            // 1. Verify stock and calculate totals grouped by vendor
            foreach ($cartItems as $item) {
                $productId = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];

                $stmt = $db->prepare("SELECT p.*, v.commission_rate FROM products p JOIN vendors v ON p.vendor_id = v.id WHERE p.id = :id");
                $stmt->execute(['id' => $productId]);
                $product = $stmt->fetch();

                if (!$product) {
                    throw new Exception("Product ID {$productId} not found.");
                }

                if ($product['stock_quantity'] < $quantity) {
                    throw new Exception("Insufficient stock for product '{$product['title']}'. Requested: {$quantity}, Available: {$product['stock_quantity']}");
                }

                // Deduct stock
                $updateStock = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :id");
                $updateStock->execute(['qty' => $quantity, 'id' => $productId]);

                $lineTotal = $product['price'] * $quantity;
                $totalAmount += $lineTotal;

                $vendorId = (int)$product['vendor_id'];
                if (!isset($vendorItemsMap[$vendorId])) {
                    $vendorItemsMap[$vendorId] = [
                        'gross' => 0.0,
                        'commission_rate' => (float)$product['commission_rate'],
                        'items' => []
                    ];
                }

                $vendorItemsMap[$vendorId]['gross'] += $lineTotal;
                $vendorItemsMap[$vendorId]['items'][] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => (float)$product['price'],
                    'line_total' => $lineTotal
                ];
            }

            // 2. Create Master Order
            $orderStmt = $db->prepare("INSERT INTO orders (customer_id, total_amount, payment_method, status) VALUES (:cust, :total, :method, 'completed')");
            $orderStmt->execute([
                'cust' => $customerId,
                'total' => $totalAmount,
                'method' => $paymentMethod
            ]);
            $orderId = (int)$db->lastInsertId();

            // 3. Create Order Items & Split-Payment Escrow Records
            $escrows = [];
            foreach ($vendorItemsMap as $vendorId => $vendorData) {
                foreach ($vendorData['items'] as $item) {
                    $itemStmt = $db->prepare("INSERT INTO order_items (order_id, vendor_id, product_id, quantity, unit_price, total_price) VALUES (:oid, :vid, :pid, :qty, :uprice, :tprice)");
                    $itemStmt->execute([
                        'oid' => $orderId,
                        'vid' => $vendorId,
                        'pid' => $item['product_id'],
                        'qty' => $item['quantity'],
                        'uprice' => $item['unit_price'],
                        'tprice' => $item['line_total']
                    ]);
                }

                // Atomic split calculation
                $gross = $vendorData['gross'];
                $commissionRate = $vendorData['commission_rate'];
                $platformCommission = round($gross * $commissionRate, 2);
                $netVendorPayout = round($gross - $platformCommission, 2);

                $escrowStmt = $db->prepare("INSERT INTO escrows (order_id, vendor_id, gross_amount, platform_commission, net_vendor_payout, status) VALUES (:oid, :vid, :gross, :comm, :payout, 'locked')");
                $escrowStmt->execute([
                    'oid' => $orderId,
                    'vid' => $vendorId,
                    'gross' => $gross,
                    'comm' => $platformCommission,
                    'payout' => $netVendorPayout
                ]);

                $escrows[] = [
                    'vendor_id' => $vendorId,
                    'gross_amount' => $gross,
                    'platform_commission' => $platformCommission,
                    'net_vendor_payout' => $netVendorPayout,
                    'status' => 'locked'
                ];
            }

            $db->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'total_amount' => $totalAmount,
                'escrow_ledger' => $escrows
            ];

        } catch (Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function getVendorOrders(int $authVendorId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT oi.*, o.created_at, o.payment_method, p.title as product_title, e.platform_commission, e.net_vendor_payout, e.status as escrow_status
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN escrows e ON e.order_id = oi.order_id AND e.vendor_id = oi.vendor_id
            WHERE oi.vendor_id = :auth_vendor_id
            ORDER BY oi.id DESC
        ");
        $stmt->execute(['auth_vendor_id' => $authVendorId]);
        return $stmt->fetchAll();
    }
}
