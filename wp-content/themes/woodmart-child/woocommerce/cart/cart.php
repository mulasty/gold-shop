<?php
defined('ABSPATH') || exit;

do_action('woocommerce_before_cart'); ?>

<form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
    <?php do_action('woocommerce_before_cart_table'); ?>
    <table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
        <thead>
            <tr>
                <th class="product-remove"><span class="screen-reader-text"><?php esc_html_e('Remove item', 'woodmart'); ?></span></th>
                <th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e('Thumbnail image', 'woodmart'); ?></span></th>
                <th class="product-name"><?php esc_html_e('Product', 'woodmart'); ?></th>
                <th class="product-price"><?php esc_html_e('Price', 'woodmart'); ?></th>
                <th class="product-quantity"><?php esc_html_e('Quantity', 'woodmart'); ?></th>
                <th class="product-subtotal"><?php esc_html_e('Subtotal', 'woodmart'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php do_action('woocommerce_before_cart_contents'); ?>
            <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                $gold_product = get_post_meta($product_id, '_gold_product', true);

                if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) :
                    $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
            ?>
                <tr class="woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
                    <td class="product-remove">
                        <?php echo apply_filters('woocommerce_cart_item_remove_link', sprintf(
                            '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
                            esc_url(wc_get_cart_remove_url($cart_item_key)),
                            esc_html__('Remove this item', 'woodmart'),
                            esc_attr($product_id),
                            esc_attr($_product->get_sku())
                        ), $cart_item_key); ?>
                    </td>
                    <td class="product-thumbnail">
                        <?php $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
                        if (!$product_permalink) echo $thumbnail;
                        else printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); ?>
                    </td>
                    <td class="product-name" data-title="<?php esc_attr_e('Product', 'woodmart'); ?>">
                        <?php
                        if (!$product_permalink) echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key) . '&nbsp;');
                        else echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
                        do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
                        echo wc_get_formatted_cart_item_data($cart_item);

                        if ($gold_product === 'yes') {
                            $purity = get_post_meta($product_id, '_gold_purity', true);
                            $weight = get_post_meta($product_id, '_gold_weight_g', true);
                            if ($purity) echo '<br><small>Próba: ' . esc_html($purity) . '</small>';
                            if ($weight) echo '<br><small>Waga: ' . esc_html($weight) . ' g</small>';
                        }

                        if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woodmart') . '</p>', $product_id));
                        ?>
                    </td>
                    <td class="product-price" data-title="<?php esc_attr_e('Price', 'woodmart'); ?>">
                        <?php echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); ?>
                    </td>
                    <td class="product-quantity" data-title="<?php esc_attr_e('Quantity', 'woodmart'); ?>">
                        <?php if ($_product->is_sold_individually()) :
                            $product_quantity = sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key);
                        else :
                            $product_quantity = woocommerce_quantity_input([
                                'input_name'   => "cart[{$cart_item_key}][qty]",
                                'input_value'  => $cart_item['quantity'],
                                'max_value'    => $_product->get_max_purchase_quantity(),
                                'min_value'    => '0',
                                'product_name' => $_product->get_name(),
                            ], $_product, false);
                        endif;
                        echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item); ?>
                    </td>
                    <td class="product-subtotal" data-title="<?php esc_attr_e('Subtotal', 'woodmart'); ?>">
                        <?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
                    </td>
                </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php do_action('woocommerce_cart_contents'); ?>
            <tr>
                <td colspan="6" class="actions">
                    <?php if (wc_coupons_enabled()) : ?>
                        <div class="coupon">
                            <label for="coupon_code" class="screen-reader-text"><?php esc_html_e('Coupon:', 'woodmart'); ?></label>
                            <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e('Coupon code', 'woodmart'); ?>" />
                            <button type="submit" class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woodmart'); ?>"><?php esc_html_e('Apply coupon', 'woodmart'); ?></button>
                            <?php do_action('woocommerce_cart_coupon'); ?>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="update_cart" value="<?php esc_attr_e('Update cart', 'woodmart'); ?>"><?php esc_html_e('Update cart', 'woodmart'); ?></button>
                    <?php do_action('woocommerce_cart_actions'); ?>
                    <?php wp_nonce_field('woocommerce-cart', '_wpnonce'); ?>
                </td>
            </tr>
            <?php do_action('woocommerce_after_cart_contents'); ?>
        </tbody>
    </table>
    <?php do_action('woocommerce_after_cart_table'); ?>
</form>

<?php do_action('woocommerce_before_cart_collaterals'); ?>
<div class="cart-collaterals">
    <?php
    do_action('woocommerce_cart_collaterals');
    gold_cart_gold_summary();
    ?>
</div>
<?php do_action('woocommerce_after_cart'); ?>

<?php
function gold_cart_gold_summary() {
    $gold_total_weight = 0;
    foreach (WC()->cart->get_cart() as $item) {
        $product_id = $item['product_id'];
        $gold_product = get_post_meta($product_id, '_gold_product', true);
        if ($gold_product === 'yes') {
            $weight = (float) get_post_meta($product_id, '_gold_weight_g', true);
            $gold_total_weight += $weight * $item['quantity'];
        }
    }
    if ($gold_total_weight > 0) : ?>
    <div class="cart-gold-summary" style="background:#1a1a2e;color:#fff;padding:20px;border-radius:8px;margin-top:20px;">
        <h4 style="color:#d4af37;margin-top:0;">Podsumowanie zakupu złota</h4>
        <p style="margin:5px 0;">Łączna waga: <strong><?php echo number_format($gold_total_weight, 2, ',', ' '); ?> g</strong></p>
        <p style="margin:5px 0;font-size:0.85em;opacity:0.7;">
            Ceny złota są dynamiczne i oparte na bieżących notowaniach rynkowych.
            Ostateczna cena zostanie potwierdzona po złożeniu zamówienia.
        </p>
    </div>
    <?php endif;
}
