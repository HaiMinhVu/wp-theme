<?php

namespace SellmarkTheme;

use DateTime;

class Sitemap {

    const DEFAULT_LAST_UPDATE = '2020-06-30T16:53:52+00:01';

    public static function init() {
        $SiteMap = new self;
        try {
            $SiteMap->addToSitemap();
        } catch(\Exception $e) {
            //
        }
    }

    protected function addToSitemap() {
        add_filter('wpseo_sitemap_page_content', array($this, 'addPagesToSitemap'));
        add_filter('wpseo_sitemap_category_content', array($this, 'addCategoriesToSitemap'));
    }

    public function addCategoriesToSitemap() {
        $categoriesArr = array_map(function($category){
            return $this->getCategoryPageSitemapString($category);
        }, Data::getCategories());
        return implode('', $categoriesArr);
    }

    public function getCategoryPageSitemapString($category) {
        $location = get_bloginfo('url').'/category/'.$category->id;
        $lastModified = $this->getLastModifiedDate($category->last_update);
        // $lastModified = $category->last_update ? $category->last_update : self::DEFAULT_LAST_UPDATE;
        $image = false;
        if($image_path = $category->remote_path) {
            $image = cdnLink($image_path, 400);
        }
        return $this->getSitemapString($location, $lastModified, $image);
    }

    public function addPagesToSitemap() {
        $pages = [];
        $pages[] = $this->addProductPagesToSitemap();
        return implode('', $pages);
    }

    protected function addProductPagesToSitemap() {
        $productPagesArr = array_map(function($product){
            return $this->getProductPageSitemapString($product);
        }, Data::getProducts());
        return implode('', $productPagesArr);
    }

    protected function getLastModifiedDate($dateString = null) {
        $dateTime = ($dateString) ? new DateTime($dateString) : new DateTime(self::DEFAULT_LAST_UPDATE);
        return $dateTime->format('c');
    }

    protected function getProductPageSitemapString($product) {
        $location = get_bloginfo('url').productPage($product);
        $lastModified = $this->getLastModifiedDate($product->last_remote_update);
        // $lastModified = $product->last_remote_update ? $product->last_remote_update : self::DEFAULT_LAST_UPDATE;
        $image = false;
        if($image_path = $product->remote_image_path) {
            $image = cdnLink($image_path, 400);
        }
        return $this->getSitemapString($location, $lastModified, $image);
    }

    protected function getSitemapString($location, $lastModified, $image = false) {
        $s = "<url>";
        $s .= "<loc>{$location}</loc>";
        $s .= "<lastmod>{$lastModified}</lastmod>";
        if($image) {
            $s .= "<image:image>
                     <image:loc>{$image}</image:loc>
                   </image:image>";
        }
        $s .= "</url>";
        return $s;
    }

}
