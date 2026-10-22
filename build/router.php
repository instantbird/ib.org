<?php
// Router for PHP's built-in server, emulating the old .htaccess rewrites:
//   /<locale>/page.html -> page.php?lang=<locale>, /page.html -> page.php
$root = realpath(__DIR__.'/../php');
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('@^/([a-zA-Z]{2}(-[a-zA-Z]{2})?)/(.*)$@', $path, $m)) {
  $_GET['lang'] = $m[1];
  $path = '/'.$m[3];
}
if ($path == '/')
  $path = '/index.php';
$path = preg_replace('@^/([a-z0-9.-]+)\.html?$@', '/$1.php', $path);
$file = $root.$path;
if (!is_file($file)) {
  http_response_code(404);
  return true;
}
if (substr($file, -4) != '.php') {
  header('Content-Type: '.mime_content_type($file));
  readfile($file);
  return true;
}
chdir(dirname($file));
require $file;
