<?php
/**
 * MyAdmin - Test
 * Copyright (C) Persian Icon Software
 * The GPL License
*/

$collate = 'utf8_persian_ci';

return [
	'tabale_a' => [
		'id'      => ['type' => 'MEDIUMINT(2)', 'unsigned' => TRUE, 'autoincrement' => TRUE, 'primary_key' => TRUE],
		'name'    => ['type' => 'VARCHAR(10)'],
		'status'  => ['type' => 'TINYINT(1)'],
		'date'    => ['type' => 'INT(10)', 'default' => 0],

		'_options' => [
			'character_set' => 'utf8',
			'collate' => $collate
		],
	],


	'tabale_b' => [
		'id'    => ['type' => 'MEDIUMINT(2)', 'unsigned' => TRUE, 'autoincrement' => TRUE, 'primary_key' => TRUE],
		'parent_id' => [
			'type'  => 'MEDIUMINT(2)',
			'unsigned' => TRUE,
			'default'  => NULL,
			'foreign_key' => [
				'ref_table'  => 'tabale_a',
				'ref_column' => 'id',
				'column'     => 'parent_id',
				'on_delete'  => 'cascade'
			]
		],

		'value_str'  => ['type' => 'VARCHAR(240)', 'default' => NULL],
		'value_int'  => ['type' => 'INT(10)', 'default' => NULL],
		'value_bool' => ['type' => 'TINYINT(1)', 'default' => NULL],

		'_options' => [
			'character_set' => 'utf8',
			'collate' => $collate
		],

		'_index' => [
			'parent_id_index' => ['parent_id' => 'asc']
		]
	],


	'tabale_c' => [
		'id'    => ['type' => 'INT', 'unsigned' => TRUE, 'autoincrement' => TRUE, 'primary_key' => TRUE],
		'key'   => ['type' => 'VARCHAR(60)', 'unique' => TRUE],
		'val'   => ['type' => 'SMALLINT(2)'],

		'_options' => [
			'character_set' => 'utf8',
			'collate' => $collate,
			'engine'  => 'MyISAM'
		],

		'_index' => [
			'example_index' => ['key' => 'asc', 'val' => 'asc']
		]
	],
	

	'tabale_d' => [
		'id'       => ['type' => 'INT(10)', 'unsigned' => true, 'autoincrement' => true, 'primary_key' => true, '_filter' => 'int'],
		'app_id'   => ['type' => 'SMALLINT(1)', 'unsigned' => true, '_filter' => 'int'],
		'user_id'  => ['type' => 'INT(10)', '_filter' => 'int'],
		'status'   => ['type' => 'TINYINT(1)', 'default' => 0, '_filter' => 'int'],
		'pagename' => ['type' => 'VARCHAR(80)', 'null' => true, 'default' => null, '_filter' => 'xss'],
		'sort'     => ['type' => 'SMALLINT(3)', 'default' => 0],
		'language' => ['type' => 'TINYINT(1)', 'default' => 1, '_filter' => 'int'],
		'special'  => ['type' => 'TINYINT(1)', 'null' => true, 'default' => null, '_filter' => 'int'],
		'publish'  => ['type' => 'INT(10)', 'null' => true, 'default' => null, '_filter' => 'int'],
		'visit'    => ['type' => 'INT(10)', 'default' => 0, '_filter' => 'int'],
		'like'     => ['type' => 'MEDIUMINT(1)', 'default' => 0, '_filter' => 'int'],
		'dislike'  => ['type' => 'MEDIUMINT(1)', 'default' => 0, '_filter' => 'int'],

		'_options' => [
			'character_set' => 'utf8', 
			'collate' => $collate, 
			'auto_increment' => 100
		]
	],

];