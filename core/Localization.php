<?php
namespace Shopnear\Core;

class Localization
{
    private static string $defaultLang = 'en';
    private static array $supportedLangs = ['en', 'ml', 'hi'];

    private static array $dictionaries = [
        'en' => [
            'welcome' => 'Welcome to Shopnear',
            'tagline' => 'Hyperlocal multi-vendor e-commerce marketplace',
            'checkout' => 'Checkout',
            'order_summary' => 'Order Summary',
            'cart' => 'Cart',
            'total' => 'Total',
            'pay_now' => 'Pay Now',
            'sponsored' => 'Sponsored',
            'store' => 'Store',
            'buy_now' => 'Buy Now'
        ],
        'ml' => [
            'welcome' => 'ഷോപ്പ്നിയറിലേക്ക് സ്വാഗതം',
            'tagline' => 'ഹൈപ്പർലോക്കൽ മൾട്ടി-വെണ്ടർ ഇ-കൊമേഴ്സ് വിപണി',
            'checkout' => 'ചെക്ക്ഔട്ട്',
            'order_summary' => 'ഓർഡർ സംഗ്രഹം',
            'cart' => 'കാർട്ട്',
            'total' => 'ആകെ',
            'pay_now' => 'ഇപ്പോൾ പണം നൽകുക',
            'sponsored' => 'സ്‌പോൺസർ ചെയ്തത്',
            'store' => 'സ്റ്റോർ',
            'buy_now' => 'ഇപ്പോൾ വാങ്ങുക'
        ],
        'hi' => [
            'welcome' => 'शॉपनियर में आपका स्वागत है',
            'tagline' => 'हाइपरलोकल मल्टी-वेंडर ई-कॉमर्स मार्केटप्लेस',
            'checkout' => 'चेकआउट',
            'order_summary' => 'ऑर्डर सारांश',
            'cart' => 'कार्ट',
            'total' => 'कुल',
            'pay_now' => 'अभी भुगतान करें',
            'sponsored' => 'प्रायोजित',
            'store' => 'स्टोर',
            'buy_now' => 'अभी खरीदें'
        ]
    ];

    public static function getSupportedLanguages(): array
    {
        return self::$supportedLangs;
    }

    public static function get(string $key, string $lang = 'en'): string
    {
        if (!in_array($lang, self::$supportedLangs)) {
            $lang = self::$defaultLang;
        }

        return self::$dictionaries[$lang][$key] ?? self::$dictionaries[self::$defaultLang][$key] ?? $key;
    }
}
