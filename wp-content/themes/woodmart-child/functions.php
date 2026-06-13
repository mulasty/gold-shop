<?php
/**
 * WoodMart Child Theme - Sklep ze złotem
 * 
 * Funkcje: Base Linker, social media pixele, dynamiczne ceny złota,
 * WooCommerce customization, wydajność, SEO
 */

add_action('wp_enqueue_scripts', 'gold_shop_enqueue_scripts', 99);
function gold_shop_enqueue_scripts() {
    wp_enqueue_style('woodmart-child',
        get_stylesheet_directory_uri() . '/style.css',
        ['woodmart-style'],
        wp_get_theme()->get('Version')
    );
}

/* =============================================
 * 1. DYNAMICZNE CENY ZŁOTA - API NBP / London Fix
 * ============================================= */

add_action('init', 'gold_register_metal_prices_cron');
function gold_register_metal_prices_cron() {
    if (!wp_next_scheduled('gold_update_prices_hourly')) {
        wp_schedule_event(time(), 'hourly', 'gold_update_prices_hourly');
    }
}

add_action('gold_update_prices_hourly', 'gold_fetch_and_update_prices');
function gold_fetch_and_update_prices() {
    $api_source = get_option('gold_price_api_source', 'nbp');
    $price_data = [];

    if ($api_source === 'nbp') {
        $response = wp_remote_get(
            'https://api.nbp.pl/api/cenyzlota/last/1?format=json',
            ['timeout' => 15]
        );
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($body[0]['cena'])) {
                $price_data['gold_pln_g'] = $body[0]['cena'] / 1000;
                $price_data['gold_pln_oz'] = $body[0]['cena'] * 31.1035;
                $price_data['gold_pln_kg'] = $body[0]['cena'] * 1000;
                $price_data['last_update'] = current_time('mysql');
                $price_data['source'] = 'NBP';
            }
        }
    } elseif ($api_source === 'goldapi') {
        $api_key = get_option('gold_api_key', '');
        if ($api_key) {
            $response = wp_remote_get(
                'https://www.goldapi.io/api/XAU/PLN',
                [
                    'timeout' => 15,
                    'headers' => [
                        'x-access-token' => $api_key,
                        'Content-Type' => 'application/json',
                    ],
                ]
            );
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['price'])) {
                    $price_data['gold_pln_oz'] = $body['price'];
                    $price_data['gold_pln_g'] = $body['price'] / 31.1035;
                    $price_data['gold_pln_kg'] = $body['price'] * 32.1507;
                    $price_data['last_update'] = current_time('mysql');
                    $price_data['source'] = 'GoldAPI';
                    $price_data['change'] = $body['ch'] ?? 0;
                    $price_data['change_percent'] = $body['chp'] ?? 0;
                }
            }
        }
    }

    if (!empty($price_data)) {
        update_option('gold_current_prices', $price_data);
        do_action('gold_prices_updated', $price_data);
    }
}

add_shortcode('gold_price', 'gold_price_shortcode');
function gold_price_shortcode($atts) {
    $atts = shortcode_atts([
        'unit' => 'g',
        'label' => 'Złoto',
        'purity' => '585',
        'format' => true,
    ], $atts);

    $prices = get_option('gold_current_prices', []);
    if (empty($prices)) {
        return '<span class="gold-price-loading">Ładowanie ceny...</span>';
    }

    $base_price = $prices['gold_pln_g'] ?? 0;
    $purity_factors = ['375' => 0.375, '585' => 0.585, '750' => 0.750, '916' => 0.916, '999' => 0.999];
    $factor = $purity_factors[$atts['purity']] ?? 0.585;

    $unit_multipliers = ['g' => 1, 'oz' => 31.1035, 'kg' => 1000];
    $multiplier = $unit_multipliers[$atts['unit']] ?? 1;

    $price = $base_price * $factor * $multiplier;

    return sprintf(
        '<span class="gold-price" data-purity="%s" data-unit="%s" data-price="%s">%s %s</span>',
        esc_attr($atts['purity']),
        esc_attr($atts['unit']),
        esc_attr($price),
        $atts['format'] ? number_format($price, 2, ',', ' ') . ' PLN' : $price,
        esc_html($atts['label'])
    );
}

