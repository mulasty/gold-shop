<?php
/**
 * Kadence Child - Zlot functions
 */

// BaseLinker Integration loader
require_once get_stylesheet_directory() . '/inc/class-base-linker-integration.php';

/**
 * SEO Meta Updater — runs once on admin init, sets Rank Math / Yoast titles & descriptions
 */
function goldshop_seo_meta_updater() {
	if ( get_option( 'goldshop_seo_meta_v1_done' ) ) {
		return;
	}

	$pages = array(
		// Homepage (front page, ID=7)
		7  => array(
			'title'       => 'Gold Shop – Złoto inwestycyjne online. Sztabki i monety LBMA',
			'description' => 'Kup złoto inwestycyjne online w Gold Shop. Certyfikowane sztabki i monety bulionowe. Zwolnione z VAT. Wysyłka 24h. Sprawdź ceny!',
		),
		// O nas ID=18
		18 => array(
			'title'       => 'O nas – Gold Shop | Dealer złota inwestycyjnego | Mula Group',
			'description' => 'Gold Shop to dealer złota inwestycyjnego należący do Mula Group. Oferujemy certyfikowane złote sztabki i monety bulionowe inwestycyjne LBMA. Sprawdź naszą ofertę.',
		),
		// Kontakt ID=17
		17 => array(
			'title'       => 'Kontakt – Gold Shop | Skup i sprzedaż złota',
			'description' => 'Skontaktuj się z Gold Shop – dealerem złota inwestycyjnego. Skup i sprzedaż certyfikowanych sztabek i monet bulionowych. Zapraszamy do kontaktu.',
		),
		// Jak kupować ID=19
		19 => array(
			'title'       => 'Jak kupować złoto inwestycyjne – poradnik | Gold Shop',
			'description' => 'Dowiedz się jak bezpiecznie kupować złoto inwestycyjne online. Poradnik krok po kroku – sztabki i monety bulionowe LBMA zwolnione z VAT.',
		),
		// Regulamin ID=15
		15 => array(
			'title'       => 'Regulamin sklepu | Gold Shop',
			'description' => 'Regulamin sklepu internetowego Gold Shop. Zapoznaj się z zasadami zakupu złota inwestycyjnego – sztabek i monet bulionowych.',
		),
		// Polityka prywatności ID=16
		16 => array(
			'title'       => 'Polityka prywatności | Gold Shop',
			'description' => 'Polityka prywatności sklepu Gold Shop. Dowiedz się jak przetwarzamy Twoje dane osobowe podczas zakupu złota inwestycyjnego.',
		),
	);

	foreach ( $pages as $id => $data ) {
		update_post_meta( $id, 'rank_math_title', $data['title'] );
		update_post_meta( $id, 'rank_math_description', $data['description'] );
		update_post_meta( $id, '_yoast_wpseo_title', $data['title'] );
		update_post_meta( $id, '_yoast_wpseo_metadesc', $data['description'] );
	}

	// Product category meta
	$categories = array(
		62 => array(
			'title'       => 'Złote sztabki inwestycyjne | Certyfikat LBMA | Zwolnione z VAT | Gold Shop',
			'description' => 'Złote sztabki inwestycyjne renomowanych mennic LBMA. Certyfikowane, zwolnione z VAT. Kupuj bezpiecznie online w Gold Shop. Sprawdź ceny!',
			'slug'        => 'zlote-sztabki',
		),
		63 => array(
			'title'       => 'Złote monety bulionowe – inwestycyjne | Bez VAT | Gold Shop',
			'description' => 'Złote monety bulionowe inwestycyjne – Krugerrand, Filharmonik, Kangur i więcej. Sprawdzone mennice, certyfikaty, zwolnione z VAT. Kup online!',
			'slug'        => 'zlote-monety',
		),
		17 => array(
			'title'       => 'Srebro inwestycyjne – sztabki i monety | Kup online | Gold Shop',
			'description' => 'Srebro inwestycyjne – sztabki i monety bulionowe. Najlepsze ceny srebra inwestycyjnego online. Sprawdź ofertę Gold Shop!',
			'slug'        => 'srebro-inwestycyjne',
		),
	);

	foreach ( $categories as $id => $data ) {
		update_term_meta( $id, 'rank_math_title', $data['title'] );
		update_term_meta( $id, 'rank_math_description', $data['description'] );
	}

	update_option( 'goldshop_seo_meta_v1_done', 1 );
}
add_action( 'admin_init', 'goldshop_seo_meta_updater' );

