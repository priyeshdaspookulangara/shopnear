<?php
namespace Shopnear\Core;

class SEO
{
    public static function renderProductTags(array $product, array $vendor, string $currentUrl = ''): string
    {
        $title = htmlspecialchars($product['title'] . " | " . $vendor['store_name'] . " - Shopnear");
        $desc = htmlspecialchars(mb_strimwidth($product['description'] ?? 'Shopnear hyperlocal marketplace', 0, 160, "..."));
        $image = htmlspecialchars($product['image_url'] ?? '/assets/default-product.png');
        $price = number_format((float)$product['price'], 2, '.', '');

        $jsonLd = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $product['title'],
            "image" => [$image],
            "description" => $product['description'] ?? '',
            "sku" => "PROD-" . $product['id'],
            "brand" => [
                "@type" => "Brand",
                "name" => $vendor['store_name']
            ],
            "offers" => [
                "@type" => "Offer",
                "url" => $currentUrl,
                "priceCurrency" => "USD",
                "price" => $price,
                "itemCondition" => "https://schema.org/NewCondition",
                "availability" => ($product['stock_quantity'] > 0) ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
                "seller" => [
                    "@type" => "Organization",
                    "name" => $vendor['store_name']
                ]
            ]
        ];

        $html = "<title>{$title}</title>\n";
        $html .= "<meta name=\"description\" content=\"{$desc}\">\n";
        $html .= "<meta property=\"og:title\" content=\"{$title}\">\n";
        $html .= "<meta property=\"og:description\" content=\"{$desc}\">\n";
        $html .= "<meta property=\"og:image\" content=\"{$image}\">\n";
        $html .= "<meta property=\"og:type\" content=\"product\">\n";
        $html .= "<meta property=\"og:url\" content=\"{$currentUrl}\">\n";
        $html .= '<script type="application/ld+json">' . json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</script>\n";

        return $html;
    }
}
