<?php
/**
 * MyAdmin - Test
 * Copyright (C) Persian Icon Software
 * The GPL License
*/

require_once(__DIR__.'/ma.inc.php');

myWebsite(FALSE);

header('Content-Type: text/plain');

echo date('Y-m-d H:i:s e');
echo "\n";

ma_timezone('Europe/Berlin');
echo date('Y-m-d H:i:s e');
echo "\n";

ma_timezone('Asia/Tokyo');
echo date('Y-m-d H:i:s e');
echo "\n";

ma_timezone('America/Los_Angeles');
echo date('Y-m-d H:i:s e');
echo "\n";

ma_timezone('Asia/Tehran');
echo date('Y-m-d H:i:s e');
echo "\n";