/**
 * Output Organization schema on every page
 */
function goldshop_schema_organization() {
	$json = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Organization',
		'name'        => 'Gold Shop',
		'url'         => 'http://goldshop.mulagroup.eu',
		'description' => 'Dealer złota inwestycyjnego – sztabki i monety bulionowe LBMA',
		'parentOrganization' => 'Mula Group',
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'goldshop_schema_organization', 5 );

/**
 * Output WebSite schema on homepage only
 */
function goldshop_schema_website() {
	if ( ! is_front_page() ) {
		return;
	}

	$json = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'WebSite',
		'name'            => 'Gold Shop',
		'url'             => 'http://goldshop.mulagroup.eu',
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => 'http://goldshop.mulagroup.eu/?s={search_term_string}',
			'query-input' => 'required name=search_term_string',
		),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'goldshop_schema_website', 10 );

/**
 * Output BreadcrumbList schema on all pages except front page
 */
function goldshop_schema_breadcrumb() {
	if ( is_front_page() ) {
		return;
	}

	$items   = array();
	$item_id = 1;

	$items[] = array(
		'@type'    => 'ListItem',
		'position' => $item_id,
		'name'     => 'Gold Shop',
		'item'     => home_url( '/' ),
	);
	$item_id++;

	if ( is_singular( 'product' ) ) {
		$product_cats = get_the_terms( get_the_ID(), 'product_cat' );
		if ( $product_cats && ! is_wp_error( $product_cats ) ) {
			$main_cat = $product_cats[0];
			$items[]  = array(
				'@type'    => 'ListItem',
				'position' => $item_id,
				'name'     => $main_cat->name,
				'item'     => get_term_link( $main_cat ),
			);
			$item_id++;
		}

		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $item_id,
			'name'     => get_the_title(),
		);
	} elseif ( is_tax( 'product_cat' ) ) {
		$term = get_queried_object();
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $item_id,
			'name'     => $term->name,
		);
	} elseif ( is_page() ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $item_id,
			'name'     => get_the_title(),
		);
	} elseif ( is_single() ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $item_id,
			'name'     => get_the_title(),
		);
	} else {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $item_id,
			'name'     => wp_get_document_title(),
		);
	}

	$json = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'goldshop_schema_breadcrumb', 20 );

/**
 * Output Product schema on product pages
 */
function goldshop_schema_product() {
	if ( ! is_singular( 'product' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) {
		return;
	}

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'name'        => $product->get_name(),
		'description' => wp_strip_all_tags( $product->get_description() ),
		'url'         => get_permalink( $product->get_id() ),
	);

	if ( $product->get_sku() ) {
		$schema['sku'] = $product->get_sku();
	}

	if ( has_post_thumbnail( $product->get_id() ) ) {
		$thumb_id        = get_post_thumbnail_id( $product->get_id() );
		$thumb_url       = wp_get_attachment_url( $thumb_id );
		if ( $thumb_url ) {
			$schema['image'] = $thumb_url;
		}
	}

	if ( $product->get_price() ) {
		$schema['offers'] = array(
			'@type'           => 'Offer',
			'price'           => wc_get_price_to_display( $product ),
			'priceCurrency'   => get_woocommerce_currency(),
			'availability'    => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			'url'             => get_permalink( $product->get_id() ),
		);
	}

	$brand_terms = get_the_terms( $product->get_id(), 'product_brand' );
	if ( $brand_terms && ! is_wp_error( $brand_terms ) ) {
		$schema['brand'] = array(
			'@type' => 'Brand',
			'name'  => $brand_terms[0]->name,
		);
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'goldshop_schema_product', 30 );

/**
 * Register Rank Math meta fields for REST API access
 */
function goldshop_register_meta_rest() {
	$post_types = array( 'page', 'post', 'product' );
	foreach ( $post_types as $pt ) {
		register_post_meta( $pt, 'rank_math_title', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
		) );
		register_post_meta( $pt, 'rank_math_description', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
		) );
	}

	$taxonomies = array( 'category', 'product_cat' );
	foreach ( $taxonomies as $tax ) {
		register_term_meta( $tax, 'rank_math_title', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
		) );
		register_term_meta( $tax, 'rank_math_description', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
		) );
	}
}
add_action( 'init', 'goldshop_register_meta_rest' );
