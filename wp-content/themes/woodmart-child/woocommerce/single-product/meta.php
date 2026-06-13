<?php
global $product;
?>
<div class="product_meta gold-product-meta">
    <?php do_action('woocommerce_product_meta_start'); ?>

    <?php $gold_product = get_post_meta($product->get_id(), '_gold_product', true); ?>
    <?php if ($gold_product === 'yes') : ?>
        <?php
        $purity = get_post_meta($product->get_id(), '_gold_purity', true);
        $weight = get_post_meta($product->get_id(), '_gold_weight_g', true);
        $premium = get_post_meta($product->get_id(), '_gold_premium_percent', true);
        ?>
        <span class="sku_wrapper gold-detail">
            <span class="gold-detail-label">Próba złota:</span>
            <span class="gold-detail-value"><?php echo esc_html($purity); ?> (<?php echo esc_html(gold_get_purity_karat($purity)); ?>)</span>
        </span>
        <?php if ($weight) : ?>
        <span class="sku_wrapper gold-detail">
            <span class="gold-detail-label">Waga:</span>
            <span class="gold-detail-value"><?php echo esc_html($weight); ?> g</span>
        </span>
        <?php endif; ?>
        <?php if ($premium) : ?>
        <span class="sku_wrapper gold-detail">
            <span class="gold-detail-label">Premia:</span>
            <span class="gold-detail-value">+<?php echo esc_html($premium); ?>%</span>
        </span>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (wc_product_sku_enabled() && ($product->get_sku() || $product->is_type('variable'))) : ?>
        <span class="sku_wrapper"><?php esc_html_e('SKU:', 'woodmart'); ?> <span class="sku"><?php echo esc_html($sku = $product->get_sku()) ? $sku : esc_html__('N/A', 'woodmart'); ?></span></span>
    <?php endif; ?>

    <?php echo wc_get_product_category_list($product->get_id(), ', ', '<span class="posted_in">' . _n('Category:', 'Categories:', count($product->get_category_ids()), 'woodmart') . ' ', '</span>'); ?>
    <?php echo wc_get_product_tag_list($product->get_id(), ', ', '<span class="tagged_as">' . _n('Tag:', 'Tags:', count($product->get_tag_ids()), 'woodmart') . ' ', '</span>'); ?>
    <?php do_action('woocommerce_product_meta_end'); ?>
</div>
