<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return [
	'default_posts_per_page' => 24,
	'sorting' => [
		'date'       => 'Sort by latest',
		'popularity' => 'Sort by popularity',
		'rating'     => 'Sort by average rating',
		'price'      => 'Sort by price: low to high',
		'price-desc' => 'Sort by price: high to low',
		'title'      => 'Sort by title'
	]
];