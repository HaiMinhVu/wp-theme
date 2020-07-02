<?php
/**
 * WPSEO plugin file.
 *
 * @package Yoast\WP\SEO\Generators\Schema
 */

namespace SellmarkTheme\Yoast\Schema\Piece;

use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;
use SellmarkTheme\Data;

class Product extends Abstract_Schema_Piece {

    private $product;
    private $image;

    public function __construct($product) {
        $this->product = $product;
        $this->setImage();
    }

    public function is_needed() {
        return true;
    }

    /**
     * Adds Product piece to the graph.
     *
     * @return array Person Schema markup.
     */
    public function generate() {
        $data      = [
            "@context" => "http://schema.org/",
			'@type' => 'Product',
            'name' => $this->product->name,
            'description' => preg_replace( "/\r|\n/", "", strip_tags($this->product->description, '<p><br><ul><li><ol>')),
            'sku' => $this->product->sku,
            'brand' => Data::getBrandName(),
            'gtin8' => $this->product->upc,
            'offers' => [
                '@type' => 'Offer',
                'availability' => $this->itemAvailability(),
                'priceCurrency' => 'USD',
                'price' => $this->product->price
            ]
		];

        if($this->image) {
            $data['image'] = $this->image;
        }

        return $data;
    }

    private function itemAvailability() {
        $type = $this->product->in_stock ? 'InStock' : 'OutOfStock';
        return "http://schema.org/{$type}";
    }

    private function setImage() {
        $remotePath = $this->product->remote_image_path;
        $hasRemoteImage = $remotePath != '' || !$remotePath;
        $this->image = ($hasRemoteImage) ? Data::cdnLink($remotePath, 400) : false;
    }
}
