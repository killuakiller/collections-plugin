<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return [
	'gift-for-dad' => [
		'title'          => 'Gift For Dad',
		'taxonomy'       => [
			[
				'taxonomy' => 'product_cat',
				'terms'    => [
					'dad'
				]
			]
		],
		'posts_per_page' => 15,
		'filters'        => [
			'tag'   => 'product_tag',
			'color' => 'pa_color',
			'size'  => 'pa_size'
		]
	],
	
	'memorial' => [
		'title'          => 'Memorial',
		'taxonomy'       => [
			[
				'taxonomy' => 'product_cat',
				'terms'    => [
					'memorial'
				]
			]
		],
		'posts_per_page' => 12,
		'filters'        => [] // Không có filter nào cho collection này
	]
];