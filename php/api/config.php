<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(array('error' => 'Method not allowed'), 405);
}

$config = public_site_config();
json_response($config, 200);
