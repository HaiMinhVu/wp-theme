<?php
/**
 * Template Name: Support Template
 * The template for displaying forms and lists of forms
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.2
 */

global $post;
$args = [
    'orderby' => 'menu_order',
    'order' => 'ASC',
    'post_status' => 'publish'
];

if($post->post_parent == 0) {
    $args['post_parent'] = $post->ID;
    $children = array_values(get_children($args));
    if(count($children) > 0) {
        $redirectUrl = get_permalink($children[0]->ID);
    } else {
        $redirectUrl = get_site_url();
    }
    wp_redirect($redirectUrl);
    exit();
}

$args['post_parent'] = $post->post_parent;
$children = array_values(get_children($args));

$sideMenuItems = array_map(function($child){
    return [
        'permalink' => get_permalink($child->ID),
        'title' => $child->post_title
    ];
}, $children);

$context = Timber::context();
$context['side_menu_items'] = $sideMenuItems;
$context['parent_title'] = (get_post($post->post_parent))->post_title;
$context['permalink'] = get_the_permalink();
$context['post'] = new Timber\Post($post->ID);

// $context['content'] = apply_filters( 'the_content', $post->post_content );
Timber::render( 'support.twig', $context );
