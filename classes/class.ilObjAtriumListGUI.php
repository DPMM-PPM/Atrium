<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Services/Repository/PluginSlot/class.ilObjectPluginListGUI.php";

/**
 * ListGUI implementation for Atrium object plugin. This one
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponfing ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 */
class ilObjAtriumListGUI extends ilObjectPluginListGUI
{
	
	/**
	* Init type
	*/
	function initType(): void
	{
		$this->setType("xatr");
	}
	
	/**
	* Get name of gui class handling the commands
	*/
	function getGuiClass(): string
	{
		return "ilObjAtriumGUI";
	}
	
	/**
	* Get commands
	*/
	function initCommands(): array
	{
		return array
		(
			array(
				"permission" => "read",
				"cmd" => "showContent",
				"default" => true),
			array(
				"permission" => "write",
				"cmd" => "editProperties",
				"txt" => $this->txt("edit"),
				"default" => false),
		);
	}

	/**
	* Get item properties
	*
	* @return	array		array of property arrays:
	*						"alert" (boolean) => display as an alert property (usually in red)
	*						"property" (string) => property name
	*						"value" (string) => property value
	*/
	function getProperties(): array
	{
	include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Atrium/classes/class.ilObjAtriumAccess.php");
	             
		global $lng, $ilUser;

		$props = array();
		
//		$this->plugin->includeClass("class.ilObjAtriumAccess.php");
		if (!ilObjAtriumAccess::checkOnline($this->obj_id))
		{
			$props[] = array("alert" => true, "property" => $this->txt("status"),
				"value" => $this->txt("offline"));
		}

		return $props;
	}
}
?>
