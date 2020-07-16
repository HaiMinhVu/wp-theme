<?php

namespace SellmarkTheme;

class Redirection {

    const PRODUCT_PATH_KEY = 'product_path';
    const PRODUCT_VAR_KEY = 'product_var';
    const DEFAULT_COLUMN_NAME = 'id';

    protected $options;
    protected $products;

    public function __construct(array $options)
    {
        $this->options = $options;
        $this->products = Data::getProducts();
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
    }

    public function setupProductRedirects() : void
    {
        if($this->getOption(self::PRODUCT_PATH_KEY) && $this->getOption(self::PRODUCT_VAR_KEY)) {
            $this->setupRedirect(
                $this->getOption(self::PRODUCT_PATH_KEY),
                $this->getOption(self::PRODUCT_VAR_KEY)
            );
        }
    }

    protected function setupRedirect(string $path, string $getVar, string $columnName = self::DEFAULT_COLUMN_NAME) : void
    {
        if($this->getUrlPath() == $path) {
            $items = $this->getItems();
            if($key = array_search($_GET[$getVar], array_column($items, $columnName))) {
                $queryVars = $this->buildQueryVars([$getVar]);
                $url = $this->getItemPage($items[$key]);
                $url = $url.'?'.$queryVars;
                $this->redirect($url);
            }
        }
    }

    protected function getItems() : ?array
    {
        return $this->products;
    }

    protected function getItemPage(object $item)
    {
        return Data::productPage($item);
    }

    protected function productRedirect(object $product) : void
    {
        if($page = Data::productPage($product)) {
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

    protected function getUrlPath() : ?string
    {
        try {
            $requestUri = $_SERVER['REQUEST_URI'];
            $parsedUrl = parse_url($requestUri);
            return $parsedUrl['path'];
        } catch(\Exception $e) {
            return null;
        }
    }

}
