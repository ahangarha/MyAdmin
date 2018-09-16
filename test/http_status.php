<?php
/**
 * MyAdmin - Test
 * Copyright (C) Persian Icon Software
 * The GPL License
*/

require_once(__DIR__.'/ma.inc.php');

myWebsite(FALSE);


ma_http_status(404);

echo "Page not found.";