add_action('rest_api_init', function () {
    register_rest_route('gold-shop/v1', '/prices', [
        'methods' => 'GET',
        'callback' => function () {
            $prices = get_option('gold_current_prices', []);
            if (empty($prices)) {
                return new WP_Error('no_data', 'Brak danych cenowych', ['status' => 503]);
            }
            return rest_ensure_response($prices);
        },
        'permission_callback' => '__return_true',
    ]);
});

/* =============================================
 * 2. BASE LINKER (Base.com) INTEGRACJA
 * ============================================= */

add_filter('woocommerce_integrations', 'gold_add_base_linker_integration');
function gold_add_base_linker_integration($integrations) {
    if (class_exists('WC_Integration')) {
        require_once get_stylesheet_directory() . '/inc/class-base-linker-integration.php';
        $integrations[] = 'Gold_Base_Linker_Integration';
    }
    return $integrations;
}

add_action('rest_api_init', function () {
    register_rest_route('gold-shop/v1', '/baselinker/sync', [
        'methods' => 'POST',
        'callback' => 'gold_handle_baselinker_webhook',
        'permission_callback' => function () {
            $token = get_option('gold_baselinker_webhook_token', '');
            if (!$token) return false;
            $received = '';
            if (isset($_SERVER['HTTP_X_BASELINKER_TOKEN'])) {
                $received = $_SERVER['HTTP_X_BASELINKER_TOKEN'];
            }
            return hash_equals($token, $received);
        },
    ]);
});

function gold_handle_baselinker_webhook($request) {
    $body = $request->get_json_params();
    $event = $body['event'] ?? '';
    $data = $body['data'] ?? [];

    do_action('gold_baselinker_event', $event, $data);

    return rest_ensure_response(['status' => 'ok', 'received' => $event]);
}

/* =============================================
 * 3. META (FACEBOOK / INSTAGRAM) PIXEL
 * ============================================= */

add_action('wp_head', 'gold_meta_pixel_output', 1);
function gold_meta_pixel_output() {
    $pixel_id = get_option('gold_facebook_pixel_id', '');
    if (!$pixel_id) return;
    ?>
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}
(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo esc_js($pixel_id); ?>');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo esc_attr($pixel_id); ?>&ev=PageView&noscript=1"/></noscript>
<?php
}

if (!wp_doing_ajax()) {
    add_action('wp_footer', 'gold_meta_pixel_track_product', 20);
}
function gold_meta_pixel_track_product() {
    if (!is_product()) return;
    global $product;
    if (!$product) return;
    ?>
<script>
fbq('track', 'ViewContent', {
    content_type: 'product',
    content_ids: ['<?php echo esc_js($product->get_id()); ?>'],
    content_name: '<?php echo esc_js($product->get_name()); ?>',
    currency: 'PLN',
    value: <?php echo esc_js($product->get_price()); ?>
});
</script>
<?php
}

add_action('woocommerce_add_to_cart', 'gold_meta_pixel_track_add_to_cart', 10, 4);
function gold_meta_pixel_track_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id) {
    $product = wc_get_product($product_id);
    if (!$product) return;
    wc_enqueue_js("
        fbq('track', 'AddToCart', {
            content_type: 'product',
            content_ids: ['{$product_id}'],
            content_name: '" . esc_js($product->get_name()) . "',
            currency: 'PLN',
            value: " . ($product->get_price() * $quantity) . "
        });
    ");
}

add_action('woocommerce_thankyou', 'gold_meta_pixel_track_purchase', 10, 1);
function gold_meta_pixel_track_purchase($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    ?>
<script>
fbq('track', 'Purchase', {
    content_type: 'product',
    currency: 'PLN',
    value: <?php echo esc_js($order->get_total()); ?>,
    content_ids: [<?php
        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = "'" . esc_js($item->get_product_id()) . "'";
        }
        echo implode(',', $items);
    ?>]
});
</script>
<?php
}

/* =============================================
 * 4. TIKTOK PIXEL
 * ============================================= */

