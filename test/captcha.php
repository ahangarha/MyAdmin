<?php
/**
 * MyAdmin - Test
 * Copyright (C) Persian Icon Software
 * The GPL License
*/

require_once(__DIR__.'/ma.inc.php');

myWebsite(FALSE);

$captcha = &ma_class('captcha');

$use_session = FALSE;

$captcha->image_size = [220, 80];

$captcha->display($use_session);