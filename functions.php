<?php
/**
 * Timber starter-theme
 * https://github.com/timber/starter-theme
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

 use Pulsar\Data;
 // use Twig\Extra\Intl\IntlExtension;

 const SITE_PREFIX = 'pulsar';




/**
 * If you are installing Timber as a Composer dependency in your theme, you'll need this block
 * to load your dependencies and initialize Timber. If you are using Timber via the WordPress.org
 * plug-in, you can safely delete this block.
 */
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
	$timber = new Timber\Timber();
}

/**
 * This ensures that Timber is loaded and available as a PHP class.
 * If not, it gives an error message to help direct developers on where to activate
 */
if ( ! class_exists( 'Timber' ) ) {

	add_action(
		'admin_notices',
		function() {
			echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
		}
	);

	add_filter(
		'template_include',
		function( $template ) {
			return get_stylesheet_directory() . '/static/no-timber.html';
		}
	);
	return;
}

/**
 * Sets the directories (inside your theme) to find .twig files
 */
Timber::$dirname = array( 'templates', 'views' );

/**
 * By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
 * No prob! Just set this value to true
 */
Timber::$autoescape = false;


/**
 * We're going to configure our theme inside of a subclass of Timber\Site
 * You can move this to its own file and include here via php's include("MySite.php")
 */
class StarterSite extends Timber\Site {
	/** Add timber support. */
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_filter( 'timber/context', array( $this, 'add_to_context' ) );
		add_filter( 'timber/twig', array( $this, 'add_to_twig' ) );
		add_action( 'init', array($this, 'show_shopping_counter') );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array($this, 'add_rewrites') );
        add_action( 'init', array($this, 'add_shortcodes') );
        add_action( 'wp_enqueue_scripts', array($this, 'add_theme_scripts') );
		parent::__construct();
	}
	/** This is where you can register custom post types. */
	public function register_post_types() {

	}
	/** This is where you can register custom taxonomies. */
	public function register_taxonomies() {

	}

	/** This is where you add some context
	 *
	 * @param string $context context['this'] Being the Twig's {{ this }}.
	 */
	public function add_to_context( $context ) {
		$context['menu']  = new Timber\Menu();
		$context['site']  = $this;
		$context['product_categories'] = Data::productCategories();
        $context['nav_items'] = $this->navItems();
		return $context;
	}

	public function theme_supports() {
		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		// add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			)
		);

		/*
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		 */
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

	/** This Would return 'foo bar!'.
	 *
	 * @param string $text being 'foo', then returned 'foo bar!'.
	 */
	public function myfoo( $text ) {
		$text .= ' bar!';
		return $text;
	}

	public function show_shopping_counter() {
		if(!empty($_GET['show_shopping_counter'])) {
		    include(get_template_directory().'/shopping-counter.php');
		    exit();
		}
	}

	/** This is where you can add your own functions to twig.
	 *
	 * @param string $twig get extension.
	 */
	public function add_to_twig( $twig ) {
		$twig->addExtension(new Twig\Extension\StringLoaderExtension());
        // $twig->addExtension(new IntlExtension());
		$twig->addFilter(new Timber\Twig_Filter( 'product_page', function( $product ) {
	        return '/products/'.$product->nsid.'/'.sanitize_title($product->name);
	    }));
        $twig->addFilter(new Timber\Twig_Filter( 'format_currency', function( $price ) {
            $dollarAmount = number_format($price, 2, '.', ',');
            return "\${$dollarAmount}";
        }));

        $twig->addFunction( new Timber\Twig_Function('in_stock', function($bool){
            if($bool) {
                return '<span class="text-success">In Stock</span>';
            } else {
                return '<span class="text-danger">Not in Stock</span>';
            }
        }));
		$twig->addFunction( new Timber\Twig_Function('print_r', 'print_r'));
		$twig->addFunction( new Timber\Twig_Function('dd', function($var) {
				echo '<pre>';
				print_r($var);
				echo '</pre>';
				exit();
		}));

        $twig->addFilter( new Timber\Twig_Filter('cdn_link', function($filename, $imageWidth = null){
            // return $imageWidth;
            $options = [
                "bucket" => "sellmark-media-bucket",
                "key" => $filename
            ];
            if($imageWidth) {
                $options['edits'] = [
                    'resize' => [
                        'width' => $imageWidth,
                        'fit' => 'contain'
                    ]
                ];
            }
            $options = base64_encode(json_encode($options));
            return "https://d4ursusm8s4tk.cloudfront.net/{$options}";
        }));
		return $twig;
	}

	public function add_rewrites() {
		add_rewrite_rule(
	        '^products/([a-z_\-0-9]+)/?',
	        'index.php?page_id=5&product=$matches[1]',
	        'top'
	    );
		add_rewrite_tag('%product%','([a-z_\-0-9]+)');

		add_rewrite_rule(
	        '^category/([a-z_\-0-9]+)/?',
	        'index.php?page_id=7&category=$matches[1]',
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
        return [
            [
                'name' => 'About Us',
                'link' => '/about-us'
            ],
            [
                'name' => 'Support',
                'link' => '/support'
            ],
            [
                'name' => 'Press &amp; Blogs',
                'link' => '/blog'
            ],
            [
                'name' => 'Contact',
                'link' => '/contact'
            ],
            [
                'name' => 'Products',
                'link' => '/products',
                'children' => array_map(function($category){
                    return [
                        'name' => $category->label,
                        'link' => "/category/{$category->id}"
                    ];
                }, Data::productCategories())
            ]
        ];
    }

}

new StarterSite();

function dd($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
    exit();
}

// add_action('init', 'pulsar_product2_rewrite');
// function pulsar_product2_rewrite() {
//     add_rewrite_rule(
//         '^products/([\w]+)/([\w]+)/([0-9]+)/?',
//         'index.php?page_id=21&product=$matches[3]',
//         'top'
//     );
// 	add_rewrite_tag('%product%','([0-9]+)');
// }
//
// add_action('init', 'pulsar_product_rewrite');
// function pulsar_product_rewrite() {
//     add_rewrite_rule(
//         '^products/([\w]+)/([0-9]+)/?',
//         'index.php?page_id=21&product=$matches[2]',
//         'top'
//     );
// 	add_rewrite_tag('%product%','([0-9]+)');
// }



// function custom_query_vars_filter($vars) {
//   $vars[] .= 'product';
//   return $vars;
// }
// add_filter( 'query_vars', 'custom_query_vars_filter', 1 );
