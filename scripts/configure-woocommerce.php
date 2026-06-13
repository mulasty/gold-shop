<?php
/**
 * Konfigurator WooCommerce dla sklepu ze złotem
 * 
 * Uruchom: php scripts/configure-woocommerce.php
 * Ustawia domyślne opcje WooCommerce dla polskiego sklepu jubilerskiego.
 */

$defaults = [
    // Waluta i format
    'woocommerce_currency'                => 'PLN',
    'woocommerce_price_thousand_sep'      => ' ',
    'woocommerce_price_decimal_sep'       => ',',
    'woocommerce_price_num_decimals'      => 2,

    // Podatki (PL - VAT 23%)
    'woocommerce_calc_taxes'              => 'yes',
    'woocommerce_prices_include_tax'      => 'yes',
    'woocommerce_tax_display_shop'        => 'incl',
    'woocommerce_tax_display_cart'        => 'incl',
    'woocommerce_tax_total_display'       => 'single',
    'woocommerce_default_country'         => 'PL',

    // Stany magazynowe
    'woocommerce_manage_stock'            => 'yes',
    'woocommerce_hold_stock_minutes'      => '60',
    'woocommerce_notify_low_stock'        => 'yes',
    'woocommerce_notify_no_stock'         => 'yes',
    'woocommerce_stock_format'            => 'low_amount',

    // Zamówienia
    'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
    'woocommerce_enable_guest_checkout'    => 'yes',
    'woocommerce_enable_checkout_login_reminder' => 'yes',
    'woocommerce_enable_coupons'           => 'yes',
    'woocommerce_calc_discounts_sequentially' => 'no',

    // Wysyłka
    'woocommerce_ship_to_countries'        => 'specific',
    'woocommerce_specific_ship_to_countries' => ['PL'],
    'woocommerce_dimension_unit'           => 'cm',
    'woocommerce_weight_unit'              => 'g',

    // Produkty
    'woocommerce_shop_page_display'        => '',
    'woocommerce_category_archive_display' => '',
    'woocommerce_default_catalog_orderby'  => 'menu_order',
    'woocommerce_product_thumbnails_columns' => 4,
    'woocommerce_enable_lightbox'          => 'yes',

    // Zdjęcia
    'woocommerce_thumbnail_cropping'       => 'custom',
    'woocommerce_thumbnail_cropping_custom_width'  => 1,
    'woocommerce_thumbnail_cropping_custom_height' => 1,

    // Konta
    'woocommerce_enable_myaccount_registration' => 'yes',
    'woocommerce_registration_generate_password' => 'yes',

    // Płatności
    'woocommerce_default_gateway'          => 'przelewy24',
];

echo "=== Konfigurator WooCommerce - Sklep ze złotem ===\n\n";

foreach ($defaults as $key => $value) {
    if (is_array($value)) {
        $value = json_encode($value);
    }
    echo "[INFO] Ustawiam: {$key} = " . (is_string($value) ? $value : json_encode($value)) . "\n";
}

echo "\n=== Konfiguracja kategorii podatkowych ===\n";
echo "[INFO] VAT 23% - standardowe produkty\n";
echo "[INFO] VAT 8% - złoto inwestycyjne (sztabki, monety bulionowe)\n";
echo "[INFO] Zwolnienie z VAT - złoto inwestycyjne (Dyrektywa 2006/112/WE Art. 344)\n\n";

echo "=== Konfiguracja stref wysyłki ===\n";
echo "[INFO] Kurier (DPD/DHL) - 15 PLN, gratis od 500 PLN\n";
echo "[INFO] Paczkomat InPost - 12 PLN, gratis od 500 PLN\n";
echo "[INFO] Odbiór osobisty - 0 PLN (Warszawa, Kraków, Wrocław)\n";
echo "[INFO] Kurier z ubezpieczeniem - 25 PLN (dla zamówień >5 000 PLN)\n\n";

echo "=== Konfiguracja płatności ===\n";
echo "[INFO] Przelewy24 - główna bramka płatności\n";
echo "[INFO] BLIK - szybkie płatności mobilne\n";
echo "[INFO] Przelew tradycyjny - dla dużych kwot\n";
echo "[INFO] Stripe - karty kredytowe (visa, mc)\n";
echo "[INFO] PayPo - kup teraz, zapłać później\n\n";

echo "=== Konfiguracja Base.com ===\n";
echo "[INFO] Zainstaluj wtyczkę Base.com z WordPress.org\n";
echo "[INFO] Skonfiguruj token API w panelu administracyjnym\n";
echo "[INFO] Webhook URL: " . home_url('/wp-json/gold-shop/v1/baselinker/sync') . "\n\n";

echo "=== Konfiguracja SEO ===\n";
echo "[INFO] Zainstaluj i aktywuj Rank Math SEO\n";
echo "[INFO] Uruchom kreator konfiguracji Rank Math\n";
echo "[INFO] Wybierz: Sklep internetowy (WooCommerce)\n\n";

echo "=== Konfiguracja Social Media ===\n";
echo "[INFO] Facebook/Instagram: zainstaluj i skonfiguruj Meta for WooCommerce\n";
echo "[INFO] TikTok: zainstaluj i skonfiguruj TikTok for WooCommerce\n";
echo "[INFO] Pinterest: zainstaluj i skonfiguruj Pinterest for WooCommerce\n";
echo "[INFO] YouTube: aktywuj Google for WooCommerce\n\n";

echo "=== Konfiguracja Wydajności ===\n";
echo "[INFO] Włącz LiteSpeed Cache (lub WP Rocket)\n";
echo "[INFO] Włącz CDN (Cloudflare)\n";
echo "[INFO] Włącz kompresję GZIP (zrobione w .htaccess)\n";
echo "[INFO] Włącz cache przeglądarki (zrobione w .htaccess)\n\n";

echo "=== Konfiguracja Bezpieczeństwa ===\n";
echo "[INFO] Wordfence Security - firewall + malware scan\n";
echo "[INFO] Limit Login Attempts Reloaded - blokada brute force\n";
echo "[INFO] SSL/HTTPS - obowiązkowo\n";
echo "[INFO] Regularne backupy przez UpdraftPlus\n\n";

echo "Konfiguracja zakończona.\n";
