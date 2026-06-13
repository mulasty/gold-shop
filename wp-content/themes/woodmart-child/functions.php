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
 * 11a. CRON - Price Alert + Flush Rewrite
 * ============================================= */

add_action('init', 'gold_schedule_price_check');
function gold_schedule_price_check() {
    if (!wp_next_scheduled('gold_price_alert_cron') && !wp_installing()) {
        wp_schedule_event(time(), 'hourly', 'gold_price_alert_cron');
    }
}

add_action('gold_price_alert_cron', 'gold_cron_check_alerts');
function gold_cron_check_alerts() {
    $prices = get_option('gold_current_prices', []);
    if (!empty($prices)) {
        do_action('gold_prices_updated', $prices);
    }
}

/* =============================================
 * 12. GOOGLE TAG MANAGER / GA4 / GOOGLE ADS
 * ============================================= */

add_action('wp_head', 'gold_gtm_head', 0);
function gold_gtm_head() {
    $gtm_id = get_option('gold_gtm_container_id', '');
    if (!$gtm_id) return;
    ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo esc_js($gtm_id); ?>');</script>
<?php
}

add_action('wp_body_open', 'gold_gtm_body', 0);
function gold_gtm_body() {
    $gtm_id = get_option('gold_gtm_container_id', '');
    if (!$gtm_id) return;
    ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($gtm_id); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php
}

add_action('wp_footer', 'gold_ga4_enhanced_events', 50);
function gold_ga4_enhanced_events() {
    if (!function_exists('WC') || is_admin()) return;

    $gtm_id = get_option('gold_gtm_container_id', '');
    if (!$gtm_id) return;
    ?>
<script>
window.dataLayer = window.dataLayer || [];
<?php if (is_product()) : global $product; if ($product) : ?>
dataLayer.push({ ecommerce: null });
dataLayer.push({
    event: 'view_item',
    ecommerce: {
        currency: 'PLN',
        value: <?php echo (float) $product->get_price(); ?>,
        items: [{
            item_id: '<?php echo esc_js($product->get_sku() ?: $product->get_id()); ?>',
            item_name: '<?php echo esc_js($product->get_name()); ?>',
            price: <?php echo (float) $product->get_price(); ?>,
            item_category: '<?php echo esc_js(wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names'])[0] ?? ''); ?>'
        }]
    }
});
<?php endif; endif; ?>
</script>
<?php
}

add_action('woocommerce_add_to_cart', 'gold_ga4_add_to_cart', 10, 4);
function gold_ga4_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id) {
    $gtm_id = get_option('gold_gtm_container_id', '');
    if (!$gtm_id) return;
    $product = wc_get_product($product_id);
    if (!$product) return;
    wc_enqueue_js("
        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            event: 'add_to_cart',
            ecommerce: {
                currency: 'PLN',
                value: " . ($product->get_price() * $quantity) . ",
                items: [{
                    item_id: '" . esc_js($product->get_sku() ?: $product_id) . "',
                    item_name: '" . esc_js($product->get_name()) . "',
                    price: " . $product->get_price() . ",
                    quantity: {$quantity}
                }]
            }
        });
    ");
}

add_action('woocommerce_thankyou', 'gold_ga4_purchase', 10, 1);
function gold_ga4_purchase($order_id) {
    $gtm_id = get_option('gold_gtm_container_id', '');
    if (!$gtm_id) return;
    $order = wc_get_order($order_id);
    if (!$order) return;
    $items = [];
    foreach ($order->get_items() as $item) {
        $items[] = sprintf(
            '{item_id: "%s", item_name: "%s", price: %s, quantity: %s}',
            esc_js($item->get_product()->get_sku() ?: $item->get_product_id()),
            esc_js($item->get_name()),
            $item->get_total(),
            $item->get_quantity()
        );
    }
    wc_enqueue_js("
        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            event: 'purchase',
            ecommerce: {
                transaction_id: '{$order->get_order_number()}',
                value: {$order->get_total()},
                currency: 'PLN',
                items: [" . implode(',', $items) . "]
            }
        });
    ");
}

