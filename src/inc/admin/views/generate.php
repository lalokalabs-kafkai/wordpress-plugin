<?php
/**
 * Generate page for the admin.
 */

use Niteo\Kafkai\Plugin\Admin\Api;

$api      = new Api();
$response = $api->call(
	'/articles/generated',
	'GET'
);

if ( $response ) {
	$data = json_decode( $api->response, true );

	// If isset $data['errors'], then the request was not successfull

	print_r( $data );
} else {
	print_r( $api->error );
}
