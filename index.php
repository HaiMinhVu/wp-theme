<?php
/**
 * The main template file
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use SellmarkTheme\Data;

$featuredProducts = Data::getFeaturedProducts(143);
$sliderImages = Data::sliderImages();

$context = Timber::context();
$context['featured_products'] = $featuredProducts;
$context['sliders'] = $sliderImages;
$context['blog_posts'] = Timber::get_posts(['posts_per_page' => 4, 'page' => 1, 'orderby' => 'publish_date']);


$whoWeAre = Data::getThemeOption('who_we_are');
if($whoWeAre) $context['who_we_are'] = $whoWeAre[0];

$interestedDealer = Data::getThemeOption('interested_becoming_dealer');
if($interestedDealer) $context['interested_becoming_dealer'] = $interestedDealer[0];

if($categoryCallout = Data::getThemeOption('category_callout')) {
	$categoryCalloutData = [
		$categoryCallout[0]->left[0],
		$categoryCallout[0]->right[0],
	];
	$context['category_callout'] = $categoryCalloutData;
}

Timber::render('index.twig', $context);
