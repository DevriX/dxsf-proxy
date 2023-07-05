<?php
namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

interface HandlerInterface {
	public function handle( WP_REST_Request $request ) : WP_REST_Response;
}