<#1>
<?php
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'is_online' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	),
	'option_one' => array(
		'type' => 'text',
		'length' => 10,
		'fixed' => false,
		'notnull' => false
	),
	'option_two' => array(
		'type' => 'text',
		'length' => 10,
		'fixed' => false,
		'notnull' => false
	)
);

$ilDB->createTable("rep_robj_xatr_data", $fields);
$ilDB->addPrimaryKey("rep_robj_xatr_data", array("id"));
?>
<#2>
<?php
$ilDB->dropTableColumn("rep_robj_xatr_data", "option_one");
?>
<#3>
<?php
$ilDB->dropTableColumn("rep_robj_xatr_data", "option_two");
?>
<#4>
<?php
$ilDB->addTableColumn("rep_robj_xatr_data", 'cbt_id', array(
		'type' => 'text',
		'length' => 80,
		'fixed' => false,
		'notnull' => false
	)
);
?>
<#5>
<?php
$ilDB->addTableColumn("rep_robj_xatr_data", 'cbt_key', array(
		'type' => 'text',
		'length' => 80,
		'fixed' => false,
		'notnull' => false
	)
);
?>
<#6>
<?php
$fields = array(
	'obj_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'usr_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'raw_pers_data' => array(
		'type' => 'clob'
	),
	'total_connections' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 0
	),
	'total_time' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 0
	),
	'training_end' => array(
		'type' => 'date',
		'notnull' => false
	),
	'raw_avg_data' => array(
		'type' => 'clob'
	)
);

$ilDB->createTable("rep_robj_xatr_tracking", $fields);
$ilDB->addPrimaryKey("rep_robj_xatr_tracking", array("obj_id", "usr_id"));
?>
<#7>
<?php
$fields = array(
	'obj_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'usr_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'name' => array(
		'type' => 'text',
		'length' => 80,
		'notnull' => false
	),
	'nr' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 0
	)
);

$ilDB->createTable("rep_robj_xatr_tr_disc", $fields);
$ilDB->addPrimaryKey("rep_robj_xatr_tr_disc", array("obj_id", "usr_id", "name"));
?>
<#8>
<?php
$fields = array(
	'obj_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'usr_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'discipline' => array(
		'type' => 'text',
		'length' => 80,
		'notnull' => true
	),
	'module' => array(
		'type' => 'text',
		'length' => 80,
		'notnull' => true
	),
	'final' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'score_crypted' => array(
		'type' => 'clob'
	),
	'tdate' => array(
		'type' => 'date',
		'notnull' => false
	),
	'passed' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	)
);

$ilDB->createTable("rep_robj_xatr_tr_test", $fields);
$ilDB->addPrimaryKey("rep_robj_xatr_tr_test", array("obj_id", "usr_id", "discipline", "module", "final"));
?>
<#9>
<?php
$fields = array(
	'disc_connections' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 0
	),
	'disc_time' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 0
	),
	'first_connection' => array(
		'type' => 'date',
		'notnull' => false
	)
);
foreach ($fields as $f => $def)
{
	$ilDB->addTableColumn("rep_robj_xatr_tr_disc", $f, $def);
}
?>
<#10>
<?php
$fields = array(
	'tries' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 0
	),
	'score' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 0
	)
);
foreach ($fields as $f => $def)
{
	$ilDB->addTableColumn("rep_robj_xatr_tr_test", $f, $def);
}
?>
<#11>
<?php
$fields = array(
	'avg_data' => array(
		'type' => 'clob'
	)
);
foreach ($fields as $f => $def)
{
	$ilDB->addTableColumn("rep_robj_xatr_tracking", $f, $def);
}
?>
<#12>
<?php
$fields = array(
	'status' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	)
);
foreach ($fields as $f => $def)
{
	$ilDB->addTableColumn("rep_robj_xatr_tr_disc", $f, $def);
}
?>
<#13>
<?php
$fields = array(
	'percentage' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	)
);
foreach ($fields as $f => $def)
{
	$ilDB->addTableColumn("rep_robj_xatr_tr_disc", $f, $def);
}
?>
<#14>
<?php
$fields = array(
	'avg_points' => array(
		'type' => 'float',
		'notnull' => true,
		'default' => 0
	),
	'status' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'percentage' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	)
);
foreach ($fields as $f => $def)
{
	$ilDB->addTableColumn("rep_robj_xatr_tracking", $f, $def);
}
?>
<#15>
<?php

$ilDB->dropTableColumn("rep_robj_xatr_tr_test", "score");
$ilDB->dropTableColumn("rep_robj_xatr_tr_test", "score_crypted");

$fields = array(
	'score' => array(
		'type' => 'float',
		'notnull' => true,
		'default' => 99
	)
);
foreach ($fields as $f => $def)
{
	$ilDB->addTableColumn("rep_robj_xatr_tr_test", $f, $def);
}
?>
<#16>
<?php
$fields = array(
	'name_id' => array(
		'type' => 'text',
		'length' => 80,
		'notnull' => true
	),
	'name' => array(
		'type' => 'text',
		'length' => 200,
		'notnull' => false
	)
);

$ilDB->createTable("rep_robj_xatr_md_name", $fields);
$ilDB->addPrimaryKey("rep_robj_xatr_md_name", array("name_id"));
?>
<#17>
<?php
$ilDB->dropPrimaryKey("rep_robj_xatr_md_name");
$fields = array(
	'obj_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 0
	));
foreach ($fields as $f => $def)
{
	$ilDB->addTableColumn("rep_robj_xatr_md_name", $f, $def);
}
$ilDB->addPrimaryKey("rep_robj_xatr_md_name", array("obj_id", "name_id"));
?>

