<?php
global $product;

$gold_product = get_post_meta($product->get_id(), '_gold_product', true);
if ($gold_product === 'yes') {
    $base_price = 0;
    $prices = get_option('gold_current_prices', []);
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
<?php if ($gold_product === 'yes' && isset($calculated)) : ?>
    <span class="price gold-loop-price">
        <span class="woocommerce-Price-amount amount">
            <?php echo wc_price($calculated); ?>
        </span>
    </span>
<?php else : ?>
    <?php echo $product->get_price_html(); ?>
<?php endif; ?>
