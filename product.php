<?php
/**
 * Template Name: Product view page
 * The template for displaying products
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

use SellmarkTheme\{
	CarbonFields,
	Data
};
use SellmarkTheme\Yoast\Schema\Piece\Product as ProductPiece;

$context = Timber::context();
$productSlug = get_query_var('product');
$productId = (is_numeric($productSlug)) ? $productSlug : Data::getProductIDBySlug($productSlug);
// $product = (Data::doesProductBelongOnSite($productId)) ? Data::getProduct($productId) : null;
$product = Data::getProduct($productId);

if(!$product) {
	try {
		$parentCategoryPage = Data::parentCategoryPage();
		wp_redirect($parentCategoryPage, 302);
		exit();
	} catch(\Exception $e) {
		// dd($e->getMessage());
		wp_redirect(get_site_url());
	}
	exit();
}

$productImage = cdnLink($product->remote_image_path, 400);
if(array_key_exists('slmk_breadcrumbs', $_SESSION)) {
	$context['breadcrumbs'] = $_SESSION['slmk_breadcrumbs'];
}
$context['product'] = $product;
$context['body_class'] = "{$context['body_class']} product-page";

// Shopping URLs
$context['netsuite_addcart_base_url'] = CarbonFields::get('netsuite_addcart_base_url');

// Structured data filters
$productUrl = get_site_url().productPage($product);
add_filter( 'wpseo_schema_webpage', function($data) use ($product, $productUrl) {
	$data['url'] = $productUrl;
	$data['potentialAction'][0]['target'] = $productUrl;
	$data['name'] = $product->name;
	if($product->last_remote_update) $data['dateModified'] = $product->last_remote_update;
    return $data;
});
add_filter( 'wpseo_schema_graph_pieces', function($pieces, $context) use ($product) {
	$pieces[] = new ProductPiece($product);
	return $pieces;
}, 11, 2 );

// Open Graph additions
adjust_og_filter('url', $productUrl);
adjust_og_filter('title', $product->name);
adjust_og_filter('type', 'product');
adjust_og_filter('description', 'product');
add_og_meta('image', $productImage);
removeYoastOG();

Timber::render( array( 'product.twig' ), $context );
