<?php
/**
 * generate_example.php
 * Generates the example website. This is itself an example of how to compose
 * your builder script for your own static website.
 *
 * @author Nathan Campos <nathan@innoveworkshop.com>
 */

require_once __DIR__ . '/vendor/autoload.php';

use \Fantastique\Builder;

// Get our builder helper.
$builder = new Builder(__DIR__ . '/example', __DIR__ . '/build');

// Copy over everything from the static directory.
$builder->copy_static(__DIR__ . '/static');

// Render the example folder.
$builder->render_folder(__DIR__ . '/example', true, ['context.php']);
$builder->render_page($builder->base_path . '/context.php', [
	'something' => 'extra context!'
]);