add_action('wp_head', 'gold_tiktok_pixel_output', 2);
function gold_tiktok_pixel_output() {
    $pixel_id = get_option('gold_tiktok_pixel_id', '');
    if (!$pixel_id) return;
    ?>
<script>
!function(w,d,e){var s,n,i;s=d.createElement(e);s.async=!0;s.src="https://analytics.tiktok.com/i18n/pixel/events.js";
n=d.getElementsByTagName(e)[0];n.parentNode.insertBefore(s,n);w.ttq=w.ttq||[];w.ttq.push(['init','<?php echo esc_js($pixel_id); ?>']);w.ttq.push(['track','PageView']);}
(window,document,'script');
</script>
<?php
}

/* =============================================
 * 5. PINTEREST TAG
 * ============================================= */

add_action('wp_head', 'gold_pinterest_tag_output', 3);
function gold_pinterest_tag_output() {
    $tag_id = get_option('gold_pinterest_tag_id', '');
    if (!$tag_id) return;
    ?>
<script>
!function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var n=window.pintrk;n.queue=[],n.version="3.0";var t=document.createElement("script");t.async=!0,t.src=e;var r=document.getElementsByTagName("script")[0];r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");
pintrk('load','<?php echo esc_js($tag_id); ?>', {np: "gtm"});
pintrk('page');
</script>
<noscript><img height="1" width="1" style="display:none" src="https://ct.pinterest.com/v3/?tid=<?php echo esc_attr($tag_id); ?>&noscript=1"/></noscript>
<?php
}

/* =============================================
 * 6. WOOCOMMERCE KONFIGURACJA
 * ============================================= */

add_action('after_setup_theme', 'gold_woocommerce_setup');
function gold_woocommerce_setup() {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}

add_filter('woocommerce_currency', 'gold_set_currency');
function gold_set_currency($currency) {
    return 'PLN';
}

add_filter('woocommerce_price_format', 'gold_price_format');
function gold_price_format($format) {
    return '%2$s&nbsp;%1$s';
}

add_filter('woocommerce_currencies', 'gold_add_currencies');
function gold_add_currencies($currencies) {
    $currencies['XAU'] = __('Złoto (XAU)', 'gold-shop');
    return $currencies;
}

add_filter('woocommerce_price_thousand_sep', function () {
    return ' ';
});

add_filter('woocommerce_price_decimal_sep', function () {
    return ',';
});

function gold_get_purity_karat($purity) {
    $map = ['375' => '9K', '585' => '14K', '750' => '18K', '916' => '22K', '999' => '24K'];
    return $map[$purity] ?? $purity;
}

add_filter('woocommerce_get_price_html', 'gold_custom_price_html', 10, 2);
function gold_custom_price_html($price, $product) {
    $gold_enabled = get_post_meta($product->get_id(), '_gold_product', true);
    if ($gold_enabled !== 'yes') return $price;

    $prices = get_option('gold_current_prices', []);
    $last_update = $prices['last_update'] ?? '';
    $base_price = $prices['gold_pln_g'] ?? 0;

    $weight = (float) get_post_meta($product->get_id(), '_gold_weight_g', true);
    $purity = get_post_meta($product->get_id(), '_gold_purity', true) ?: '585';
    $purity_factors = ['375' => 0.375, '585' => 0.585, '750' => 0.750, '916' => 0.916, '999' => 0.999];
    $factor = $purity_factors[$purity] ?? 0.585;
    $premium = (float) get_post_meta($product->get_id(), '_gold_premium_percent', true) ?: 5;

    if ($base_price > 0 && $weight > 0) {
        $calculated = $base_price * $factor * $weight * (1 + $premium / 100);
        $calculated = round($calculated);

        $price = sprintf(
            '<span class="gold-price-calculated" data-last-update="%s">%s</span>',
            esc_attr($last_update),
            wc_price($calculated)
        );

        if ($last_update) {
            $price .= sprintf(
                '<small class="gold-price-note" style="display:block;font-size:0.75em;opacity:0.7;">Cena oparta na notowaniach złota z %s</small>',
                esc_html($last_update)
            );
        }
    }

    return $price;
}

