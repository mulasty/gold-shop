<?php
/**
 * Base Linker (Base.com) Integration for WooCommerce
 * 
 * Klasa rozszerzająca WooCommerce native integration API
 * do komunikacji z Base.com (dawniej BaseLinker).
 */

class Gold_Base_Linker_Integration extends WC_Integration {

    public function __construct() {
        $this->id                 = 'gold-base-linker';
        $this->method_title       = 'Base Linker (Base.com)';
        $this->method_description = 'Integracja z Base.com - synchronizacja produktów, zamówień, stanów magazynowych i cen.';

        $this->init_form_fields();
        $this->init_settings();

        $this->api_url   = $this->get_option('api_url', 'https://api.base.com/v1');
        $this->api_token = $this->get_option('api_token', '');
        $this->webhook_url = $this->get_option('webhook_url', rest_url('gold-shop/v1/baselinker/sync'));

        add_action('woocommerce_update_options_integration_' . $this->id, [$this, 'process_admin_options']);

        // Webhooki Base.com - nasłuchiwanie eventów
        add_action('gold_baselinker_event', [$this, 'handle_event'], 10, 2);

        // Eksport produktów do Base.com
        add_action('save_post_product', [$this, 'sync_product_on_save'], 10, 3);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'api_url' => [
                'title'       => 'API URL',
                'type'        => 'text',
                'description' => 'URL API Base.com (domyślnie: https://api.base.com/v1)',
                'default'     => 'https://api.base.com/v1',
            ],
            'api_token' => [
                'title'       => 'API Token',
                'type'        => 'password',
                'description' => 'Token API z panelu Base.com: Ustawienia → API → Klucz API',
                'default'     => '',
            ],
            'enable_auto_sync' => [
                'title'       => 'Auto-synchronizacja',
                'type'        => 'checkbox',
                'label'       => 'Włącz automatyczną synchronizację produktów przy zapisie',
                'default'     => 'yes',
            ],
        ];
    }

    public function handle_event($event, $data) {
        switch ($event) {
            case 'order.create':
                $this->handle_order_create($data);
                break;
            case 'order.update':
                $this->handle_order_update($data);
                break;
            case 'product.update':
                $this->handle_product_update($data);
                break;
            case 'inventory.update':
                $this->handle_inventory_update($data);
                break;
        }
    }

    private function handle_order_create($data) {
        $order_id = $data['order_id'] ?? 0;
        if (!$order_id) return;
        $order = wc_get_order($order_id);
        if (!$order) return;

        $order->update_meta_data('_baselinker_synced', current_time('mysql'));
        $order->save();
    }

    private function handle_order_update($data) {
        $order_id = $data['order_id'] ?? 0;
        if (!$order_id) return;
        $order = wc_get_order($order_id);
        if (!$order) return;
        $status = $data['status_id'] ?? '';
        $base_status_map = [
            '1'  => 'pending',
            '2'  => 'processing',
            '3'  => 'completed',
            '4'  => 'cancelled',
        ];
        if (isset($base_status_map[$status])) {
            $order->update_status($base_status_map[$status], sprintf(
                'Status zaktualizowany przez Base.com (status_id: %s)',
                $status
            ));
        }
    }

    private function handle_product_update($data) {
        $product_id = $data['product_id'] ?? 0;
        if (!$product_id) return;
        if ($this->get_option('enable_auto_sync', 'yes') !== 'yes') return;

        $price = $data['price'] ?? null;
        $stock = $data['stock'] ?? null;

        $product = wc_get_product($product_id);
        if (!$product) return;

        if ($price !== null) {
            $product->set_regular_price($price);
        }
        if ($stock !== null) {
            $product->set_stock_quantity((int) $stock);
            $product->set_stock_status($stock > 0 ? 'instock' : 'outofstock');
        }
        $product->save();
    }

    private function handle_inventory_update($data) {
        $product_id = $data['product_id'] ?? 0;
        $quantity = $data['quantity'] ?? null;
        if (!$product_id || $quantity === null) return;

        $product = wc_get_product($product_id);
        if ($product) {
            $product->set_stock_quantity((int) $quantity);
            $product->set_stock_status($quantity > 0 ? 'instock' : 'outofstock');
            $product->save();
        }
    }

    public function sync_product_on_save($post_id, $post, $update) {
        if ($post->post_type !== 'product') return;
        if ($this->get_option('enable_auto_sync', 'yes') !== 'yes') return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        $product = wc_get_product($post_id);
        if (!$product) return;

        $data = [
            'product_id' => $product->get_id(),
            'name'       => $product->get_name(),
            'sku'        => $product->get_sku(),
            'price'      => $product->get_price(),
            'stock'      => $product->get_stock_quantity(),
            'categories' => wp_get_post_terms($post_id, 'product_cat', ['fields' => 'names']),
        ];

        $gold_product = get_post_meta($post_id, '_gold_product', true);
        if ($gold_product === 'yes') {
            $data['attributes'] = [
                'gold_weight'  => get_post_meta($post_id, '_gold_weight_g', true),
                'gold_purity'  => get_post_meta($post_id, '_gold_purity', true),
                'gold_premium' => get_post_meta($post_id, '_gold_premium_percent', true),
            ];
        }

        $this->api_request('product.sync', $data);
    }

    private function api_request($method, $parameters = []) {
        if (!$this->api_token) {
            $this->log('Brak tokena API Base.com');
            return false;
        }

        $response = wp_remote_post($this->api_url, [
            'timeout' => 30,
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_token,
            ],
            'body' => wp_json_encode([
                'method'     => $method,
                'parameters' => $parameters,
            ]),
        ]);

        if (is_wp_error($response)) {
            $this->log('Błąd API Base.com: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (($body['status'] ?? '') !== 'SUCCESS') {
            $this->log('API Base.com zwróciło błąd: ' . ($body['error_message'] ?? 'nieznany błąd'));
            return false;
        }

        return $body;
    }

    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[BaseLinker] ' . $message);
        }
    }
}
