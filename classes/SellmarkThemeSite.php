<?php

namespace SellmarkTheme;

use Timber\{
    Image,
    Menu,
    Site,
    Twig_Filter,
    Twig_Function
};
use Twig\Extension\StringLoaderExtension;

class SellmarkThemeSite extends Site {
    /** Add timber support. */
    public function __construct() {
        parent::__construct();
        add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
        add_filter( 'timber/context', array( $this, 'add_to_context' ) );
        add_filter( 'timber/twig', array( $this, 'add_to_twig' ) );
        add_action( 'init', array($this, 'show_shopping_counter') );
        add_action( 'init', array($this, 'set_additional_debug_settings') );
        add_action('init', array($this, 'add_to_sitemap'));
        add_action('template_redirect', array($this, 'add_dynamic_redirects') );
        add_action( 'init', array($this, 'add_rewrites') );
        add_action( 'init', array($this, 'add_shortcodes') );
        add_action( 'get_header', array($this, 'clear_breadcrumbs') );
        add_action( 'rest_api_init', array($this, 'register_routes') );
        add_action( 'wp_enqueue_scripts', array($this, 'add_theme_scripts') );
    }

    public function add_to_sitemap() {
        Sitemap::init();
    }

    public function add_dynamic_redirects() {
        Redirection::init([
            'product_path' => CarbonFields::get('slmk_redirect_product_path'),
            'product_var' => CarbonFields::get('slmk_redirect_product_var')
        ]);
    }

    /** Add global context
    *
    * @param string $context
    */
    public function add_to_context( $context ) {
        $context['menu']  = new Menu();
        $context['site']  = $this;
        $context['product_categories'] = Data::productCategories();
        $context['nav_items'] = $this->navItems();
        $context['footer_links'] = Data::getThemeOption('footer_links', 'theme_option');
        $context['addresses'] = Data::getThemeOption('addresses', 'theme_option');
        $context['phone_numbers'] = Data::getThemeOption('phone_numbers', 'theme_option');
        $context['slmk_brand_color'] = CarbonFields::get('slmk_brand_color');
        $context['slmk_brand_color_rgba'] = carbon_hex_to_rgba($context['slmk_brand_color']);
        $context['slmk_brand_color_hsl'] = rgbToHsl($context['slmk_brand_color_rgba']['red'], $context['slmk_brand_color_rgba']['green'], $context['slmk_brand_color_rgba']['blue']);
        $context['slmk_site_brand'] = ucwords(CarbonFields::get('slmk_site_brand'));
        $context['slmk_analytics'] = CarbonFields::get('slmk_analytics');
        foreach(['slmk_site_favicon', 'slmk_site_logo'] as $imageSetting) {
            $context[$imageSetting] = (new Image(Data::getSetting($imageSetting)))->src;
        }
        foreach(['facebook', 'youtube','twitter','instagram'] as $socialLink) {
            $context["{$socialLink}_link"] = CarbonFields::get($socialLink);
        }
        return $context;
    }

    public function clear_breadcrumbs() {
        try {
            $templateFile = get_template_file();
            if(array_key_exists('slmk_breadcrumbs', $_SESSION) && !in_array($templateFile, ['product'])) {
                $context = Timber::context();
                unset($context['breadcrumbs']);
                unset($_SESSION['slmk_breadcrumbs']);
            }
        } catch(\Exception $e) {
            //
        }
    }

    public function theme_supports() {
        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support(
            'html5',
            array(
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            )
        );
        add_theme_support(
            'post-formats',
            array(
                'aside',
                'image',
                'video',
                'quote',
                'link',
                'gallery',
                'audio',
            )
        );
        add_theme_support( 'menus' );
    }

    public function show_shopping_counter() {
        if(!empty($_GET['show_shopping_counter'])) {
            include(get_template_directory().'/shopping-counter.php');
            exit();
        }
    }

    /** Add twig functions and filters
    *
    * @param string $twig get extension.
    */
    public function add_to_twig( $twig ) {
        $twig->addExtension(new StringLoaderExtension());
        $twig->addFilter(new Twig_Filter( 'product_page', function($product){
            return productPage($product);
        }));
        $twig->addFilter(new Twig_Filter( 'format_currency', function( $price ) {
            return number_format($price, 2, '.', ',');
        }));

        $twig->addFilter(new Twig_Filter('img_src', function($id) {
            return (new Timber\Image($id))->src;
        }));

        $twig->addFilter(new Twig_Filter('uglify_string', function($string) {
            $string = preg_replace("/\s{2,}/", " ", $string);
            $string = str_replace("\n", "", $string);
            $string = str_replace('@CHARSET "UTF-8";', "", $string);
            $string = str_replace(', ', ",", $string);
            return trim($string);
        }));

        $twig->addFilter(new Twig_Filter('only_numbers', function($str) {
            return preg_replace('/\D/', '', $str);
        }));

        $twig->addFunction( new Twig_Function('in_stock', function($bool, $allow_backorders = false){
            return inStock($bool, $allow_backorders);
        }));
        $twig->addFunction( new Twig_Function('allow_purchase', function($bool, $allow_backorders = false){
            return allowPurchase($bool, $allow_backorders);
        }));
        $twig->addFunction( new Twig_Function('print_r', 'print_r'));
        $twig->addFunction( new Twig_Function('dd', function($var) {
            echo '<pre>';
            print_r($var);
            echo '</pre>';
            exit();
        }));

        $twig->addFilter( new Twig_Filter('cdn_link', function($filename, $imageWidth = null, $additionalOptions = []){
            return cdnLink($filename, $imageWidth, $additionalOptions);
        }));

        $twig->addFilter( new Twig_Filter('file_link', function($filename){
            return fileLink($filename);
        }));

        return $twig;
    }

