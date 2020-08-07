<?php

namespace SellmarkTheme;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Carbon_Fields\Carbon_Fields;

class CarbonFields {

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
		    ->add_tab(__('Footer'), $this->footerFields())
		    ->add_tab(__('Social'), $this->socialFields())
			->add_tab(__('Store'), $this->storeFields())
			->add_tab(__('Newsletter'), $this->newsletterFields())
		    ->add_tab(__('Developer'), $this->developerFields());
	}

	private function globalSettingsFields() : array
	{
		$brands = [];
		foreach(Data::brands() as $brand) {
			$brands[$brand->slug] = $brand->name;
		}
		return [
			Field::make( 'select', 'slmk_site_brand', 'Site Brand' )->add_options($brands)->set_default_value('pulsar'),
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

	private function footerFields()
	{
		return [
			Field::make( 'complex', 'footer_links', __( 'Footer Links' ) )
				->add_fields([
					Field::make( 'text', 'name', __( 'Name' ) ),
					Field::make( 'text', 'link', __( 'Link' ) )
				]),
			Field::make( 'complex', 'addresses', __( 'Locations' ) )
				->add_fields([
					Field::make( 'text', 'name', __( 'Global Region' ) ),
					Field::make( 'complex', 'children', __( 'Addresses' ) )
						->add_fields([
							Field::make( 'textarea', 'name', __( 'Address' ) ),
							Field::make( 'complex', 'phone_numbers', __( 'Phone Numbers' ) )
								->add_fields([
									Field::make( 'select', 'phone_type', __('Type') )
										->add_options([
											'phone' => __('Phone'),
											'fax' => __('Fax')
										]),
									Field::make( 'text', 'phone_number', __( 'Phone Number' ) )
										->set_attribute( 'placeholder', '(***) ***-****' )
									]),
							Field::make( 'complex', 'emails', __( 'Email Addresses' ) )
								->add_fields([
									Field::make( 'text', 'email', __( 'Email Address' ) )
								])->set_max(3)
						])->set_max(3)
				])->set_max(2)
		];
	}

	private function socialFields()
	{
		return array_map(
			function($socialTag){
				return Field::make('text', $socialTag, __( ucfirst($socialTag).' Link' ));
			}, ['facebook', 'youtube','twitter','instagram']);
	}


	private function storeFields()
	{
		return  [
			Field::make( 'text', 'netsuite_addcart_base_url', 'NetSuite base cart url' )
				 ->set_default_value('https://checkout.netsuite.com/app/site/query/additemtocart.nl?qty=1&c=1247539&buyid=')
				 ->set_help_text( 'Product ID is dynamically appended to the end of URL' ),
			Field::make( 'text', 'netsuite_viewcart_url', 'View Cart URL')
			     ->set_default_value('https://www.sellmarknexus.com/checkout/cart.ssp?ext=T&amp;whence=&amp;sc=3#cart'),
		    Field::make( 'text', 'netsuite_cart_count_url', 'Cart Count URL')
			     ->set_default_value('https://checkout.netsuite.com/app/site/query/getcartitemcount.nl?c=1247539&n=1')
		];
	}

	private function newsletterFields()
	{
		return [
			Field::make( 'textarea', 'slmk_newsletter_html', 'Newsletter html snippet' )
				 ->set_rows(12)
		];
	}

	private function developerFields()
	{
		return [
			Field::make( 'text', 'slmk_api_endpoint', 'Sellmark API Endpoint' )
			     ->set_default_value('https://api-staging.slmk.dev/v1'),
			Field::make( 'text', 'slmk_api_form_endpoint', 'Sellmark API Form Endpoint' )
			     ->set_help_text('If form API endpoint/version differs, specify here, defaults to Sellmark API Enpoint declared above'),
			Field::make( 'textarea', 'slmk_api_key', 'Sellmark API Key' )
				 ->set_default_value('Y4=nsSabrJ6C8q-6XvYVMp6zDX@BYPnFmPP2k7$G%txKm%@5X4ku5rJE2ap?ZwyZYjcn^8BJZ*P7y@hPwp+r$@KMTfynkXP-a98DRBYGH%AU^?!R6+SM7?S8aRM?v_TK')
				 ->set_rows(2),
			Field::make( 'textarea', 'slmk_analytics', 'Sellmark Analytics Scripts' )->set_rows(8),
			Field::make( 'text', 'slmk_redirect_product_path', 'Redirect Product Path' ),
			Field::make( 'text', 'slmk_redirect_product_var', 'Redirect Product GET URL Variable' )
		];
	}

	public function crb_load()
	{
	    Carbon_Fields::boot();
	}

	public static function get($option, $type = 'option') : ?string
	{
		if($type == 'option') {
			return get_option("_{$option}");
		} elseif ($type == 'theme_option') {
			return json_encode(carbon_get_theme_option($option));
		}
		return null;
	}

}
