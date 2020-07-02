<?php
/**
 * WPSEO plugin file.
 *
 * @package Yoast\WP\SEO\Generators\Schema
 */

namespace SellmarkTheme\Yoast\Schema\Piece;

use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

class ItemListSimple extends Abstract_Schema_Piece {

    private $items;
    private $position;

    public function __construct($items) {
        $this->items = $items;
        $this->position = 0;
    }

    public function is_needed() {
        return true;
    }

    public function generate() {
        return [
            "@context" => "http://schema.org/",
            '@type' => 'ItemList',
            'itemListElement' => $this->addItems()
        ];
    }

    public function addItems() {
        return array_map(function($item){
            $this->position++;
            return [
                '@type' => 'ListItem',
                'position' => $this->position,
                'url' => $item['url']
            ];
        }, $this->filteredItems());
    }

    private function filteredItems() {
        return array_filter($this->items, function($item){
            return array_key_exists('url', $item);
        });
    }

}
