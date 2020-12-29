<?php

namespace SellmarkTheme;

use GuzzleHttp\Client;

class Data {

    protected $client;
    protected $manufacturer;
    protected $apiEndpoint;
    protected $sliderId;
    protected static $instance = null;

    protected function __construct()
    {
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
        return $method();
    }

    protected static function getInstance()
    {
        if(self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get($cacheKey, $path, $isManufacturerPath = true, $forceDisableCache = false) {
        $instance = self::getInstance();
        if(!isset($_SESSION[$cacheKey])){
            return $instance->_get($cacheKey, $path, $isManufacturerPath, $forceDisableCache);
        }
        else{
            return $_SESSION[$cacheKey];
        }
    }

    public function _get($cacheKey, $path, $isManufacturerPath = true, $forceDisableCache = false) {
        $url = ($isManufacturerPath) ? $this->getManufacturerEndpoint($path) : $this->getEndpoint($path);
        $res = $this->client->get($url);
        $data = json_decode($res->getBody()->getContents());
        $_SESSION[$cacheKey] = $data;
        return $data;
    }

    public static function getSetting($key) {
        return self::getCarbonField($key);
    }

    public static function getThemeOption($key) {
        return self::getCarbonField($key, 'theme_option');
    }

    public static function getBrandName() {
        $brandSlug = CarbonFields::get('slmk_site_brand');
        $brandSlug = str_replace('-', ' ', $brandSlug);
        return ucwords($brandSlug);
    }

    private static function getCarbonField($key, $type = 'option') {
        return json_decode(CarbonFields::get($key, $type));
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
        $names = self::get('products_names', 'products/names')->data;

        return array_map(function($name){
            return [
                'id' => $name->id,
                'slug' => sanitize_title($name->name)
            ];
        }, $names);
    }

    public static function form($formId)
    {
        $instance = self::getInstance();
        $instance->apiEndpoint = CarbonFields::get('slmk_api_form_endpoint');
        return $instance->_get("form_{$formId}", "form/{$formId}", false)->data;
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

    public static function getCategories()
    {
        return self::productCategoriesAll();
    }

    public static function productCategoriesAll()
    {
        return self::get('products_categories_all', 'categories/all')->data;
    }

    public static function parentCategory()
    {
        return self::getCategory(self::parentCategoryId());
    }

    public static function parentCategoryId() : ?int
    {
        $categories = self::productCategories();
        if(count($categories) > 0) {
            return $categories[0]->parent;
        }
        return null;
    }

    public static function parentCategoryPage() : ?string
    {
        return self::categoryPageById(self::parentCategoryId());
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

        if($parentCategory = Data::parentCategory()) {
            $parentCategory->label = 'All Products';
            $categoryTree[] = $parentCategory;
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

    public static function getProducts($forceDisableCache = false)
    {
        return self::getProductsByCategoryId(self::parentCategoryId(), $forceDisableCache);

        // Using dedicated endpoint
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
        return self::get("product_{$id}", "product/{$id}", false)->data;
    }

    public static function getProductsByCategoryId($categoryId, $forceDisableCache = false)
    {
        return self::get("category_{$categoryId}_products", "category/{$categoryId}/products", true, $forceDisableCache)->data;
    }

    public static function getCategory($categoryId)
    {
        return self::get("category_{$categoryId}_with_parent", "category/{$categoryId}")->data;
    }

    public static function getSubCategories($categoryId)
    {
        if($category = self::getCategory($categoryId)) {
            return $category->subCategories;
        }
        return [];
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

    public static function cdnLink($filename, $imageWidth = null, $additionalOptions = []){
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

    public static function productPage($product = null) {
        if($product) {
            return '/products/'.$product->nsid.'/'.sanitize_title($product->name);
        }
        return null;
    }

    public static function categoryPage($category = null) {
        if($category) {
            return self::categoryPageById($category->id).'/'.sanitize_title($category->label);
        }
        return null;
    }

    public static function categoryPageById($id = null) {
        if($id) {
            return get_site_url().'/category/'.$id;
        }
        return null;
    }

    public static function doesProductBelongOnSite($productId = null)
    {
        if($productId) {
            $validIds = array_map(function($product) {
                return $product->nsid;
            },self::getProducts());
            return in_array($productId, $validIds);
        }
        return false;
    }

    public static function productPageUrl($product) {
        return get_site_url().self::productPage($product);
    }

}
