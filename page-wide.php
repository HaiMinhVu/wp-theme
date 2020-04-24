<?php
/**
 * Template Name: Wide Template
 * The template for displaying wide format pages
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.2
 */

$context = Timber::context();

$timber_post     = new Timber\Post();
$context['post'] = $timber_post;
Timber::render('page-wide.twig', $context);
