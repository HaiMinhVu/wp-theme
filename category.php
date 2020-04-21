<?php
/**
 * Template Name: Category list view page
 * The template for displaying products
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

use Pulsar\Data;

$categoryId = get_query_var('category');

$context = Timber::context();
$context['category'] = Data::getCategoryById($categoryId);
$context['products'] = Data::getProductsByCategoryId($categoryId);
$context['sub_categories'] = Data::getSubCategories($categoryId);
$context['category_id'] = $categoryId;
$context['breadcrumbs'] = Data::getCategoryBreadcrumbs($categoryId);

Timber::render( array( 'category.twig' ), $context );
