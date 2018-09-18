<?php
/**
 * MyAdmin - Test
 * Copyright (C) Persian Icon Software
 * The GPL License
*/

require_once(__DIR__.'/ma.inc.php');

myWebsite(FALSE);

header('Content-Type: text/plain');

$client =& ma_class('client');

// PID
echo "Process ID: " . $client->pid() . CRLF;


// Domain
echo "Domain: " . $client->domain(['filter' => TRUE, 'with_www' => FALSE]) . CRLF;


// URL Path
echo "URL path: " . $client->url_path() . CRLF;


// HTTP Schema
echo "HTTP Schema: " . $client->http_schema() . CRLF;


// HTTP Schema (X-Forward HTTPS/like CloudFlare)
echo "HTTP Schema (proxy): " . $client->http_schema_x() . CRLF;


// Port
echo "Port: " . $client->server_port() . CRLF;


// Port (X-Forward/like CloudFlare)
echo "Port (proxy): " . $client->server_port_x() . CRLF;


// IP
echo "IP: " . $client->ip() . "\n";


// User Agent
echo "User Agent: " . $client->user_agent() . CRLF;


// AJAX call?
$is_ajax = $client->is_ajax() == TRUE ? 'Yes' : 'No';
echo "Ajax: " . $is_ajax . CRLF;


// GET
echo "Get[test]: " . $client->get('test', $filter = 'xss', $max_length = 15) . CRLF;


// POST
echo "Post[test]: " . $client->post('test', $filter = 'xss', $max_length = 15) . CRLF;


// Get or Post (like _REQUEST)
echo "Request[test]: " . $client->get_post('test', $filter = 'xss', $max_length = 15) . CRLF;


// Cookie
$cookie = $client->cookie('test');
if($cookie != FALSE){
	echo "Cookie[test]:" . $cookie . CRLF;
}
else{
	$name = 'test';
	$value = 'last-visit-'.date('Y-m-d H:i:s');
	$expire = time()+60;
	$client->set_cookie($name, $value, $expire);
}

echo "\n\n";

/* Cache */

// New data
$_SERVER['REMOTE_ADDR'] = '192.168.1.20';
$_SERVER['SERVER_NAME'] = 'www.example.com';
$_SERVER['REQUEST_URI'] = '/test-page';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla';

// Cache data
echo "Domain - cache: "   . $client->domain(['filter' => TRUE, 'with_www' => FALSE]) . CRLF;
echo "URL path - cache: " . $client->url_path($_SERVER['PHP_SELF']) . CRLF;
echo "IP - cache: " . $client->ip() . CRLF;
echo "User Agent: " . $client->user_agent() . CRLF;
echo "Browser: ";

print_r($client->browser());

echo CRLF . CRLF;

// Reset
$client->clear_cache();

// New
echo "Domain - reset: " . $client->domain(['filter' => TRUE, 'with_www' => FALSE]) . CRLF;
echo "URL path - reset: " . $client->url_path($_SERVER['PHP_SELF']) . CRLF;
echo "IP - reset: " . $client->ip() . CRLF;
echo "User Agent - reset: " . $client->user_agent() . CRLF;

return EXIT_SUCCESS;