add_action('wp_footer', 'gold_google_ads_conversion', 60);
function gold_google_ads_conversion() {
    $conversion_id = get_option('gold_google_ads_id', '');
    $conversion_label = get_option('gold_google_ads_label', '');
    if (!$conversion_id || !$conversion_label) return;

    if (is_order_received_page()) {
        global $wp;
        $order_id = $wp->query_vars['order-received'] ?? 0;
        if (!$order_id) return;
        $order = wc_get_order($order_id);
        if (!$order) return;
    ?>
<script>
gtag('event', 'conversion', {
    send_to: '<?php echo esc_js($conversion_id); ?>/<?php echo esc_js($conversion_label); ?>',
    value: <?php echo $order->get_total(); ?>,
    currency: 'PLN',
    transaction_id: '<?php echo $order->get_order_number(); ?>'
});
</script>
<?php
    }
}

/* =============================================
 * 13. GOOGLE MERCHANT CENTER - XML PRODUKTÓW
 * ============================================= */

add_action('init', 'gold_merchant_feed_rewrite');
function gold_merchant_feed_rewrite() {
    add_rewrite_rule('^gold-feed\.xml$', 'index.php?gold_feed=1', 'top');
}

add_filter('query_vars', 'gold_merchant_feed_query_var');
function gold_merchant_feed_query_var($vars) {
    $vars[] = 'gold_feed';
    return $vars;
}

add_action('template_redirect', 'gold_merchant_feed_output');
function gold_merchant_feed_output() {
    if (!get_query_var('gold_feed')) return;

    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="utf-8"?>';
    ?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
<channel>
<title>Sklep ze Złotem - Produkty</title>
<link><?php echo home_url(); ?></link>
<description>Feed produktów dla Google Merchant Center / YouTube Shopping</description>
<?php
    $products = wc_get_products(['limit' => -1, 'status' => 'publish']);
    $prices = get_option('gold_current_prices', []);
    $base_price = $prices['gold_pln_g'] ?? 0;

    foreach ($products as $product) {
        $id = $product->get_id();
        $gold_product = get_post_meta($id, '_gold_product', true);
        $price = $product->get_price();
        $gold_price = null;

        if ($gold_product === 'yes' && $base_price > 0) {
            $weight = (float) get_post_meta($id, '_gold_weight_g', true);
            $purity = get_post_meta($id, '_gold_purity', true) ?: '585';
            $purity_factors = ['375' => 0.375, '585' => 0.585, '750' => 0.750, '916' => 0.916, '999' => 0.999];
            $factor = $purity_factors[$purity] ?? 0.585;
            $premium = (float) get_post_meta($id, '_gold_premium_percent', true) ?: 5;
            if ($weight > 0) {
                $gold_price = round($base_price * $factor * $weight * (1 + $premium / 100));
                $price = $gold_price;
            }
        }
        $image = wp_get_attachment_image_url($product->get_image_id(), 'full') ?: wc_placeholder_img_src();
        $cats = wp_get_post_terms($id, 'product_cat', ['fields' => 'names']);
        $availability = $product->get_stock_status() === 'instock' ? 'in_stock' : 'out_of_stock';
        $sku = $product->get_sku() ?: 'gold-' . $id;
        ?>
<item>
<g:id><?php echo esc_xml($sku); ?></g:id>
<g:title><?php echo esc_xml($product->get_name()); ?></g:title>
<g:description><?php echo esc_xml(wp_trim_words(strip_tags($product->get_description() ?: $product->get_short_description()), 40)); ?></g:description>
<g:link><?php echo esc_url($product->get_permalink()); ?></g:link>
<g:image_link><?php echo esc_url($image); ?></g:image_link>
<g:price><?php echo $price; ?> PLN</g:price>
<g:availability><?php echo $availability; ?></g:availability>
<g:brand>Twój Sklep ze Złotem</g:brand>
<g:mpn><?php echo esc_xml($sku); ?></g:mpn>
<g:condition>new</g:condition>
<?php if ($cats) : ?><g:product_type><?php echo esc_xml(implode(' > ', $cats)); ?></g:product_type><?php endif; ?>
<?php if ($gold_product === 'yes') : ?>
<g:material>Złoto</g:material>
<g:product_type>Złoto Inwestycyjne</g:product_type>
<?php endif; ?>
</item>
<?php } ?>
</channel>
</rss>
<?php exit;
}

/* =============================================
 * 14. LIVE TICKER ZŁOTA - pływający pasek
 * ============================================= */

