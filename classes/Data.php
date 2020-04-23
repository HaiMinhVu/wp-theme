<?php

namespace Pulsar;

include_once( ABSPATH . 'wp-admin/includes/image.php' );

use Sellmark\Cache\Connector as SellmarkCache;
use GuzzleHttp\Client;

class Data {

    const TIMEOUT_SECONDS = 3600;
    const PERSIST_SECONDS = 300;
    const DISABLE_CACHE = false;
    const DISABLE_REMOTE_CACHE = false;

    protected $cache;
    protected $client;
    protected $manufacturer;
    protected $apiEndpoint;
    protected $sliderId;
    protected static $instance = null;

    protected function __construct()
    {
        $this->cache = new SellmarkCache([
        	 'host' => REDIS_HOST,
        	 'port' => 6379
        ]);
        $this->client = new Client([
            'headers' => [
                'X-Api-Key' => CarbonFields::get('slmk_api_key')
            ]
        ]);

        $this->manufacturer = CarbonFields::get('slmk_site_brand');
        $this->apiEndpoint = CarbonFields::get('slmk_api_endpoint');
        $this->sliderId = CarbonFields::get('slmk_home_slider');
    }

    public static function cacheRemember($key, $method) {
        $instance = self::getInstance();
        $cache = $instance->cache;

        if($cache->isExpired($key) || self::DISABLE_CACHE) {
            $value = $method();
            $cache->set($key, $value);
        }

        return $cache->get($key);
    }

