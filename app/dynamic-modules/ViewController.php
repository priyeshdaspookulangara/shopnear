<?php
namespace Shopnear\DynamicModules;

use Shopnear\Core\Database;
use Shopnear\Core\SEO;
use Shopnear\Core\Localization;

class ViewController
{
    public static function renderHeader(string $title, string $lang = 'en', string $seoHtml = '', string $pageCss = ''): void
    {
        ?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $seoHtml ?: "<title>" . htmlspecialchars($title) . " - Shopnear</title>" ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            colors: {
              obsidian: '#0a0a0f',
              indigoGlass: '#13111C',
              accentIndigo: '#6366f1',
              neonCyan: '#06b6d4',
            }
          }
        }
      }
    </script>
    <style>
      body {
        background-color: #0a0a0f;
        color: #f3f4f6;
        font-family: system-ui, -apple-system, sans-serif;
      }
      <?= $pageCss ?>
    </style>
</head>
<body class="min-h-screen bg-obsidian text-gray-100 flex flex-col justify-between">
    <header class="sticky top-0 z-50 bg-indigoGlass/90 backdrop-blur-md px-6 py-4 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-accentIndigo to-neonCyan flex items-center justify-center font-bold text-white text-xl shadow-lg">
                S
            </div>
            <a href="/<?= $lang ?>/" class="text-2xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-gray-200 to-indigo-400">
                SHOPNEAR
            </a>
        </div>
        <nav class="flex items-center space-x-6 text-sm font-medium">
            <a href="/<?= $lang ?>/" class="hover:text-accentIndigo transition-colors"><?= Localization::get('welcome', $lang) ?></a>
            <a href="/<?= $lang ?>/cart" class="hover:text-accentIndigo transition-colors"><?= Localization::get('cart', $lang) ?></a>
            <div class="flex items-center space-x-2 bg-white/5 rounded-lg px-2 py-1 text-xs">
                <a href="/en/" class="<?= $lang === 'en' ? 'text-accentIndigo font-bold' : 'text-gray-400' ?>">EN</a> |
                <a href="/ml/" class="<?= $lang === 'ml' ? 'text-accentIndigo font-bold' : 'text-gray-400' ?>">ML</a> |
                <a href="/hi/" class="<?= $lang === 'hi' ? 'text-accentIndigo font-bold' : 'text-gray-400' ?>">HI</a>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-6 py-8 flex-1">
        <?php
    }

    public static function renderFooter(): void
    {
        ?>
    </main>
    <footer class="bg-indigoGlass/90 py-6 px-6 text-center text-xs text-gray-500">
        <p>&copy; <?= date('Y') ?> Shopnear Inc. Ultra-fast, Glassmorphic Multi-Vendor Hyperlocal Marketplace.</p>
    </footer>
</body>
</html>
        <?php
    }

    public static function renderHome(string $lang): void
    {
        $homeCss = "
            .home-glass-card {
                background: rgba(19, 17, 28, 0.7);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
            }
            .home-glass-glow {
                background: rgba(19, 17, 28, 0.8);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(99, 102, 241, 0.3);
                box-shadow: 0 0 20px rgba(99, 102, 241, 0.15);
            }
        ";
        self::renderHeader(Localization::get('welcome', $lang), $lang, '', $homeCss);
        $db = Database::getInstance();
        $products = $db->query("SELECT p.*, v.store_name, v.store_slug FROM products p JOIN vendors v ON p.vendor_id = v.id ORDER BY p.id DESC")->fetchAll();
        $sponsored = AdController::getActiveSponsoredProducts(3);
        ?>
        <div class="mb-8 p-8 rounded-2xl home-glass-card bg-gradient-to-r from-indigo-900/30 to-purple-900/30">
            <h1 class="text-4xl font-extrabold text-white mb-2"><?= Localization::get('welcome', $lang) ?></h1>
            <p class="text-gray-300 text-lg"><?= Localization::get('tagline', $lang) ?></p>
        </div>

        <?php if (!empty($sponsored)): ?>
            <section class="mb-10">
                <h2 class="text-xl font-bold mb-4 flex items-center space-x-2 text-indigo-300">
                    <span class="inline-block w-2 h-2 rounded-full bg-neonCyan"></span>
                    <span><?= Localization::get('sponsored', $lang) ?></span>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($sponsored as $ad): ?>
                        <?php $productUrl = "/{$lang}/product/{$ad['store_slug']}/{$ad['product_slug']}"; ?>
                        <div class="home-glass-glow rounded-xl p-5 flex flex-col justify-between">
                            <div>
                                <span class="bg-indigo-500/20 text-indigo-300 text-xs px-2 py-0.5 rounded border border-indigo-500/30 font-semibold mb-2 inline-block"><?= Localization::get('sponsored', $lang) ?></span>
                                <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($ad['title']) ?></h3>
                                <p class="text-xs text-indigo-400 mb-2"><?= Localization::get('store', $lang) ?>: <?= htmlspecialchars($ad['store_name']) ?></p>
                                <p class="text-xl font-extrabold text-neonCyan mb-4">$<?= number_format($ad['price'], 2) ?></p>
                            </div>
                            <a href="/<?= $lang ?>/ad/click/<?= $ad['campaign_id'] ?>?redirect=<?= urlencode($productUrl) ?>" class="block text-center w-full py-2 bg-gradient-to-r from-accentIndigo to-indigo-600 hover:opacity-90 rounded-lg text-white text-sm font-semibold shadow">
                                <?= Localization::get('buy_now', $lang) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section>
            <h2 class="text-2xl font-bold mb-6 text-white">Featured Products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($products as $p): ?>
                    <div class="home-glass-card rounded-xl p-5 flex flex-col justify-between hover:border-accentIndigo/50 transition duration-300">
                        <div>
                            <div class="h-40 rounded-lg bg-gray-800/50 mb-4 flex items-center justify-center">
                                <span class="text-4xl">🛍️</span>
                            </div>
                            <h3 class="font-bold text-lg text-white mb-1"><?= htmlspecialchars($p['title']) ?></h3>
                            <p class="text-xs text-gray-400 mb-2"><?= htmlspecialchars($p['store_name']) ?></p>
                            <p class="text-lg font-extrabold text-neonCyan mb-3">$<?= number_format($p['price'], 2) ?></p>
                        </div>
                        <a href="/<?= $lang ?>/product/<?= $p['store_slug'] ?>/<?= $p['product_slug'] ?>" class="block text-center py-2 bg-white/10 hover:bg-white/20 rounded-lg text-white text-sm font-medium transition">
                            View Details
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        self::renderFooter();
    }

    public static function renderProductDetail(string $lang, string $storeSlug, string $productSlug): void
    {
        $productCss = "
            .product-glass-card {
                background: rgba(19, 17, 28, 0.7);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
            }
        ";

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT p.*, v.store_name, v.store_slug FROM products p JOIN vendors v ON p.vendor_id = v.id WHERE v.store_slug = :sslug AND p.product_slug = :pslug");
        $stmt->execute(['sslug' => $storeSlug, 'pslug' => $productSlug]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(404);
            self::renderHeader("Product Not Found", $lang, '', $productCss);
            echo "<h1 class='text-2xl font-bold text-red-400'>Product Not Found</h1>";
            self::renderFooter();
            return;
        }

        $vStmt = $db->prepare("SELECT * FROM vendors WHERE store_slug = :sslug");
        $vStmt->execute(['sslug' => $storeSlug]);
        $vendor = $vStmt->fetch();

        $seoHtml = SEO::renderProductTags($product, $vendor, "https://shopnear.local/{$lang}/product/{$storeSlug}/{$productSlug}");

        self::renderHeader($product['title'], $lang, $seoHtml, $productCss);
        ?>
        <div class="max-w-4xl mx-auto product-glass-card rounded-2xl p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="h-72 rounded-xl bg-gray-800/60 flex items-center justify-center">
                <span class="text-6xl">🛍️</span>
            </div>
            <div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 inline-block mb-3">
                    <?= htmlspecialchars($vendor['store_name']) ?>
                </span>
                <h1 class="text-3xl font-extrabold text-white mb-2"><?= htmlspecialchars($product['title']) ?></h1>
                <p class="text-gray-400 text-sm mb-4"><?= htmlspecialchars($product['description'] ?? 'No description provided.') ?></p>
                <div class="text-3xl font-extrabold text-neonCyan mb-6">$<?= number_format($product['price'], 2) ?></div>

                <form action="/<?= $lang ?>/checkout" method="POST" class="space-y-4">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div class="flex items-center space-x-4">
                        <label class="text-sm text-gray-300 font-medium">Quantity:</label>
                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?>" class="w-20 bg-white/5 rounded-lg px-3 py-2 text-white font-semibold">
                    </div>
                    <button type="submit" class="w-full py-3 bg-gradient-to-r from-accentIndigo to-indigo-600 hover:opacity-90 rounded-xl font-bold text-white shadow-lg transition">
                        <?= Localization::get('checkout', $lang) ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
        self::renderFooter();
    }
}