add_shortcode('gold_ticker', 'gold_ticker_shortcode');
function gold_ticker_shortcode() {
    $prices = get_option('gold_current_prices', []);
    if (empty($prices)) return '';

    $gold_g = $prices['gold_pln_g'] ?? 0;
    $gold_oz = $prices['gold_pln_oz'] ?? 0;
    $source = $prices['source'] ?? '';
    $change = $prices['change_percent'] ?? 0;
    $last = $prices['last_update'] ?? '';

    $change_class = $change >= 0 ? 'gold-up' : 'gold-down';
    $change_sign = $change >= 0 ? '+' : '';

    ob_start();
    ?>
<div class="gold-live-ticker" style="background:linear-gradient(90deg,#1a1a2e,#16213e);color:#fff;padding:8px 20px;font-size:0.85em;display:flex;flex-wrap:wrap;gap:20px;align-items:center;justify-content:center;border-bottom:1px solid #d4af37;font-family:Arial,sans-serif;">
    <span style="color:#d4af37;font-weight:700;">&#9679; ZŁOTO NA ŻYWO</span>
    <span>1 g: <strong><?php echo number_format($gold_g, 2, ',', ' '); ?> PLN</strong></span>
    <span>1 oz: <strong><?php echo number_format($gold_oz, 2, ',', ' '); ?> PLN</strong></span>
    <?php if ($change) : ?>
    <span class="<?php echo $change_class; ?>" style="font-weight:600;">
        <?php echo $change_sign . number_format($change, 2, ',', ' '); ?>%
    </span>
    <?php endif; ?>
    <span style="opacity:0.5;font-size:0.8em;">
        Źródło: <?php echo esc_html($source); ?> | <?php echo esc_html($last); ?>
    </span>
</div>
<style>
.gold-up { color: #4caf50; }
.gold-down { color: #f44336; }
.gold-live-ticker a { color: #d4af37; text-decoration: none; }
.gold-live-ticker a:hover { text-decoration: underline; }
@media (max-width: 768px) {
    .gold-live-ticker { font-size: 0.75em; gap: 10px; padding: 6px 10px; }
}
</style>
<?php
    return ob_get_clean();
}

/* =============================================
 * 15. SUBSKRYPCJA CENY - Price Alert
 * ============================================= */

add_shortcode('gold_price_alert', 'gold_price_alert_form');
function gold_price_alert_form() {
    ob_start();
    ?>
<div class="gold-price-alert" style="background:#1a1a2e;color:#fff;padding:25px;border-radius:12px;max-width:450px;margin:20px 0;font-family:Arial,sans-serif;">
    <h3 style="color:#d4af37;margin-top:0;font-size:1.2em;">🔔 Alert cenowy</h3>
    <p style="opacity:0.8;font-size:0.9em;">Powiadomimy Cię gdy złoto osiągnie wybraną cenę</p>
    <form id="gold-price-alert-form" method="post">
        <input type="hidden" name="gold_alert_action" value="subscribe">
        <?php wp_nonce_field('gold_alert_nonce', 'gold_alert_nonce_field'); ?>
        <div style="margin:12px 0;">
            <label style="display:block;margin-bottom:5px;font-size:0.9em;">Próba złota</label>
            <select name="alert_purity" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #d4af37;background:#16213e;color:#fff;font-size:1em;">
                <option value="585">585 (14K)</option>
                <option value="750">750 (18K)</option>
                <option value="999">999 (24K)</option>
            </select>
        </div>
        <div style="margin:12px 0;">
            <label style="display:block;margin-bottom:5px;font-size:0.9em;">Cena docelowa (PLN za gram)</label>
            <input type="number" name="alert_target_price" step="0.01" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #d4af37;background:#16213e;color:#fff;font-size:1em;" placeholder="np. 250.00">
        </div>
        <div style="margin:12px 0;">
            <label style="display:block;margin-bottom:5px;font-size:0.9em;">Twój e-mail</label>
            <input type="email" name="alert_email" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #d4af37;background:#16213e;color:#fff;font-size:1em;" placeholder="jan@example.com">
        </div>
        <div style="margin:12px 0;">
            <label style="display:block;margin-bottom:5px;font-size:0.9em;">Kierunek alertu</label>
            <select name="alert_direction" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #d4af37;background:#16213e;color:#fff;font-size:1em;">
                <option value="below">Gdy cena spadnie poniżej</option>
                <option value="above">Gdy cena wzrośnie powyżej</option>
            </select>
        </div>
        <button type="submit" style="width:100%;padding:12px;background:#d4af37;color:#1a1a2e;border:none;border-radius:6px;font-size:1em;font-weight:700;cursor:pointer;">Zapisz się na alert</button>
    </form>
    <div id="gold-alert-message" style="margin-top:10px;display:none;"></div>
</div>
<script>
document.getElementById('gold-price-alert-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this, data = new FormData(form), msg = document.getElementById('gold-alert-message');
    data.append('action', 'gold_subscribe_alert');
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: data })
    .then(r => r.json()).then(r => {
        msg.style.display = 'block';
        msg.style.color = r.success ? '#4caf50' : '#f44336';
        msg.textContent = r.data;
        if (r.success) form.reset();
    });
});
</script>
<?php
    return ob_get_clean();
}