    protected static function getInstance()
    {
        if(self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function get($key, $path, $isManufacturerPath = true) {
        $instance = self::getInstance();
        $cache = $instance->cache;
        $client = $instance->client;
        $url = ($isManufacturerPath) ? $instance->getManufacturerEndpoint($path) : $instance->getEndpoint($path);
        $url = (self::DISABLE_REMOTE_CACHE) ? $url.'?force-update=1' : $url;

    	if($cache->isExpired($key) || self::DISABLE_CACHE) {
    		try {
    			$res = $client->get($url);
    			$data = $res->getBody()->getContents();
    			$cache->set($key, $data, self::TIMEOUT_SECONDS);
    		} catch(\Exception $e) {
                print_r($e->getMessage());
    			$cache->expire($key, self::PERSIST_SECONDS);
    		}
    	}

    	return $cache->get($key);
    }

    public static function getSetting($key) {
        return self::getCarbonField($key);
    }

    public static function getThemeOption($key) {
        return self::getCarbonField($key, 'theme_option');
    }

    private static function getCarbonField($key, $type = 'option') {
        $instance = self::getInstance();
        $cache = $instance->cache;
        
        if($cache->isExpired($key) || self::DISABLE_CACHE) {
            $cache->set($key, CarbonFields::get($key, $type));
        }

        return $cache->get($key);
    }

    private function getEndpoint($path)
    {
        return "{$this->apiEndpoint}/{$path}";
    }

    private function getManufacturerEndpoint($path)
    {
        $instance = self::getInstance();
        return "{$instance->apiEndpoint}/{$instance->manufacturer}/{$path}";
    }

    public static function productSlugs()
    {
        $names = self::get('products_names', '/products/names');

        return array_map(function($name){
            return [
                'id' => $name->id,
                'slug' => sanitize_title($name->name)
            ];
        }, $names);
    }

    public static function sliderImages()
    {
        $instance = self::getInstance();
        return self::get("slider_images_{$instance->sliderId}", "slider/{$instance->sliderId}", false)->data;
    }

    public static function sliders()
    {
        return self::get('sliders', 'slider', false)->data;
    }

    public static function brands()
    {
        return self::get('brands', 'brand', false)->data;
    }

    public static function featuredProducts()
    {
        return self::get('featured_products', 'products/featured', false)->data;
    }

    public static function productCategoriesAll()
    {
        return self::get('products_categories_all', 'categories/all')->data;
    }

    public static function productCategories()
    {   
        return (self::get('products_categories', 'categories')) ? self::get('products_categories', 'categories')->data : [];
    }

    public static function getProductCategory($categoryId)
    {
        $filtered = array_filter(self::productCategoriesAll(), function($category) use ($categoryId) {
            return $category->id == $categoryId;
        });

        if(count($filtered) > 0) {
            $filtered = array_values($filtered);
            return $filtered[0];
        }

        return null;
    }

    public static function getFeaturedProducts($featuredProductParentId)
    {
        $featuredId = CarbonFields::get('slmk_featured_products');
        return self::get("featured_products_{$featuredId}", "products/featured/{$featuredId}", false)->data;
    }

    public static function getProductCategoryTree($categoryId)
    {
        return (object)[];
        $category = self::getProductCategory($categoryId);
        $parent = self::getProductCategory($category->parent);
        $data = (object)[
            'category' => $category
        ];
        if($parent) {
            $data->parent = $parent;
        }
        return $data;
    }

    public static function getCategoryBreadcrumbs($categoryId)
    {
        $categoryTree = [];

        while ($categoryId > 0) {
            $category = self::getProductCategory($categoryId);
            if($category) {
                $categoryId = $category->parent;
                unset($category->children);
                $categoryTree[] = $category;
            } else {
                $categoryId = false;
            }
        }
        $categoryTree = array_reverse($categoryTree);

        return $categoryTree;
    }

    private static function getCategoryIdsByString($categoriesString = null)
    {
        if(!$categoriesString) return [];
        $categories = explode(",", $categoriesString);
        $categoryIds = array_map(function($categoryName){
            return self::getCategoryIdByLabel($categoryName);
        }, $categories);
        return array_values(array_unique(array_filter($categoryIds)));
    }

    private static function getCategoryIdByLabel($label)
    {
        $categories = self::productCategories();

        $filtered = array_filter($categories, function($category) use ($label){
            return $label == $category->label;
        });

        if(count($filtered) > 0) {
            return (array_values($filtered)[0])->id;
        }
        return '';
    }

    public static function getCategoryLabelById($id)
    {
        $category = self::getCategoryByType($id);
        return $category->label;
    }

    public static function getCategoryById($id)
    {
        return self::getCategoryByType($id);
    }

    private static function getCategoryByType($str, $type = 'id')
    {
        $categories = self::productCategoriesAll();

        $filtered = array_filter($categories, function($category) use ($str, $type){
            return $str == $category->$type;
        });

        if(count($filtered) > 0) {
            return (array_values($filtered)[0]);
        }
        return '';
    }

    public static function getProducts()
    {
        return self::get('products_api', 'products')->data;
    }

    public static function getUrl($path)
    {
        $instance = self::getInstance();
        return $instance->apiEndpoint.$path;
    }

    public static function getPublicUrl($path)
    {
        $instance = self::getInstance();
        return $instance->getManufacturerEndpoint($path);
    }

    public static function getProducts2()
    {
        return self::getProducts();
    }

    public static function getProduct($id)
    {
        return self::get("product_{$id}", "product/{$id}")->data;
    }

    public static function getProductsByCategoryId($categoryId)
    {
        return self::get("category_{$categoryId}_products", "category/{$categoryId}/products")->data;
    }

    public static function getSubCategories($categoryId)
    {
        return self::get("category_{$categoryId}", "category/{$categoryId}")->data->subCategories;
    }

    public static function getProductIDBySlug($productSlug)
    {
        $slugs = self::productSlugs();

        $filtered = array_filter($slugs, function($slug) use ($productSlug){
            return $productSlug == $slug['slug'];
        });

        if(count($filtered) == 0) {
            return null;
        }

        return (array_values($filtered)[0])['id'];
    }

    private static function downloadProductImage($url)
    {
        $parsed = parse_url($url);
        $filename = basename($parsed['path']);
        $uploaddir = wp_upload_dir();
        $uploadfile = $uploaddir['path'] . '/' . $filename;

        $contents= file_get_contents($url);
        $savefile = fopen($uploadfile, 'w');
        fwrite($savefile, $contents);
        fclose($savefile);

        $wp_filetype = wp_check_filetype(basename($filename), null );

        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => $filename,
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $uploadfile );

        $imagenew = get_post( $attach_id );
        $fullsizepath = get_attached_file( $imagenew->ID );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
        wp_update_attachment_metadata( $attach_id, $attach_data );
    }

    public static function downloadProductImages($product)
    {
        foreach($product->images as $image) {
            self::downloadProductImage("http://pulsarnv-ds.com/pulsarnv2020/assets/images/img_main/$image->image_name");
        }
    }

}
