<?php
global $product;

$gold_product = get_post_meta($product->get_id(), '_gold_product', true);
if ($gold_product === 'yes') {
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
    }
}
?>
<p class="<?php echo esc_attr(apply_filters('woocommerce_product_price_class', 'price')); ?>">
    <?php if ($gold_product === 'yes' && isset($calculated)) : ?>
        <span class="gold-price-calculated" data-last-update="<?php echo esc_attr($last_update); ?>">
            <?php echo wc_price($calculated); ?>
        </span>
    <?php else : ?>
        <?php echo $product->get_price_html(); ?>
    <?php endif; ?>
</p>

<?php if ($gold_product === 'yes' && !empty($last_update)) : ?>
    <p class="gold-price-note" style="font-size:0.8em;opacity:0.7;margin-top:-10px;margin-bottom:15px;">
        Cena oparta na notowaniach złota z <?php echo esc_html($last_update); ?>.
        <br>Cena może ulec zmianie przy kolejnej aktualizacji notowań.
    </p>
<?php endif; ?>