add_action('wp_ajax_gold_subscribe_alert', 'gold_ajax_subscribe_alert');
add_action('wp_ajax_nopriv_gold_subscribe_alert', 'gold_ajax_subscribe_alert');
function gold_ajax_subscribe_alert() {
    check_ajax_referer('gold_alert_nonce', 'gold_alert_nonce_field');

    $email = sanitize_email($_POST['alert_email'] ?? '');
    $purity = sanitize_text_field($_POST['alert_purity'] ?? '');
    $target = (float) ($_POST['alert_target_price'] ?? 0);
    $direction = in_array($_POST['alert_direction'] ?? '', ['below', 'above']) ? $_POST['alert_direction'] : 'below';

    if (!is_email($email) || !$target) {
        wp_send_json_error('Nieprawidłowe dane.');
    }

    $alerts = get_option('gold_price_alerts', []);
    $hash = md5($email . $purity . $target . $direction);

    if (isset($alerts[$hash])) {
        wp_send_json_error('Jesteś już zapisany na ten alert.');
    }

    $alerts[$hash] = [
        'email'     => $email,
        'purity'    => $purity,
        'target'    => $target,
        'direction' => $direction,
        'created'   => current_time('mysql'),
        'notified'  => false,
    ];
    update_option('gold_price_alerts', $alerts);

    wp_send_json_success('Alert zapisany! Powiadomimy Cię gdy cena osiągnie wybrany poziom.');
}

add_action('gold_prices_updated', 'gold_check_price_alerts');
function gold_check_price_alerts($prices) {
    $current = $prices['gold_pln_g'] ?? 0;
    if (!$current) return;

    $alerts = get_option('gold_price_alerts', []);
    $purity_factors = ['585' => 0.585, '750' => 0.750, '999' => 0.999];
    $to_notify = [];

    foreach ($alerts as $hash => $alert) {
        if ($alert['notified']) continue;

        $factor = $purity_factors[$alert['purity']] ?? 0.585;
        $price_per_gram = $current * $factor;

        $triggered = false;
        if ($alert['direction'] === 'below' && $price_per_gram <= $alert['target']) {
            $triggered = true;
        } elseif ($alert['direction'] === 'above' && $price_per_gram >= $alert['target']) {
            $triggered = true;
        }

        if ($triggered) {
            $to_notify[] = $alert;
            $alerts[$hash]['notified'] = true;
        }
    }

    update_option('gold_price_alerts', $alerts);

    foreach ($to_notify as $alert) {
        gold_send_price_alert_email($alert, $current);
    }
}

function gold_send_price_alert_email($alert, $current_price) {
    $subject = sprintf('🔔 Alert cenowy: złoto %s %.2f PLN/g', $alert['purity'], $alert['target']);
    $direction_text = $alert['direction'] === 'below' ? 'spadła poniżej' : 'wzrosła powyżej';
    $body = sprintf(
        "Cena złota próby %s właśnie %s %.2f PLN za gram!\n\n"
      . "Aktualna cena: %.2f PLN/g\n"
      . "Twój alert: %s %.2f PLN/g\n\n"
      . "Odwiedź nasz sklep: %s\n\n"
      . "---\nSklep ze Złotem",
        $alert['purity'],
        $direction_text,
        $alert['target'],
        $current_price,
        $alert['direction'] === 'below' ? 'poniżej' : 'powyżej',
        $alert['target'],
        home_url('/sklep')
    );

    wp_mail($alert['email'], $subject, $body, [
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>',
    ]);
}

