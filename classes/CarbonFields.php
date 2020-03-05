<?php 

namespace Pulsar;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Carbon_Fields\Carbon_Fields;

class CarbonFields {

	const DEFAULTS = [
        'slmk_api_key' => 'Y4=nsSabrJ6C8q-6XvYVMp6zDX@BYPnFmPP2k7$G%txKm%@5X4ku5rJE2ap?ZwyZYjcn^8BJZ*P7y@hPwp+r$@KMTfynkXP-a98DRBYGH%AU^?!R6+SM7?S8aRM?v_TK',
        'slmk_site_brand' => 'pulsar',
        'slmk_api_endpoint' => 'https://api.slmk.dev/v1',
        // 'slmk_api_endpoint' => 'apidev/v1',
        'slmk_home_slider' => 257,
        'slmk_featured_products' => 105,
        'slmk_site_logo' => 5486,
        'slmk_site_favicon' => 548
    ];

	public function __construct() 
	{
		add_action('after_setup_theme', [$this, 'crb_load']);
		add_action('carbon_fields_register_fields', [$this, 'register_sellmark_fields']);
		// add_action('carbon_fields_theme_options_container_saved', [$this, 'generate_env_file']);
	}

	public function register_sellmark_fields()
	{
		$sliders = [];
		foreach(Data::sliders() as $slider) {
			$sliders[$slider->id] = $slider->description;
		}

		$brands = [];
		foreach(Data::brands() as $brand) {
			$brands[$brand->slug] = $brand->name;
		}

		$featuredProducts = [];
		foreach(Data::featuredProducts() as $product) {
			$featuredProducts[$product->id] = $product->description;
		}

		Container::make('theme_options', __( 'Theme Options', 'crb' ))
			->add_fields([
				Field::make( 'select', 'slmk_site_brand', 'Site Brand' )->add_options($brands),
				Field::make( 'image', 'slmk_site_favicon', __( 'Site Favicon' ) ),
				Field::make( 'image', 'slmk_site_logo', __( 'Site Logo' ) ),
				Field::make( 'text', 'slmk_api_endpoint', 'Sellmark API Endpoint' ),
				Field::make( 'textarea', 'slmk_api_key', 'Sellmark API Key' )->set_rows(2),
				Field::make( 'select', 'slmk_home_slider', 'Home Slider' )->add_options($sliders),
				Field::make( 'select', 'slmk_featured_products', 'Featured Products' )->add_options($featuredProducts),
			]);
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

	public static function get($option) : string
	{
		$optionValue = get_option("_{$option}");
		return $optionValue ? $optionValue : self::getDefault($option);
	}

	public function generate_env_file()
	{
		//
	}

}