    /** Add rewrite rules for custom external data
    *
    */
    public function add_rewrites() {
        $productPageID = Data::cacheRemember('product-page-id', function() {
            return getTemplatePageId('product');
        });

        add_rewrite_rule(
            '^products/([a-z_\-0-9]+)/?',
            'index.php?page_id='.$productPageID.'&product=$matches[1]',
            'top'
        );
        add_rewrite_tag('%product%','([a-z_\-0-9]+)');

        $categoryPageID = Data::cacheRemember('category-page-id', function() {
            return getTemplatePageId('category');
        });

        add_rewrite_rule(
            '^category/([a-z_\-0-9]+)/?',
            'index.php?page_id='. $categoryPageID .'&category=$matches[1]',
            'top'
        );
        add_rewrite_tag('%category%','([a-z_\-0-9]+)');
    }

    public function add_theme_scripts() {
        wp_enqueue_script('script', get_template_directory_uri() . '/static/main.js');
    }

    public function add_shortcodes() {
        add_shortcode('registration_form', [$this, 'addRegistrationFormShortcode']);
        add_shortcode('contact_form', [$this, 'addContactFormShortcode']);
        add_shortcode('slmk_form', 'getSLMKForm');
    }

    public function addRegistrationFormShortcode() {
        $context = Timber::context();
        $context['product_select_list'] = array_map(function($product){
            return [
                'id' => $product->id,
                'name' => $product->name
            ];
        }, Data::getProducts2());
        $context['post_url'] = Data::getPublicUrl('product/registration');
        return Timber::compile( 'partial/registration.twig', $context );
    }

    public function addContactFormShortcode() {
        $context = Timber::context();
        $context['post_url'] = Data::getPublicUrl('contact');
        return Timber::compile( 'partial/contact.twig', $context );
    }

    private function navItems() {
        $items = Data::getThemeOption('menu_items', 'theme_option');
        $productCategories = Data::productCategories();
        if(count($productCategories) > 0) {
            $categoryDropdown =  [
                'name' => 'Products',
                'link' => '/products',
                'children' => array_map(function($category){
                    return [
                        'name' => $category->label,
                        'link' => "/category/{$category->id}"
                    ];
                }, Data::productCategories())
            ];
            $items[] = $categoryDropdown;
        }
        return $items;
    }

    public function register_routes() {
        register_rest_route('slmk', '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'getProducts']
        ]);
    }

    public function getProducts() {
        return Data::cacheRemember('api_products_list',function(){
            $products = array_map(function($product){
                $product->image = cdnLink($product->remote_image_path, 300);
                $product->url = productPage($product);
                $product->in_stock_html = inStock($product->in_stock, $product->allow_backorders);
                return $product;
            }, Data::getProducts());
            return json_encode($products);
        });
    }

    public function add_categories_to_sitemap() {
        $pages = [];
        $pages[] = $this->addCategoriesToSitemap();
        return implode('', $pages);
    }

    private function addCategoriesToSitemap() {
        $categoriesArr = array_map(function($category){
            $location = get_bloginfo('url').'/category/'.$category->id;
            $lastModified = '2020-06-30T16:53:52+00:00';
            $image = false;
            if($image_path = $category->remote_path) {
                $image = cdnLink($image_path, 400);
            }
            return $this->getSitemapString($location, $lastModified, $image);
        }, Data::getCategories());
        return implode('', $categoriesArr);
    }

    public function add_pages_to_sitemap() {
        $pages = [];
        $pages[] = $this->addProductPagesToSitemap();
        return implode('', $pages);
    }

    private function addProductPagesToSitemap() {
        $productPagesArr = array_map(function($product){
            return $this->getProductPageSitemapString($product);
        }, Data::getProducts());
        return implode('', $productPagesArr);
    }

    private function getProductPageSitemapString($product) {
        $location = get_bloginfo('url').productPage($product);
        $lastModified = $product->last_remote_update ? $product->last_remote_update : '2020-06-30T16:53:52+00:00';
        $image = false;
        if($image_path = $product->remote_image_path) {
            $image = cdnLink($image_path, 400);
        }
        return $this->getSitemapString($location, $lastModified, $image);
    }

    private function getSitemapString($location, $lastModified, $image = false) {
        $s = "<url>";
        $s .= "<loc>{$location}</loc>";
        $s .= "<lastmod>{$lastModified}</lastmod>";
        if($image) {
            $s .= "<image:image>
                     <image:loc>{$image}</image:loc>
                   </image:image>";
        }
        $s .= "</url>";
        return $s;
    }

    public function set_additional_debug_settings() {
        if(WP_DEBUG) {
            add_filter( 'yoast_seo_development_mode', '__return_true' );
        }
    }

}