/* =============================================
 * 16. PROGRAM POLECEŃ - Referral System
 * ============================================= */

add_shortcode('gold_referral', 'gold_referral_shortcode');
function gold_referral_shortcode() {
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return '<p style="color:#d4af37;">Zaloguj się aby otrzymać link polecający i zacząć zarabiać!</p>';
    }

    $code = get_user_meta($user->ID, 'gold_referral_code', true);
    if (!$code) {
        $code = strtoupper(substr(md5($user->ID . $user->user_email . SECURE_AUTH_KEY), 0, 8));
        update_user_meta($user->ID, 'gold_referral_code', $code);
    }

    $referral_url = add_query_arg('ref', $code, home_url('/'));
    $count = (int) get_user_meta($user->ID, 'gold_referral_count', true);
    $earned = (float) get_user_meta($user->ID, 'gold_referral_earned', true);

    ob_start();
    ?>
<div class="gold-referral" style="background:#1a1a2e;color:#fff;padding:25px;border-radius:12px;max-width:500px;margin:20px 0;font-family:Arial,sans-serif;">
    <h3 style="color:#d4af37;margin-top:0;font-size:1.2em;">🎁 Program poleceń</h3>
    <p style="opacity:0.8;font-size:0.9em;">Poleć znajomym i zyskaj <strong style="color:#d4af37;">5% wartości zamówienia</strong> w złocie!</p>

    <div style="margin:15px 0;padding:15px;background:#16213e;border-radius:8px;">
        <label style="display:block;margin-bottom:5px;font-size:0.85em;opacity:0.7;">Twój link polecający:</label>
        <div style="display:flex;gap:8px;">
            <input type="text" id="gold-referral-link" value="<?php echo esc_url($referral_url); ?>" readonly style="flex:1;padding:10px;border-radius:6px;border:1px solid #d4af37;background:#1a1a2e;color:#fff;font-size:0.9em;">
            <button onclick="navigator.clipboard.writeText(document.getElementById('gold-referral-link').value);this.textContent='Skopiowano!';setTimeout(()=>this.textContent='Kopiuj',2000)" style="padding:10px 15px;background:#d4af37;color:#1a1a2e;border:none;border-radius:6px;cursor:pointer;font-weight:700;">Kopiuj</button>
        </div>
    </div>

    <div style="display:flex;gap:20px;margin:15px 0;">
        <div style="flex:1;padding:15px;background:#16213e;border-radius:8px;text-align:center;">
            <div style="font-size:1.5em;font-weight:700;color:#d4af37;"><?php echo $count; ?></div>
            <div style="font-size:0.8em;opacity:0.7;">Poleconych</div>
        </div>
        <div style="flex:1;padding:15px;background:#16213e;border-radius:8px;text-align:center;">
            <div style="font-size:1.5em;font-weight:700;color:#d4af37;"><?php echo number_format($earned, 2, ',', ' '); ?> PLN</div>
            <div style="font-size:0.8em;opacity:0.7;">Zarobione</div>
        </div>
    </div>

    <p style="font-size:0.8em;opacity:0.6;margin:10px 0 0;">
        Udostępnij link znajomym. Gdy złożą zamówienie, otrzymasz 5% wartości ich pierwszych zakupów w formie kodu rabatowego.
    </p>

    <div style="margin-top:15px;display:flex;gap:10px;">
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($referral_url); ?>" target="_blank" style="flex:1;padding:10px;background:#1877F2;color:#fff;border-radius:6px;text-align:center;text-decoration:none;font-weight:600;">Facebook</a>
        <a href="https://twitter.com/intent/tweet?text=Sprawd%C5%BA+ten+sklep+ze+z%C5%82otem%3A+<?php echo urlencode($referral_url); ?>" target="_blank" style="flex:1;padding:10px;background:#1DA1F2;color:#fff;border-radius:6px;text-align:center;text-decoration:none;font-weight:600;">Twitter</a>
        <a href="https://api.whatsapp.com/send?text=Sprawd%C5%BA+ten+sklep+ze+z%C5%82otem%3A+<?php echo urlencode($referral_url); ?>" target="_blank" style="flex:1;padding:10px;background:#25D366;color:#fff;border-radius:6px;text-align:center;text-decoration:none;font-weight:600;">WhatsApp</a>
    </div>
</div>
<?php
    return ob_get_clean();
}

