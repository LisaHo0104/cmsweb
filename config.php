<?php

// Absolute path to the project root — handy for file_get_contents, include, etc.
define('BASE_PATH', __DIR__);

// Web-accessible path prefix used when building <img src="..."> and <link href="..."> URLs
define('ASSETS_URL', '/cmsweb/assets');

// Open a DB connection; pages that need it just require this file
$conn = mysqli_connect('localhost', 'root', '', 'cart_db');
