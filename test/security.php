<?php
/**
 * MyAdmin - Test
 * Copyright (C) Persian Icon Software
 * The GPL License
*/

require_once(__DIR__.'/ma.inc.php');

myWebsite(FALSE);

header('Content-Type: text/html');

$data = [
	'HelloWorld-MyNameIs+CMS.4', // Alphabet
	15631566,                    // Integer
	'21321.5465',                // Numeric
	'hello@mail.com',            // email address
	'182.64.15.15',              // IPv4
	'2001:db8:a0b:12f0::1',      // IPv6
	'\' OR `pass`=\'0',          // Quote
	'&#60;html><div class=&quot;test&quot;>&#269;&#x5d0; test&#39;</diV>&lt;/html&gt;', // Html
	'%00xx',
];

/**************************************************/

$security =& ma_class('security');

function test_td($name, $st) {
	echo '<tr>';
	echo '<td>'.$name.'</td>';
	
	if ($st == FALSE) {
		echo '<td style="color: red">FALSE</td>';
	}
	else {
		echo '<td style="color: green">TRUE</td>';
	}

	echo '</tr>';
}

$count = count($data);

/**************************************************/

echo '<html><head>';
echo '<style>table, th, td { border: 1px solid black; border-collapse: collapse; }';
echo '#m{ width: 100%; max-width: 640px }</style></head>';
echo '<body><div id="m">';

/**************************************************/

for ($i=0; $i<$count; ++$i) {

	$c = $data[$i];

	echo '<table style="width: 100%">';
	echo '<caption>'.$data[$i].'</caption>';

	// Alphabet
	$st = $security->filter_alphabet($c);
	test_td('Alphabet', $st);

	// Email
	$st = $security->filter_email($c);
	test_td('Email', $st);

	// Int
	$st = $security->filter_int($c, 10);
	test_td('Int', $st);

	// Numeric
	$st = $security->filter_numeric($c);
	test_td('Numeric', $st);

	// IP
	$st = $security->filter_ip($c);
	test_td('IP', $st);

	// URL
	$st = $security->filter_url($c);
	test_td('URL', $st);

	// Quote
	$st = $security->filter_quote($c, 20);
	test_td('Quote', $st);

	// Quote remove
	$st = $security->filter_quote_remove($c, 20);
	test_td('Quote remove', $st);

	// XSS
	$st = $security->filter_xss($c);
	test_td('XSS', $st);

	// Convert to HTML
	$st = $security->str_to_html($c);
	echo '<tr>';
	echo '<td>Html</td>';
	echo '<td>'.$st.'</td>';
	echo '</tr>';

	echo '</table><br>';
}

echo '</div></body></html>';
return EXIT_SUCCESS;