add_action('user_register', 'gold_create_referral_code');
function gold_create_referral_code($user_id) {
    $code = strtoupper(substr(md5($user_id . time() . NONCE_KEY), 0, 8));
    update_user_meta($user_id, 'gold_referral_code', $code);
    update_user_meta($user_id, 'gold_referral_count', 0);
    update_user_meta($user_id, 'gold_referral_earned', 0);
}

add_action('init', 'gold_track_referral');
function gold_track_referral() {
    if (!isset($_GET['ref']) || is_admin()) return;
    $code = sanitize_text_field($_GET['ref']);
    if (!headers_sent()) {
        setcookie('gold_referral', $code, time() + DAY_IN_SECONDS * 30, COOKIEPATH, COOKIE_DOMAIN);
    }
}

add_action('woocommerce_checkout_update_order_meta', 'gold_apply_referral_to_order');
function gold_apply_referral_to_order($order_id) {
    if (!isset($_COOKIE['gold_referral'])) return;
    $code = sanitize_text_field($_COOKIE['gold_referral']);

    $referrer_id = gold_find_user_by_referral_code($code);
    if (!$referrer_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $total = $order->get_total();
    $commission = $total * 0.05;

    $order->update_meta_data('_gold_referral_code', $code);
    $order->update_meta_data('_gold_referral_referrer', $referrer_id);
    $order->update_meta_data('_gold_referral_commission', $commission);
    $order->save();

    update_user_meta($referrer_id, 'gold_referral_count', (int) get_user_meta($referrer_id, 'gold_referral_count', true) + 1);
    update_user_meta($referrer_id, 'gold_referral_earned', (float) get_user_meta($referrer_id, 'gold_referral_earned', true) + $commission);
}

function gold_find_user_by_referral_code($code) {
    $users = get_users(['meta_key' => 'gold_referral_code', 'meta_value' => $code, 'number' => 1]);
    return !empty($users) ? $users[0]->ID : 0;
}

/* =============================================
 * 17. KONFIGUROWALNE OPCJE (Admin Settings)
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
    // Google Ads + Merchant Center
    $extra = [
        'gold_google_ads_id' => ['type' => 'string', 'default' => ''],
        'gold_google_ads_label' => ['type' => 'string', 'default' => ''],
        'gold_merchant_center_id' => ['type' => 'string', 'default' => ''],
    ];
    foreach ($extra as $key => $args) {
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

        <h2 class="title" style="margin-top:30px;">Google Tag Manager / GA4 / Google Ads</h2>
        <table class="form-table">
            <tr><th scope="row">GTM Container ID</th>
                <td><input type="text" name="gold_gtm_container_id" value="<?php echo esc_attr(get_option('gold_gtm_container_id')); ?>" class="regular-text" placeholder="GTM-XXXXXXX">
                <p class="description">Kod GTM - automatycznie dodaje GA4 (view_item, add_to_cart, purchase) i śledzenie konwersji.</p></td></tr>
            <tr><th scope="row">Google Ads ID</th>
                <td><input type="text" name="gold_google_ads_id" value="<?php echo esc_attr(get_option('gold_google_ads_id')); ?>" class="regular-text" placeholder="AW-123456789"></td></tr>
            <tr><th scope="row">Google Ads Etykieta</th>
                <td><input type="text" name="gold_google_ads_label" value="<?php echo esc_attr(get_option('gold_google_ads_label')); ?>" class="regular-text" placeholder="XXXXXXXX"></td></tr>
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

        <h2 class="title" style="margin-top:30px;">Google Merchant Center / YouTube Shopping</h2>
        <table class="form-table">
            <tr><th scope="row">Merchant Center ID</th>
                <td><input type="text" name="gold_merchant_center_id" value="<?php echo esc_attr(get_option('gold_merchant_center_id')); ?>" class="regular-text" placeholder="123456789">
                <p class="description">Feed XML dostępny pod adresem: <code><a href="<?php echo home_url('/gold-feed.xml'); ?>" target="_blank"><?php echo home_url('/gold-feed.xml'); ?></a></code><br>Dodaj ten URL w Google Merchant Center &rarr; Produkty &rarr; Feed.</p></td></tr>
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

/* Flush rewrite rules on theme switch */
add_action('after_switch_theme', function () {
    flush_rewrite_rules();
});
