<?php
/**
 * Template Name: Product view page
 * The template for displaying products
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

use Pulsar\Data;

$context = Timber::context();
$productSlug = get_query_var('product');
$productId = (is_numeric($productSlug)) ? $productSlug : Data::getProductIDBySlug($productSlug);
$product = Data::getProduct($productId);
if(!$product) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( 404 );
	exit();
}
$context['product'] = $product;
$context['body_class'] = "{$context['body_class']} product-page";
Timber::render( array( 'product.twig' ), $context );
