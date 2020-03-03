<?php

namespace Pulsar;

include_once( ABSPATH . 'wp-admin/includes/image.php' );

use Sellmark\Cache\Connector as SellmarkCache;
use GuzzleHttp\Client;

class Data {

    const API_ENDPOINT = 'apidev/v1/';
    const MANUFACTURER = 'pulsar/';
    const PUBLIC_API_ENDPOINT = 'https://lumen.slmk.dev/';
    const TIMEOUT_SECONDS = 3600;
    const PERSIST_SECONDS = 300;
    const SLIDER_ID = 257;
    const NO_CACHE = true;

    protected $cache;
    protected $client;
    protected static $instance = null;

    protected function __construct()
    {
        $this->cache = new SellmarkCache([
        	 'host' => REDIS_HOST,
        	 'port' => 6379
        ]);
        $this->client = new Client([
            'headers' => [
                'X-Api-Key' => 'Y4=nsSabrJ6C8q-6XvYVMp6zDX@BYPnFmPP2k7$G%txKm%@5X4ku5rJE2ap?ZwyZYjcn^8BJZ*P7y@hPwp+r$@KMTfynkXP-a98DRBYGH%AU^?!R6+SM7?S8aRM?v_TK'
            ]
        ]);
    }

    protected static function getInstance()
    {
        if(self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function get($key, $url) {
        $instance = self::getInstance();
        $cache = $instance->cache;
        $client = $instance->client;

    	if($cache->isExpired($key) || self::NO_CACHE) {
    		try {
    			$res = $client->get($url);
    			$data = $res->getBody()->getContents();
    			$cache->set($key, $data, self::TIMEOUT_SECONDS);
    		} catch(\Exception $e) {
    			$cache->expire($key, self::PERSIST_SECONDS);
    		}
    	}

    	return $cache->get($key);
    }

    public static function productSlugs()
    {
        $names = self::get('products_names', self::API_ENDPOINT.self::MANUFACTURER.'/products/names');

        return array_map(function($name){
            return [
                'id' => $name->id,
                'slug' => sanitize_title($name->name)
            ];
        }, $names);
    }

    public static function sliderImages()
    {
        // dd(self::get('slider_images', self::API_ENDPOINT.'slider/'.self::SLIDER_ID));
        // dd(self::API_ENDPOINT.'slider/'.self::SLIDER_ID);
        return self::get('slider_images', self::API_ENDPOINT.'slider/'.self::SLIDER_ID)->data;
    }

    public static function productCategoriesAll()
    {
        // dd(self::get('products_categories_all', self::API_ENDPOINT.'categories/all'));
        return self::get('products_categories_all', self::API_ENDPOINT.self::MANUFACTURER.'categories/all')->data;
    }

    public static function productCategories()
    {
        return self::get('products_categories', self::API_ENDPOINT.self::MANUFACTURER.'categories')->data;


        $productCategories = (array)(self::get("product_categories", "http://pulsarnv-ds.com/pulsarnv2020/api/public/index.phpproduct/product-categories"))->data;

        return array_values($productCategories)[0]->children;

        $data = [];
        foreach($productCategories as $category) {
            $data[] = $category;
            $data = array_merge($data, $category->children);
        }
        return array_map(function($category){
            unset($category->children);
            return $category;
        }, $data);
    }

    public static function getProductCategory($categoryId)
    {
        // dd([self::productCategoriesAll(), $categoryId]);
        $filtered = array_filter(self::productCategoriesAll(), function($category) use ($categoryId) {
            return $category->id == $categoryId;
        });
        // dd($filtered);

        if(count($filtered) > 0) {
            $filtered = array_values($filtered);
            return $filtered[0];
        }

        return null;
    }

    public static function getFeaturedProducts($featuredProductParentId)
    {
        return self::get('featured_products', self::API_ENDPOINT.'products/featured/'.$featuredProductParentId)->data;
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

        // unset($categoryTree[0]);

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
        $categories = self::productCategoriesAll();

        // dd([
        //     $categories,
        //     $id
        // ]);

        $filtered = array_filter($categories, function($category) use ($id){
            return $id == $category->id;
        });

        if(count($filtered) > 0) {
            return (array_values($filtered)[0])->label;
        }
        return '';
    }

    public static function getProducts()
    {
        $products = (self::get('products', 'http://pulsarnv-ds.com/pulsarnv2020/api/public/index.phpproduct/all-products-search'))->data;

        return array_map(function($product){
            $categories = (property_exists($product, 'categories')) ? $product->categories : null;
            $product->category_ids = [];
            if($categories) {
                $product->category_ids = self::getCategoryIdsByString($product->categories);
            }
            return $product;
        }, $products);
    }

    public static function getUrl($path)
    {
        return self::API_ENDPOINT.$path;
    }

    public static function getPublicUrl($path)
    {
        return self::PUBLIC_API_ENDPOINT.self::MANUFACTURER.'/'.$path;
    }

    public static function getProducts2()
    {
        return self::get('products_api', self::API_ENDPOINT.self::MANUFACTURER.'products')->data;
    }

    public static function getProduct($id)
    {
        return self::get("product_{$id}", self::API_ENDPOINT.self::MANUFACTURER.'product/'.$id)->data;
    }

    public static function getProductsByCategoryId($categoryId)
    {
        return self::get("category_{$categoryId}_products", self::API_ENDPOINT.self::MANUFACTURER.'category/'.$categoryId.'/products')->data;
    }

    public static function getSubCategories($categoryId)
    {
        return self::get("category_{$categoryId}", self::API_ENDPOINT.self::MANUFACTURER.'category/'.$categoryId)->data->subCategories;
    }

    // public static function

    public static function getProductIDBySlug($productSlug)
    {
        // $products = self::getProducts2();
        $slugs = self::productSlugs();

        // dd([$productSlug, $products]);

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
