<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';

start_admin_session();
logout_admin();
redirect('index.php');
