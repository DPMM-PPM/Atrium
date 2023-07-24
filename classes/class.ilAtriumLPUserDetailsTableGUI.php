<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");
include_once("./Services/Tracking/classes/class.ilLPTableBaseGUI.php");
include_once "./Services/Tracking/classes/class.ilObjUserTracking.php";

/**
 * TableGUI class for atrium user lp details
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 * @ingroup Services
 */
class ilAtriumLPUserDetailsTableGUI extends ilLPTableBaseGUI
{
	/**
	 * Constructor
	 */
	function __construct($a_parent_obj, $a_parent_cmd, $a_plugin, $a_user)
	{
		global $ilCtrl, $lng, $ilAccess, $lng;
		
		$lng->loadLanguageModule("trac");
		
		parent::__construct($a_parent_obj, $a_parent_cmd);
		
		$this->plugin = $a_plugin;
		$this->user = $a_user;
		
		$this->plugin->includeClass("class.ilAtriumNames.php");
		
		$this->plugin->includeClass("class.ilAtriumTrackingData.php");
		$track = new ilAtriumTrackingData($this->parent_obj->object->getId(), $a_user->getId());
		
		$this->setData($track->getDisciplineData());
		
		$this->setTitle($this->plugin->txt("details").": ".$this->parent_obj->object->getTitle().
			", ".$this->user->getFirstname()." ".$this->user->getLastname());
		
		$this->addColumn($this->plugin->txt("discipline"), "discipline");
		$this->addColumn($lng->txt("trac_spent_seconds"), "time_spent");
		$this->addColumn($lng->txt("trac_percentage"), "percentage");
		$this->addColumn($lng->txt("trac_status"), "status");
		$this->addColumn($this->plugin->txt("average"), "average");
		$this->addColumn($lng->txt("actions"));
		
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
		$this->setRowTemplate($this->plugin->getDirectory()."/templates/tpl.lp_user_details_row.html");

		//$this->addMultiCommand("", $lng->txt(""));
		//$this->addCommandButton("", $lng->txt(""));
	}
	
	/**
	 * Fill table row
	 */
	protected function fillRow($a_set)
	{
		global $lng, $ilCtrl;;

		if (count($a_set[2]) > 0 || count($a_set[3]) > 0)
		{
			$this->tpl->setCurrentBlock("cmd");
			$ilCtrl->setParameter($this->parent_obj, "disc", urlencode($a_set[0]));
			$this->tpl->setVariable("ACTION", $lng->txt("details"));
			$this->tpl->setVariable("ACTION_HREF", $ilCtrl->getLinkTarget($this->parent_obj, "showLPUserDiscDetails"));
			$ilCtrl->setParameter($this->parent_obj, "disc", "");
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setVariable("DISC", ilAtriumNames::lookup($a_set[0], $this->parent_obj->object->getId()));
		$this->tpl->setVariable("DISC_IMG", ilUtil::img(ilUtil::getImagePath("icon_fold.svg")));
		if ($a_set[4][0] > 0)
		{
			$this->tpl->setVariable("TIME_SPENT", $this->parseValue("spent_seconds", $a_set[4][0], ""));
			$this->tpl->setVariable("PERCENTAGE", $this->parseValue("percentage", (int) $a_set[6], ""));
			$this->tpl->setVariable("AVERAGE", $a_set[8]);
		}
		$this->tpl->setVariable("STATUS", $this->parseValue("status", $a_set[7], ""));
		
//var_dump($a_set);
	}

}
?>