add_action('woocommerce_product_options_general_product_data', 'gold_add_product_fields');
function gold_add_product_fields() {
    global $post;
    echo '<div class="options_group show_if_simple show_if_variable">';
    woocommerce_wp_checkbox([
        'id' => '_gold_product',
        'label' => __('Produkt złoty', 'gold-shop'),
        'description' => __('Włącz dynamiczną wycenę na podstawie notowań złota', 'gold-shop'),
    ]);
    woocommerce_wp_text_input([
        'id' => '_gold_weight_g',
        'label' => __('Waga (gramy)', 'gold-shop'),
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
    ]);
    woocommerce_wp_select([
        'id' => '_gold_purity',
        'label' => __('Próba złota', 'gold-shop'),
        'options' => [
            '375' => '375 (9K)',
            '585' => '585 (14K)',
            '750' => '750 (18K)',
            '916' => '916 (22K)',
            '999' => '999 (24K)',
        ],
    ]);
    woocommerce_wp_text_input([
        'id' => '_gold_premium_percent',
        'label' => __('Premia (%)', 'gold-shop'),
        'description' => __('Marża ponad cenę rynkową złota', 'gold-shop'),
        'type' => 'number',
        'custom_attributes' => ['step' => '0.1', 'min' => '0'],
    ]);
    echo '</div>';
}

