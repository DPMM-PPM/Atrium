<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Repository/PluginSlot/class.ilRepositoryObjectPlugin.php");
 
/**
* Atrium repository object plugin
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* @version $Id$
*
*/
class ilAtriumPlugin extends ilRepositoryObjectPlugin
{
	function getPluginName(): string
	{
		return "Atrium";
	}
	
	protected function uninstallCustom(): void
	 {

		global $ilDB;

		if ($ilDB->tableExists('rep_robj_xatr_data')) {
			$ilDB->dropTable('rep_robj_xatr_data');
		}
		if ($ilDB->tableExists('rep_robj_xatr_md_name')) {
			$ilDB->dropTable('rep_robj_xatr_md_name');
		}
		if ($ilDB->tableExists('rep_robj_xatr_tracking')) {
			$ilDB->dropTable('rep_robj_xatr_tracking');
		}
		if ($ilDB->tableExists('rep_robj_xatr_tr_disc')) {
			$ilDB->dropTable('rep_robj_xatr_tr_disc');
		}
		if ($ilDB->tableExists('rep_robj_xatr_tr_test')) {
			$ilDB->dropTable('rep_robj_xatr_tr_test');
		}
	}
}


?>
