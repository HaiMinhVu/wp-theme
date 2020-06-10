<?php
/**
 * Timber starter-theme
 * https://github.com/timber/starter-theme
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

 use SellmarkTheme\Data;
 use SellmarkTheme\CarbonFields;
 use Timber\Site as TimberSite;

session_start();

function get_template_file() {
	return basename(get_page_template());
}

function is_template_file($templateString) : bool {
	return $templateString == get_template_file();
}

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


class StarterSite extends TimberSite {
	/** Add timber support. */
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_filter( 'timber/context', array( $this, 'add_to_context' ) );
		add_filter( 'timber/twig', array( $this, 'add_to_twig' ) );
		add_action( 'init', array($this, 'show_shopping_counter') );
		// add_action( 'init', array( $this, 'register_post_types' ) );
		// add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array($this, 'add_rewrites') );
        add_action( 'init', array($this, 'add_shortcodes') );
        add_action( 'get_header', array($this, 'clear_breadcrumbs') );
        add_action( 'rest_api_init', array($this, 'register_routes') );
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
    $context['footer_links'] = Data::getThemeOption('footer_links', 'theme_option');
    $context['phone_numbers'] = Data::getThemeOption('phone_numbers', 'theme_option');
    $context['slmk_brand_color'] = CarbonFields::get('slmk_brand_color');
    $context['slmk_brand_color_rgba'] = carbon_hex_to_rgba($context['slmk_brand_color']);
    $context['slmk_brand_color_hsl'] = rgbToHsl($context['slmk_brand_color_rgba']['red'], $context['slmk_brand_color_rgba']['green'], $context['slmk_brand_color_rgba']['blue']);
    $context['slmk_site_brand'] = ucwords(CarbonFields::get('slmk_site_brand'));
    foreach(['slmk_site_favicon', 'slmk_site_logo'] as $imageSetting) {
        $context[$imageSetting] = (new Timber\Image(Data::getSetting($imageSetting)))->src;
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
			// print_r($e->getMessage());
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

	/** This is where you can add your own functions/filters to twig.
	 *
	 * @param string $twig get extension.
	 */
	public function add_to_twig( $twig ) {
		$twig->addExtension(new Twig\Extension\StringLoaderExtension());
		$twig->addFilter(new Timber\Twig_Filter( 'product_page', function($product){
			return productPage($product);
		}));
        $twig->addFilter(new Timber\Twig_Filter( 'format_currency', function( $price ) {
            $dollarAmount = number_format($price, 2, '.', ',');
            return "\${$dollarAmount}";
        }));

        $twig->addFilter(new Timber\Twig_Filter('img_src', function($id) {
        	return (new Timber\Image($id))->src;
        }));

        $twig->addFilter(new Timber\Twig_Filter('only_numbers', function($str) {
	        return preg_replace('/\D/', '', $str);
	    }));

        $twig->addFunction( new Timber\Twig_Function('in_stock', function($bool){
        	return inStock($bool);
        }));
		$twig->addFunction( new Timber\Twig_Function('print_r', 'print_r'));
		$twig->addFunction( new Timber\Twig_Function('dd', function($var) {
				echo '<pre>';
				print_r($var);
				echo '</pre>';
				exit();
		}));

        $twig->addFilter( new Timber\Twig_Filter('cdn_link', function($filename, $imageWidth = null, $additionalOptions = []){
        	return cdnLink($filename, $imageWidth, $additionalOptions);
        }));

        $twig->addFilter( new Timber\Twig_Filter('file_link', function($filename){
        	return fileLink($filename);
        }));

		return $twig;
	}

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
				$product->in_stock_html = inStock($product->in_stock);
				return $product;
			}, Data::getProducts());
			return json_encode($products);
		});
	}

}

function getSLMKForm($atts) {
	$context = Timber::context();
	$form = Data::form($atts['id']);
	$context['form'] = $form;
	$requiredArray = array_filter($form->fields, function($field){
		return $field->required;
	});
	$context['has_required_fields'] = (count($requiredArray) > 0);
	$context['brand_slug'] = CarbonFields::get('slmk_site_brand');
	$endpoint = (CarbonFields::get('slmk_api_form_endpoint') != '') ? CarbonFields::get('slmk_api_form_endpoint') : CarbonFields::get('slmk_api_endpoint');
	$context['slmk_api_form_endpoint'] = $endpoint;
	return Timber::compile('partial/form-render.twig', $context);
}

function inStock($bool) {
    if($bool) {
        return '<span class="text-success">In Stock</span>';
    } else {
        return '<span class="text-danger">Not in Stock</span>';
    }
}

function productPage( $product ) {
    return '/products/'.$product->nsid.'/'.sanitize_title($product->name);
}

function cdnLink($filename, $imageWidth = null, $additionalOptions = []){
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
}

function fileLink($filename) {
	$url = parse_url(CarbonFields::get('slmk_api_endpoint'));
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

new StarterSite();

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
