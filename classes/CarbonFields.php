<?php 

namespace SellmarkTheme;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Carbon_Fields\Carbon_Fields;

class CarbonFields {

	const DEFAULTS = [
        'slmk_api_key' => 'Y4=nsSabrJ6C8q-6XvYVMp6zDX@BYPnFmPP2k7$G%txKm%@5X4ku5rJE2ap?ZwyZYjcn^8BJZ*P7y@hPwp+r$@KMTfynkXP-a98DRBYGH%AU^?!R6+SM7?S8aRM?v_TK',
        'slmk_site_brand' => 'pulsar',
        'slmk_api_endpoint' => 'https://api.slmk.dev/v1',
        'slmk_home_slider' => 257,
        'slmk_featured_products' => 105,
        'slmk_site_logo' => 5486,
        'slmk_site_favicon' => 548
    ];

	public function __construct() 
	{
		add_action('after_setup_theme', [$this, 'crb_load']);
		add_action('carbon_fields_register_fields', [$this, 'register_fields']);
	}

	public function register_fields()
	{
		Container::make('theme_options', __( 'Theme Options', 'crb' ))
		    ->add_tab(__( 'Global' ), $this->globalSettingsFields())
		    ->add_tab(__( 'Homepage' ), $this->homepageFields())
		    ->add_tab(__( 'Menu' ), $this->menuFields())
		    ->add_tab(__('Footer'), $this->footerFields())
		    ->add_tab(__('Social'), $this->socialFields())
		    ->add_tab(__('Developer'), $this->developerFields());
	}

	private function globalSettingsFields() : array
	{
		$brands = [];
		foreach(Data::brands() as $brand) {
			$brands[$brand->slug] = $brand->name;
		}
		return [
			Field::make( 'select', 'slmk_site_brand', 'Site Brand' )->add_options($brands),
			Field::make( 'color', 'slmk_brand_color', __( 'Site Primary Brand Color' ) )->set_alpha_enabled( true ),
			Field::make( 'image', 'slmk_site_favicon', __( 'Site Favicon' ) ),
			Field::make( 'image', 'slmk_site_logo', __( 'Site Logo' ) )
		];
	}

	private function homepageFields()
	{
		$sliders = [];
		foreach(Data::sliders() as $slider) {
			$sliders[$slider->id] = $slider->description;
		}

		$featuredProducts = [];
		foreach(Data::featuredProducts() as $product) {
			$featuredProducts[$product->id] = $product->description;
		}

		return [
			Field::make( 'select', 'slmk_home_slider', 'Home Slider' )->add_options($sliders),
			Field::make( 'select', 'slmk_featured_products', 'Featured Products' )->add_options($featuredProducts),
			Field::make( 'complex', 'interested_becoming_dealer', __( 'Interested in Becoming a Dealer Section' ))
				->add_fields( array(
					Field::make( 'image', 'image', __( 'Background Image' ) ),
					Field::make( 'text', 'title', __( 'Title' )),
					Field::make( 'text', 'sub_title', __( 'Subtitle' )),
					Field::make( 'text', 'button_text', __( 'Button Text' ) )->set_default_value('Learn More'),
					Field::make( 'text', 'button_link', __( 'Button Link' ) )			    
				))->set_min(1)->set_max(1)->set_duplicate_groups_allowed(false),
			Field::make( 'complex', 'who_we_are', __( 'Who We Are Section' ) )
			    ->add_fields( array(
					Field::make( 'image', 'image', __( 'Image' ) ),
					Field::make( 'text', 'title', __( 'Title' ) ),
					Field::make( 'rich_text', 'text', __( 'Text' ) ),
					Field::make( 'text', 'button_text', __( 'Button Text' ) )->set_default_value('Learn More'),
					Field::make( 'text', 'button_link', __( 'Button Link' ) )
			    ))->set_min(1)->set_max(1)->set_duplicate_groups_allowed(false),
			Field::make( 'complex', 'category_callout', __( 'Category Callout' ) )
				->add_fields(array_map(function($direction){
					$directionTitle = ucfirst($direction);
					return Field::make( 'complex', $direction, __( "{$directionTitle} Callout" ) )
					    ->add_fields( array(
							Field::make( 'image', 'image', __( 'Image' ) ),
							Field::make( 'text', 'text', __( 'Text' ) ),
							Field::make( 'text', 'button_text', __( 'Button Text' ) )->set_default_value('Learn More'),
							Field::make( 'text', 'button_link', __( 'Button Link' ) )
					    ))->set_min(1)->set_max(1)->set_duplicate_groups_allowed(false);
					}, ['left', 'right'])
				)
					
		];
	}

	private function menuFields() 
	{
		return [
			Field::make( 'complex', 'menu_items', __( 'Menu Items' ) )
				->add_fields([
					Field::make( 'text', 'name', __( 'Name' ) ),
					Field::make( 'text', 'link', __( 'Link' ) ),
					Field::make( 'complex', 'children', __( 'Menu Items' ) )
						->add_fields([
							Field::make( 'text', 'name', __( 'Name' ) ),
							Field::make( 'text', 'link', __( 'Link' ) ),
						])->set_max(10)
				])->set_max(4)
		];
	}

	private function footerFields()
	{
		return [
			Field::make( 'complex', 'phone_numbers', __( 'Phone Numbers' ) )
				->add_fields([
					Field::make( 'text', 'phone_number', __( 'Phone Number' ) )
						->set_attribute( 'placeholder', '(***) ***-****' )
					]),
			Field::make( 'complex', 'footer_links', __( 'Footer Links' ) )
				->add_fields([
					Field::make( 'text', 'name', __( 'Name' ) ),
					Field::make( 'text', 'link', __( 'Link' ) )
				])
		];
	}

	private function socialFields()
	{
		return array_map(
			function($socialTag){
				return Field::make('text', $socialTag, __( ucfirst($socialTag).' Link' ));
			}, ['facebook', 'youtube','twitter','instagram']);
	}

	private function developerFields()
	{
		return [
			Field::make( 'text', 'slmk_api_endpoint', 'Sellmark API Endpoint' ),
			Field::make( 'text', 'slmk_api_form_endpoint', 'Sellmark API Form Endpoint' )->set_help_text('If form API endpoint/version differs, specify here, defaults to Sellmark API Enpoint declared above'),
			Field::make( 'textarea', 'slmk_api_key', 'Sellmark API Key' )->set_rows(2)
		];
	}

	public function crb_load() 
	{
	    Carbon_Fields::boot();
	}

	private static function getDefault($option)
	{
		if(array_key_exists($option, self::DEFAULTS)) {
			return self::DEFAULTS[$option];
		}
		return '';
	}

	public static function get($option, $type = 'option') : ?string
	{
		if($type == 'option') {
			$optionValue = get_option("_{$option}");
			return $optionValue ? $optionValue : self::getDefault($option);
		} elseif ($type == 'theme_option') {
			return json_encode(carbon_get_theme_option($option));
		}
	}

	public function generate_env_file()
	{
		//
	}

}