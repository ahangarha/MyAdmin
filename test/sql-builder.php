<?php
/**
 * MyAdmin - Test
 * Copyright (C) Persian Icon Software
 * The GPL License
*/

require_once(__DIR__.'/ma.inc.php');

myWebsite(FALSE);

header('Content-Type: text/plain');

$drive = isset($_GET['sqlite']) ? 'sqlite' : 'mysql';

$db_schema = require(__DIR__.'/sql-schema.php');

$generator =& ma_class('sql_generator');

$generator->drive($drive); // mysql|sqlite

$generator->table_prefix = 'ma_test_';

$generator->if_not_exists = TRUE;

$load = $generator->schema_file($db_schema);
if ($load == FALSE) {
	echo 'err_sql_generator_error'. '('.$generator->error.')';
	return FALSE;
}

$sql_x = $generator->generate();
if ($sql_x == FALSE) {
	echo 'err_sql_generator_error'. '('.$generator->error.')';
	return FALSE;
}

foreach ($sql_x as $sql_cox) {
	echo $sql_cox;
	echo "\n\n";
}