add_action('woocommerce_process_product_meta', 'gold_save_product_fields');
function gold_save_product_fields($product_id) {
    $fields = ['_gold_product', '_gold_weight_g', '_gold_purity', '_gold_premium_percent'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($product_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}

/* =============================================
 * 7. WYSYŁKA I PŁATNOŚCI
 * ============================================= */

add_filter('woocommerce_states', 'gold_add_polish_states');
function gold_add_polish_states($states) {
    $states['PL'] = [
        'DS' => 'Dolnośląskie',
        'KP' => 'Kujawsko-Pomorskie',
        'LU' => 'Lubelskie',
        'LB' => 'Lubuskie',
        'LD' => 'Łódzkie',
        'MA' => 'Małopolskie',
        'MZ' => 'Mazowieckie',
        'OP' => 'Opolskie',
        'PK' => 'Podkarpackie',
        'PD' => 'Podlaskie',
        'PM' => 'Pomorskie',
        'SL' => 'Śląskie',
        'SK' => 'Świętokrzyskie',
        'WN' => 'Warmińsko-Mazurskie',
        'WP' => 'Wielkopolskie',
        'ZP' => 'Zachodniopomorskie',
    ];
    return $states;
}

add_filter('woocommerce_default_address_fields', 'gold_customize_checkout_fields');
function gold_customize_checkout_fields($fields) {
    $fields['address_1']['priority'] = 10;
    $fields['address_2']['priority'] = 20;
    $fields['city']['priority'] = 30;
    $fields['postcode']['priority'] = 40;
    $fields['state']['priority'] = 50;
    return $fields;
}

/* =============================================
 * 8. SEO - RANK MATH UZUPEŁNIENIA
 * ============================================= */

add_filter('rank_math/snippet/rich_snippet/product_entity', 'gold_rank_math_product_schema', 10, 2);
function gold_rank_math_product_schema($entity, $product) {
    $gold_product = get_post_meta($product->get_id(), '_gold_product', true);
    if ($gold_product === 'yes') {
        $purity = get_post_meta($product->get_id(), '_gold_purity', true);
        $weight = get_post_meta($product->get_id(), '_gold_weight_g', true);

        $entity['material'] = sprintf('Złoto próby %s', $purity);
        if ($weight) {
            $entity['weight'] = [
                '@type' => 'QuantitativeValue',
                'value' => $weight,
                'unitCode' => 'GRM',
            ];
        }
        $entity['category'] = 'Złoto Inwestycyjne';
    }
    return $entity;
}

/* =============================================
 * 9. WYDAJNOŚĆ
 * ============================================= */

add_action('init', 'gold_optimization_headers');
function gold_optimization_headers() {
    if (!is_admin() && !is_user_logged_in()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

/* =============================================
 * 10. ADMIN DASHBOARD - WIDGET Z CENAMI ZŁOTA
 * ============================================= */

add_action('wp_dashboard_setup', 'gold_add_dashboard_widget');
function gold_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'gold_price_dashboard',
        'Notowania złota',
        'gold_dashboard_widget_render'
    );
}

function gold_dashboard_widget_render() {
    $prices = get_option('gold_current_prices', []);
    if (empty($prices)) {
        echo '<p>Brak danych. Ceny zostaną pobrane przy najbliższym cronie.</p>';
        return;
    }
    echo '<table style="width:100%;border-collapse:collapse;">';
    $rows = [
        'Za gram (PLN)' => $prices['gold_pln_g'] ?? 0,
        'Za uncję (PLN)' => $prices['gold_pln_oz'] ?? 0,
        'Za kg (PLN)' => $prices['gold_pln_kg'] ?? 0,
        'Źródło' => $prices['source'] ?? '-',
        'Ostatnia aktualizacja' => $prices['last_update'] ?? '-',
    ];
    foreach ($rows as $label => $value) {
        $display = is_numeric($value) ? number_format((float)$value, 2, ',', ' ') . ' PLN' : $value;
        echo sprintf(
            '<tr style="border-bottom:1px solid #eee;"><td style="padding:6px 0;font-weight:600;">%s</td><td style="padding:6px 0;text-align:right;">%s</td></tr>',
            esc_html($label),
            esc_html($display)
        );
    }
    echo '</table>';
}

/* =============================================
 * 11. KALKULATOR WYKUPU ZŁOTA
 * ============================================= */

add_shortcode('gold_buyback_calculator', 'gold_buyback_calculator_shortcode');
function gold_buyback_calculator_shortcode() {
    $prices = get_option('gold_current_prices', []);
    $base_price = $prices['gold_pln_g'] ?? 0;

    ob_start();
    ?>
<div class="gold-buyback-calculator" style="background:#1a1a2e;color:#fff;padding:30px;border-radius:12px;max-width:500px;margin:20px 0;font-family:Arial,sans-serif;">
    <h3 style="color:#d4af37;margin-top:0;font-size:1.3em;">Kalkulator wykupu złota</h3>
    <p style="opacity:0.8;font-size:0.9em;">Sprawdź ile możesz dostać za swoje złoto</p>
    <div style="margin:15px 0;">
        <label style="display:block;margin-bottom:5px;font-size:0.9em;">Próba złota</label>
        <select id="buyback-purity" style="width:100%;padding:10px;border-radius:6px;border:1px solid #d4af37;background:#16213e;color:#fff;font-size:1em;">
            <option value="375">375 (9K)</option>
            <option value="585" selected>585 (14K)</option>
            <option value="750">750 (18K)</option>
            <option value="916">916 (22K)</option>
            <option value="999">999 (24K)</option>
        </select>
    </div>
    <div style="margin:15px 0;">
        <label style="display:block;margin-bottom:5px;font-size:0.9em;">Waga (gramy)</label>
        <input type="number" id="buyback-weight" value="10" min="0.1" step="0.1" style="width:100%;padding:10px;border-radius:6px;border:1px solid #d4af37;background:#16213e;color:#fff;font-size:1em;">
    </div>
    <div style="margin:20px 0;padding:15px;background:#16213e;border-radius:8px;text-align:center;">
        <div style="opacity:0.7;font-size:0.85em;">Szacunkowa wartość wykupu</div>
        <div id="buyback-result" style="font-size:1.8em;font-weight:700;color:#d4af37;margin-top:5px;">
            <?php echo $base_price ? number_format($base_price * 0.585 * 10 * 0.92, 2, ',', ' ') . ' PLN' : '---'; ?>
        </div>
        <div style="font-size:0.75em;opacity:0.5;margin-top:5px;">Ceny aktualne na: <?php echo esc_html($prices['last_update'] ?? '---'); ?></div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var purityEl = document.getElementById('buyback-purity');
        var weightEl = document.getElementById('buyback-weight');
        var resultEl = document.getElementById('buyback-result');
        var basePrice = <?php echo $base_price ?: 0; ?>;
        function calculate() {
            var purity = parseFloat(purityEl.value) / 1000;
            var weight = parseFloat(weightEl.value) || 0;
            var value = basePrice * purity * weight * 0.92;
            resultEl.textContent = value.toLocaleString('pl-PL', {minimumFractionDigits:2,maximumFractionDigits:2}) + ' PLN';
        }
        purityEl.addEventListener('change', calculate);
        weightEl.addEventListener('input', calculate);
    });
    </script>
</div>
<?php
    return ob_get_clean();
}

