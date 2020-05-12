<?php
/**
 * Template Name: Landing Page
 * The template for creating landing pages
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::context();

$timber_post     = new Timber\Post();
$context['post'] = $timber_post;
Timber::render( 'landing-page.twig', $context );