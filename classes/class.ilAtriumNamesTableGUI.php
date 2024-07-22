<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
 * TableGUI class for discipline/module names
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 */
class ilAtriumNamesTableGUI extends ilTable2GUI
{
	/**
	 * Constructor
	 */
	function __construct($a_parent_obj, $a_parent_cmd, $a_plugin, $a_obj_id)
	{
		global $ilCtrl, $lng, $ilAccess, $lng;
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Atrium/classes/class.ilAtriumNames.php");
		$this->pl = $a_plugin;
		$this->obj_id = $a_obj_id;
		//$this->pl->includeClass("class.ilAtriumNames.php");
		
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setData(ilAtriumNames::getNames($this->obj_id));
		$this->setTitle($this->pl->txt("names"));
		
		$this->addColumn($this->pl->txt("key"), "name_id");
		$this->addColumn($this->pl->txt("value"), "name");
		$this->setDefaultOrderField("name_id");
		$this->setDefaultOrderDirection("asc");
		
		$this->setLimit(9999);
		
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
		$this->setRowTemplate($this->pl->getDirectory()."/templates/tpl.names_row.html");

		//$this->addMultiCommand("", $lng->txt(""));
		//$this->addCommandButton("", $lng->txt(""));
	}
	
	/**
	 * Fill table row
	 */
	protected function fillRow($a_set): void
	{
		global $lng;

		$this->tpl->setVariable("KEY", $a_set["name_id"]);
		$this->tpl->setVariable("VAL", $a_set["name"]);
	}

}
?>
