<?php
/**
* Sellmark Theme
* https://git.slmk.dev/Sellmark/sellmark_wordpress_theme_2020
*/

require_once(__DIR__.'/setup_timber.php');

use SellmarkTheme\{
    CarbonFields,
    Data,
    SellmarkThemeSite,
    Sitemap
};

function removeYoastOG() {
    add_filter( 'wpseo_opengraph_url' , '__return_false' );
    add_filter( 'wpseo_opengraph_desc', '__return_false' );
    add_filter( 'wpseo_opengraph_title', '__return_false' );
    add_filter( 'wpseo_opengraph_type', '__return_false' );
    add_filter( 'wpseo_opengraph_site_name', '__return_false' );
    add_filter( 'wpseo_opengraph_image' , '__return_false' );
    add_filter( 'wpseo_og_og_image_width' , '__return_false' );
    add_filter( 'wpseo_og_og_image_height' , '__return_false' );
    add_filter( 'wpseo_opengraph_author_facebook' , '__return_false' );
}

function sitemap_exclude_post( $url, $type, $post) {
    if($type == 'term') return false;
    if(in_array($post->post_name, ['category', 'product'])) return false;

	return $url;
}
add_filter( 'wpseo_sitemap_entry', 'sitemap_exclude_post', 20, 3 );

function getSLMKForm($atts) {
    $context = Timber::context();
    $form = Data::form($atts['id']);
    $context['form'] = $form;
    $requiredArray = array_filter($form->fields, function($field){
        return $field->required;
    });
    $context['has_required_fields'] = (count($requiredArray) > 0);
    $context['brand_slug'] = Data::getSetting('slmk_site_brand');
    $endpoint = (Data::getSetting('slmk_api_form_endpoint') != '') ? Data::getSetting('slmk_api_form_endpoint') : Data::getSetting('slmk_api_endpoint');
    $context['slmk_api_form_endpoint'] = $endpoint;
    return Timber::compile('partial/form-render.twig', $context);
}

function inStock($bool, $allow_backorders = false) {
    if($bool) {
        return '<span class="text-success">In Stock</span>';
    } elseif($allow_backorders) {
        return '<span class="text-warning">Backordered</span>';
    }else {
        return '<span class="text-danger">Not in Stock</span>';
    }
}

function allowPurchase($bool, $allow_backorders = false) {
    return ($bool || $allow_backorders);
}

function productPage($product) {
    return Data::productPage($product);
}

function cdnLink($filename, $imageWidth = null, $additionalOptions = []){
    return Data::cdnLink($filename, $imageWidth, $additionalOptions);
}

function fileLink($filename) {
    $url = parse_url(Data::getSetting('slmk_api_endpoint'));
    return "{$url['scheme']}://{$url['host']}/file/{$filename}";
}

function getTemplatePageId($templateName) {
    $page = get_pages(array(
        'meta_key' => '_wp_page_template',
        'meta_value' => "{$templateName}.php",
        'number' => 1
    ));

    return (count($page) > 0) ? $page[0]->ID : null;
}

function adjust_og_filter($filter, $value) {
    add_filter("wpseo_opengraph_{$filter}", function($wpseo_filter_value) use ($value) {
        return $value;
    }, 10, 1 );
}

function add_og_meta($type, $content = false) {
    add_action('wp_head', function() use ($type, $content) {
        if($type && $content):
            ?>
            <meta property="og:<?= $type ?>" content="<?= $content; ?>"/>
            <?php
        endif;
    }, 1);
}

new SellmarkThemeSite;

new CarbonFields;

function dd($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
    exit();
}

function rgbToHsl( $r, $g, $b ) {
    $oldR = $r;
    $oldG = $g;
    $oldB = $b;

    $r /= 255;
    $g /= 255;
    $b /= 255;

    $max = max( $r, $g, $b );
    $min = min( $r, $g, $b );

    $h;
    $s;
    $l = ( $max + $min ) / 2;
    $d = $max - $min;

    if( $d == 0 ){
        $h = $s = 0; // achromatic
    } else {
        $s = $d / ( 1 - abs( 2 * $l - 1 ) );

        switch( $max ){
            case $r:
            $h = 60 * fmod( ( ( $g - $b ) / $d ), 6 );
            if ($b > $g) {
                $h += 360;
            }
            break;

            case $g:
            $h = 60 * ( ( $b - $r ) / $d + 2 );
            break;

            case $b:
            $h = 60 * ( ( $r - $g ) / $d + 4 );
            break;
        }
    }

    $lightness = round(round( $l, 2 )*100, 2);

    if($lightness >= 50) {
        $hoverLightness = $lightness-20;
    } else {
        $hoverLightness = $lightness+20;
    }

    return [
        'hue' => round( $h, 2 ),
        'saturation' => round(round( $s, 4 )*100, 2),
        'lightness' => round(round( $l, 2 )*100, 2),
        'cssHoverLightness' => $hoverLightness
    ];

}
