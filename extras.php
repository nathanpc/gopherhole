<?php
/**
 * extras.php
 * A collection of functions to help us manage this website.
 *
 * @author Nathan Campos <hi@nathancampos.me>
 */

require_once __DIR__ . '/gopher.php';

/**
 * Creates the URL pointing to a specific Gopher resource.
 *
 * @param string $type     Resource type.
 * @param string $selector Gopher selector pointing to the resource.
 *
 * @return string Proxied URL to the Gopher resource.
 */
function href($type, $selector) {
	$config = GopherConfig::getInstance();
	return Gopher::ProxySelector($type, $selector, $config->get('rewrite'));
}