/* =============================================
 * 12. KONFIGUROWALNE OPCJE (Admin Settings)
 * ============================================= */

add_action('admin_menu', 'gold_add_admin_menu');
function gold_add_admin_menu() {
    add_menu_page(
        'Konfiguracja Złota',
        'Złoto',
        'manage_options',
        'gold-settings',
        'gold_settings_page',
        'dashicons-money-alt',
        55
    );
}

add_action('admin_init', 'gold_register_settings');
function gold_register_settings() {
    $settings = [
        'gold_price_api_source' => ['type' => 'string', 'default' => 'nbp'],
        'gold_api_key' => ['type' => 'string', 'default' => ''],
        'gold_facebook_pixel_id' => ['type' => 'string', 'default' => ''],
        'gold_tiktok_pixel_id' => ['type' => 'string', 'default' => ''],
        'gold_pinterest_tag_id' => ['type' => 'string', 'default' => ''],
        'gold_baselinker_webhook_token' => ['type' => 'string', 'default' => ''],
        'gold_gtm_container_id' => ['type' => 'string', 'default' => ''],
    ];
    foreach ($settings as $key => $args) {
        register_setting('gold_settings_group', $key, $args);
    }
}

function gold_settings_page() {
    ?>
<div class="wrap">
    <h1>Konfiguracja Sklepu ze Złotem</h1>
    <form method="post" action="options.php">
        <?php settings_fields('gold_settings_group'); do_settings_sections('gold_settings_group'); ?>

        <h2 class="title" style="margin-top:30px;">Źródło cen złota</h2>
        <table class="form-table">
            <tr><th scope="row">API źródło</th>
                <td><select name="gold_price_api_source">
                    <option value="nbp" <?php selected(get_option('gold_price_api_source'), 'nbp'); ?>>NBP (darmowe)</option>
                    <option value="goldapi" <?php selected(get_option('gold_price_api_source'), 'goldapi'); ?>>GoldAPI.io (klucz API)</option>
                </select>
                <p class="description">NBP - darmowe, aktualizowane raz dziennie. GoldAPI - płatne, live pricing.</p></td></tr>
            <tr><th scope="row">Klucz GoldAPI.io</th>
                <td><input type="text" name="gold_api_key" value="<?php echo esc_attr(get_option('gold_api_key')); ?>" class="regular-text"></td></tr>
        </table>

        <h2 class="title" style="margin-top:30px;">Social Media - Pixele śledzące</h2>
        <table class="form-table">
            <tr><th scope="row">Facebook Pixel ID</th>
                <td><input type="text" name="gold_facebook_pixel_id" value="<?php echo esc_attr(get_option('gold_facebook_pixel_id')); ?>" class="regular-text" placeholder="1234567890"></td></tr>
            <tr><th scope="row">TikTok Pixel ID</th>
                <td><input type="text" name="gold_tiktok_pixel_id" value="<?php echo esc_attr(get_option('gold_tiktok_pixel_id')); ?>" class="regular-text" placeholder="ABCDEFG"></td></tr>
            <tr><th scope="row">Pinterest Tag ID</th>
                <td><input type="text" name="gold_pinterest_tag_id" value="<?php echo esc_attr(get_option('gold_pinterest_tag_id')); ?>" class="regular-text" placeholder="1234567890"></td></tr>
        </table>

        <h2 class="title" style="margin-top:30px;">Base Linker (Base.com)</h2>
        <table class="form-table">
            <tr><th scope="row">Webhook Token</th>
                <td><input type="text" name="gold_baselinker_webhook_token" value="<?php echo esc_attr(get_option('gold_baselinker_webhook_token')); ?>" class="regular-text">
                <p class="description">Token uwierzytelniający webhook Base.com. Skonfiguruj webhook w panelu Base.com: Ustawienia → API → Webhook URL: <code><?php echo rest_url('gold-shop/v1/baselinker/sync'); ?></code></p></td></tr>
        </table>

        <?php submit_button('Zapisz ustawienia'); ?>
    </form>
</div>
<?php
}
