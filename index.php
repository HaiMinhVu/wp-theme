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

use Pulsar\Data;

$featuredProducts = Data::getFeaturedProducts(143);
$sliderImages = Data::sliderImages();

// dd(carbon_get_theme_option('slmk_site_brand'));

$context = Timber::context();
$context['featured_products'] = $featuredProducts;
$context['sliders'] = $sliderImages;
$context['blog_posts'] = Timber::get_posts(['posts_per_page' => 4, 'page' => 1, 'orderby' => 'publish_date']);
Timber::render('index.twig', $context);
