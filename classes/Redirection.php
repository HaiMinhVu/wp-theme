<?php

namespace SellmarkTheme;

class Redirection {

    const PRODUCT_TYPE = 'product';
    const CATEGORY_TYPE = 'category';
    const PRODUCT_PATH_KEY = 'product_path';
    const PRODUCT_VAR_KEY = 'product_var';
    const CATEGORY_PATH_KEY = 'category_path';
    const CATEGORY_VAR_KEY = 'category_var';
    const DEFAULT_COLUMN_NAME = 'id';

    protected $options;
    protected $products;
    protected $categories;

    public function __construct(array $options)
    {
        $this->options = $options;
        $this->products = Data::getProducts();
        $this->categories = Data::getCategories();
     }

    public static function init(array $options) : void
    {
        $self = new self($options);
        try {
            $self->setupRedirects();
        } catch(\Exception $e) {
            //
        }
    }

    protected function getOption($key = null) : ?string
    {
        if($key && array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }
        return null;
    }

    public function setupRedirects() : void
    {
        $this->setupProductRedirects();
        $this->setupCategoryRedirects();
    }

    public function setupProductRedirects() : void
    {
        if($this->getOption(self::PRODUCT_PATH_KEY) && $this->getOption(self::PRODUCT_VAR_KEY)) {
            $this->setupRedirect(
                self::PRODUCT_TYPE,
                $this->getOption(self::PRODUCT_PATH_KEY),
                $this->getOption(self::PRODUCT_VAR_KEY)
            );
        }
    }

    public function setupCategoryRedirects() : void
    {
        if($this->getOption(self::CATEGORY_PATH_KEY) && $this->getOption(self::CATEGORY_VAR_KEY)) {
            $this->setupRedirect(
                self::CATEGORY_TYPE,
                $this->getOption(self::CATEGORY_PATH_KEY),
                $this->getOption(self::CATEGORY_VAR_KEY)
            );
        }
    }

    protected function setupRedirect(string $type, string $path, string $getVar, string $columnName = self::DEFAULT_COLUMN_NAME) : void
    {
        if(getUrlPath() == $path) {
            $items = $this->getItems($type);
            if($key = array_search($_GET[$getVar], array_column($items, $columnName))) {
                $queryVars = $this->buildQueryVars([$getVar]);
                $url = $this->getItemPage($type, $items[$key]);
                $url = $url.'?'.$queryVars;
                $this->redirect($url);
            }
        }
    }

    protected function getItems(string $type) : ?array
    {
        return ($type == self::CATEGORY_TYPE) ? $this->categories : $this->products;
    }

    protected function getItemPage(string $type, object $item)
    {
        return ($type == self::CATEGORY_TYPE) ? Data::categoryPage($item) : Data::productPage($item);
    }

    protected function productRedirect(object $product) : void
    {
        if($page = Data::productPage($product)) {
            $this->redirect($page);
        }
    }

    protected function categoryRedirect(object $category) : void
    {
        if($page = Data::categoryPage($category)) {
            $this->redirect($page);
        }
    }

    protected function buildQueryVars(array $without_keys = [])
    {
        $getVars = $_GET;
        foreach($without_keys as $key) {
            unset($getVars[$key]);
        }
        return http_build_query($getVars);
    }

    protected function redirect(string $url) : void
    {
        wp_redirect($url, 301);
    }

}
