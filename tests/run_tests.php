<?php
// Test runner for Shopnear platform validation

require_once __DIR__ . '/../index.php';

use Shopnear\Core\Database;
use Shopnear\Core\ApiAuth;
use Shopnear\Core\SEO;
use Shopnear\Core\Localization;
use Shopnear\DynamicModules\OrderController;
use Shopnear\DynamicModules\DemoPaymentController;
use Shopnear\DynamicModules\LandingPageController;
use Shopnear\DynamicModules\AdController;

function assertCondition(bool $condition, string $message): void {
    if (!$condition) {
        echo "❌ TEST FAILED: {$message}\n";
        exit(1);
    }
    echo "✅ TEST PASSED: {$message}\n";
}

echo "=== STARTING SHOPNEAR SYSTEM TESTS ===\n\n";

// 1. Initialize DB and Seed Test Data safely with foreign key checks
$db = Database::getInstance();
$db->exec("PRAGMA foreign_keys = OFF;");
$db->exec("DELETE FROM ad_metrics; DELETE FROM ad_campaigns; DELETE FROM escrows; DELETE FROM order_items; DELETE FROM orders; DELETE FROM products; DELETE FROM vendors; DELETE FROM users; DELETE FROM landing_pages;");
$db->exec("PRAGMA foreign_keys = ON;");

// Seed User & Vendor 1
$db->exec("INSERT INTO users (name, email, password_hash, role) VALUES ('Vendor One', 'vendor1@shopnear.com', '" . password_hash('password123', PASSWORD_BCRYPT) . "', 'vendor')");
$v1UserId = (int)$db->lastInsertId();
$db->exec("INSERT INTO vendors (user_id, store_name, store_slug, commission_rate) VALUES ({$v1UserId}, 'Aroma Bakery', 'aroma-bakery', 0.15)");
$vendor1Id = (int)$db->lastInsertId();

// Seed User & Vendor 2
$db->exec("INSERT INTO users (name, email, password_hash, role) VALUES ('Vendor Two', 'vendor2@shopnear.com', '" . password_hash('password123', PASSWORD_BCRYPT) . "', 'vendor')");
$v2UserId = (int)$db->lastInsertId();
$db->exec("INSERT INTO vendors (user_id, store_name, store_slug, commission_rate) VALUES ({$v2UserId}, 'Fresh Organics', 'fresh-organics', 0.10)");
$vendor2Id = (int)$db->lastInsertId();

// Seed Customer
$db->exec("INSERT INTO users (name, email, password_hash, role) VALUES ('Customer Alice', 'alice@shopnear.com', '" . password_hash('password123', PASSWORD_BCRYPT) . "', 'customer')");
$customerId = (int)$db->lastInsertId();

// Seed Products
$db->exec("INSERT INTO products (vendor_id, title, product_slug, description, price, stock_quantity) VALUES ({$vendor1Id}, 'Artisan Sourdough', 'artisan-sourdough', 'Freshly baked sourdough loaf', 10.00, 50)");
$p1Id = (int)$db->lastInsertId();

$db->exec("INSERT INTO products (vendor_id, title, product_slug, description, price, stock_quantity) VALUES ({$vendor2Id}, 'Organic Honey', 'organic-honey', 'Pure raw organic honey 500g', 20.00, 30)");
$p2Id = (int)$db->lastInsertId();

assertCondition($vendor1Id > 0 && $vendor2Id > 0, "Vendors seeded successfully");
assertCondition($p1Id > 0 && $p2Id > 0, "Products seeded successfully");

// 2. Test JWT Generation & Tenant Isolation Verification
$tokenPayload = ['user_id' => $v1UserId, 'role' => 'vendor', 'vendor_id' => $vendor1Id];
$jwt = ApiAuth::generateToken($tokenPayload);
$verified = ApiAuth::verifyToken($jwt);
assertCondition($verified !== null && $verified['vendor_id'] === $vendor1Id, "JWT Token generated and verified successfully");

