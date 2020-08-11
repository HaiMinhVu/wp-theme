<?php
/**
 * Template Name: Category list view page
 * The template for displaying products
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

use SellmarkTheme\Data;
use SellmarkTheme\Yoast\Schema\Piece\ItemListSimple as ItemListSimplePiece;


$categoryId = get_query_var('category');

$context = Timber::context();
$category = Data::getCategory($categoryId);
$context['category'] = $category;
$products = Data::getProductsByCategoryId($categoryId);
$context['products'] = Data::getProductsByCategoryId($categoryId);
$context['sub_categories'] = Data::getSubCategories($categoryId);
$context['category_id'] = $categoryId;
$breadcrumbs = Data::getCategoryBreadcrumbs($categoryId);
$context['breadcrumbs'] = $breadcrumbs;
$_SESSION['slmk_breadcrumbs'] = $breadcrumbs;

$categoryUrl = Data::categoryPage($category);

add_filter( 'wpseo_schema_webpage', function($data) use ($category, $categoryUrl) {
	$data['url'] = $categoryUrl;
	$data['potentialAction'][0]['target'] = $categoryUrl;
	$data['name'] = $category->label;
	if(property_exists($category, 'last_update') && $category->last_update) $data['dateModified'] = $category->last_update;
    return $data;
});
add_filter( 'wpseo_schema_graph_pieces', function($pieces, $context) use ($products) {
    $items = array_map(function($product){
        return ['url' => Data::productPageUrl($product)];
    }, $products);

	$pieces[] = new ItemListSimplePiece($items);
	return $pieces;
}, 11, 2 );

// Open Graph additions
removeYoastOG();
adjust_og_filter('url', $categoryUrl);
adjust_og_filter('title', $category->label);
adjust_og_filter('type', 'og:product');
adjust_og_filter('description', 'product');
add_og_meta('image', Data::cdnLink($category->remote_path, 400));

Timber::render( array( 'category.twig' ), $context );