// 3. Test Multi-Vendor Checkout & Atomic Split-Payment Escrow Settlement
$cart = [
    ['product_id' => $p1Id, 'quantity' => 2], // $20.00 (Vendor 1, 15% comm = $3.00, payout = $17.00)
    ['product_id' => $p2Id, 'quantity' => 1]  // $20.00 (Vendor 2, 10% comm = $2.00, payout = $18.00)
];

$orderResult = OrderController::createOrder($customerId, $cart, 'card');
assertCondition($orderResult['success'] === true, "Multi-vendor checkout completed successfully");
assertCondition($orderResult['total_amount'] === 40.00, "Order total amount calculation accurate ($40.00)");

$escrowRows = $db->query("SELECT * FROM escrows WHERE order_id = {$orderResult['order_id']} ORDER BY vendor_id ASC")->fetchAll();
assertCondition(count($escrowRows) === 2, "Escrow split entries created for both vendors");

$v1Escrow = array_values(array_filter($escrowRows, fn($e) => (int)$e['vendor_id'] === $vendor1Id))[0];
assertCondition($v1Escrow['gross_amount'] == 20.00 && $v1Escrow['platform_commission'] == 3.00 && $v1Escrow['net_vendor_payout'] == 17.00, "Vendor 1 escrow split calculation accurate ($20.00 gross -> $3.00 comm / $17.00 net)");

$v2Escrow = array_values(array_filter($escrowRows, fn($e) => (int)$e['vendor_id'] === $vendor2Id))[0];
assertCondition($v2Escrow['gross_amount'] == 20.00 && $v2Escrow['platform_commission'] == 2.00 && $v2Escrow['net_vendor_payout'] == 18.00, "Vendor 2 escrow split calculation accurate ($20.00 gross -> $2.00 comm / $18.00 net)");

// 4. Test Demo Payment Gateway Simulator
$paySuccess = DemoPaymentController::processPaymentSimulator(['card_number' => '4000000000003100', 'amount' => 40.00]);
assertCondition($paySuccess['status'] === 'APPROVED' || $paySuccess['code'] === 'APPROVED', "Demo payment simulator approved standard test card");

$payFailure = DemoPaymentController::processPaymentSimulator(['card_number' => '4000000000000000', 'amount' => 40.00]);
assertCondition($payFailure['status'] === 'FAILED' || $payFailure['code'] === 'CARD_DECLINED', "Demo payment simulator declined test failure card ending in 0000");

// 5. Test Localization Engine
assertCondition(Localization::get('welcome', 'ml') === 'ഷോപ്പ്നിയറിലേക്ക് സ്വാഗതം', "Malayalam localization lookup verified");
assertCondition(Localization::get('welcome', 'hi') === 'शॉपनियर में आपका स्वागत है', "Hindi localization lookup verified");

// 6. Test Ad Campaigns and Click Metrics
$db->exec("INSERT INTO ad_campaigns (vendor_id, product_id, campaign_name, type, budget, spent, bid_amount, status) VALUES ({$vendor1Id}, {$p1Id}, 'Sourdough Promo', 'CPC', 10.00, 0.0, 1.00, 'active')");
$campaignId = (int)$db->lastInsertId();

$ads = AdController::getActiveSponsoredProducts(2);
assertCondition(count($ads) === 1 && (int)$ads[0]['campaign_id'] === $campaignId, "Active sponsored product retrieved");

AdController::recordClick($campaignId);
$metricStmt = $db->prepare("SELECT * FROM ad_metrics WHERE campaign_id = :cid");
$metricStmt->execute(['cid' => $campaignId]);
$metric = $metricStmt->fetch();
assertCondition((int)$metric['clicks'] === 1, "Ad click recorded in metrics table");

// 7. Test CMS Landing Page Builder
$landingId = LandingPageController::createOrUpdatePage([
    'slug' => 'summer-sale',
    'title' => 'Summer Flash Sale',
    'hero_heading' => 'Up to 50% Off Hyperlocal Fresh Products',
    'status' => 'published'
]);
$page = LandingPageController::getPage('summer-sale');
assertCondition($page !== null && $page['title'] === 'Summer Flash Sale', "Headless CMS landing page created and fetched");

echo "\nALL TEST SUITES PASSED SUCCESSFULLY! 🎉\n";
