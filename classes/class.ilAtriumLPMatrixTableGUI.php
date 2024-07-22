<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Tracking/classes/class.ilLPTableBaseGUI.php");

/**
 * Atrium matrix table
 *
 */
class ilAtriumLPMatrixTableGUI extends ilLPTableBaseGUI
{
	protected $obj_ids = NULL;
	protected $objective_ids = NULL;
	protected $sco_ids = NULL;

	/**
	 * Constructor
	 */
	function __construct($a_parent_obj, $a_parent_cmd, $ref_id, $a_plugin)
	{
		global $ilCtrl, $lng, $ilAccess, $lng, $ilObjDataCache;
		
		$lng->loadLanguageModule("trac");

		$this->plugin = $a_plugin;
		$this->plugin->includeClass("class.ilAtriumNames.php");
		
		$this->setId("atrsmtx_".$ref_id);
		$this->ref_id = $ref_id;
		$this->obj_id = ilObject::_lookupObjId($ref_id);
		
		$this->plugin->includeClass("class.ilAtriumTrackingData.php");
		$this->disciplines = ilAtriumTrackingData::lookupDisciplines($this->obj_id);

		$this->initFilter();

		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setLimit(9999);
		$this->parseTitle($this->obj_id, "trac_matrix");
	
		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormActionByClass(get_class($this)));
		$this->setRowTemplate("tpl.user_object_matrix_row.html", "Services/Tracking");
		$this->setDefaultOrderField("login");
		$this->setDefaultOrderDirection("asc");
		$this->setShowTemplates(true);

		$this->addColumn($this->lng->txt("login"), "login");

		$labels = $this->getSelectableColumns();
		$selected = $this->getSelectedColumns();
		foreach ($selected as $c)
		{
			$title = $labels[$c]["txt"];
			$tooltip = "";
			if(isset($labels[$c]["icon"]))
			{
				$alt = $lng->txt($labels[$c]["type"]);
				$icon = '<img src="'.$labels[$c]["icon"].'" alt="'.$alt.'" />';
				if(sizeof($selected) > 5)
				{
					$tooltip = $title;
					$title = $icon;
				}
				else
				{
					$title = $icon.' '.$title;
				}
			}
			$this->addColumn($title, $labels[$c]["id"], "", false, "", $tooltip);
		}
		
		$this->setExportFormats(array(self::EXPORT_CSV, self::EXPORT_EXCEL));
	}

	function initFilter()
    {
		global $lng;

		$item = $this->addFilterItemByMetaType("name", ilTable2GUI::FILTER_TEXT);
		$this->filter["name"] = $item->getValue();
	}

	function getSelectableColumns()
	{
		global $ilObjDataCache;

		$columns = array();
		
		if($this->obj_ids === NULL)
		{
			$this->obj_ids = $this->getItems();
		}
		if($this->obj_ids)
		{
			$tmp_cols = array();
			foreach($this->obj_ids as $obj_id)
			{
				if($obj_id == $this->obj_id)
				{
					$parent = array("txt" => $this->lng->txt("status"),
						"default" => true);
				}
				else
				{
					$title = $ilObjDataCache->lookupTitle($obj_id);
					$type = $ilObjDataCache->lookupType($obj_id);
					$icon = ilObject::_getIcon("", "tiny", $type);
					if($type == "sess")
					{
						include_once "Modules/Session/classes/class.ilObjSession.php";
						$sess = new ilObjSession($obj_id, false);
						$title = $sess->getPresentationTitle();
					}
					$tmp_cols[strtolower($title)."#~#obj_".$obj_id] = array("txt" => $title, "icon" => $icon, "type" => $type, "default" => true);
				}
			}
			if(sizeof($this->disciplines))
			{
				foreach($this->disciplines as $k => $disc)
				{
					$icon = ilUtil::getTypeIconPath("fold", $k, "tiny");
					$tmp_cols[strtolower($disc)."#~#objdisc_".$k] =
						array("txt" => ilAtriumNames::lookup($disc, $this->parent_obj->object->getId()), "icon"=>$icon, "default" => true);
				}
			}

			foreach($tmp_cols as $id => $def)
			{
				$id = explode('#~#', $id);
				$columns[$id[1]] = $def;
			}
			unset($tmp_cols);

			if($parent)
			{
				$columns["obj_".$this->obj_id] = $parent;
			}
		}
/*
		$columns["status_changed"] = array("txt" => $this->lng->txt("trac_status_changed"),
			"id" => "status_changed",
			"default" => false);
	*/	
		include_once 'Services/Tracking/classes/class.ilObjUserTracking.php';
		$tracking = new ilObjUserTracking();
		/*
		if($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_LAST_ACCESS))
		{
			$columns["last_access"] = array("txt" => $this->lng->txt("last_access"), 
				"id" => "last_access",
				"default" => false);
		}
		
		if($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_SPENT_SECONDS))
		{
			$columns["spent_seconds"] = array("txt" => $this->lng->txt("trac_spent_seconds"), 
				"id" => "spent_seconds",
				"default" => false);
		}
		*/
		return $columns;
	}

	function getItems()
	{
		global $lng, $tree;

		// $this->determineOffsetAndOrder();

		include_once("./Services/Tracking/classes/class.ilTrQuery.php");
		$collection = ilTrQuery::getObjectIds($this->obj_id, $this->ref_id, true);
		if($collection["object_ids"])
		{
			// we need these for the timing warnings
			$this->ref_ids = $collection["ref_ids"];

			$data = ilTrQuery::getUserObjectMatrix($this->ref_id, $collection["object_ids"], $this->filter["name"],array(),array(),null);
			if($collection["objectives_parent_id"] && $data["users"])
			{
				$objectives = ilTrQuery::getUserObjectiveMatrix($collection["objectives_parent_id"], $data["users"]);
				if($objectives["cnt"])
				{
					$this->objective_ids = array();
					$objective_columns = array();
					foreach($objectives["set"] as $row)
					{
						if(isset($data["set"][$row["usr_id"]]))
						{
							$obj_id = "objtv_".$row["obj_id"];
							$data["set"][$row["usr_id"]]["objects"][$obj_id] = array("status"=>$row["status"]);

							if(!in_array($obj_id, $this->objective_ids))
							{
								$this->objective_ids[$obj_id] = $row["title"];
							}
						}
					}
				}
			}

			$this->plugin->includeClass("class.ilAtriumTrackingData.php");
			foreach(array_keys($data["set"]) as $user_id)
			{
				foreach($this->disciplines as $k => $d)
				{
					$ddata = ilAtriumTrackingData::lookupDisciplineDataForUser($this->obj_id,
						$user_id, $d);
					$data["set"][$user_id]["disc"][$k] = array("status" => $ddata["status"],
						"percentage" => $ddata["percentage"]);
				}
			}
			$this->setMaxCount($data["cnt"]);
			$this->setData($data["set"]);
//var_dump($this->sco_ids);
			return $collection["object_ids"];
		}
		return false;
	}

	function fillRow($a_set) // VINCENT SAYAH
	{
		$this->tpl->setVariable("VAL_LOGIN", $a_set["login"]);
		$obj_status=array();
		foreach ($this->getSelectedColumns() as $c)
		{
		global $ilLog;
		
			switch($c)
			{
				case "last_access":
				case "spent_seconds":
				case 'status_changed':
					$this->tpl->setCurrentBlock($c);
					$this->tpl->setVariable("VAL_".strtoupper($c), $this->parseValue($c, $a_set[$c], ""));
					$this->tpl->parseCurrentBlock();
					break;

				case (substr($c, 0, 4) == "obj_"):
					$obj_id = substr($c, 4);
					if(!isset($a_set["objects"][$obj_id]))
					{
						if (array_sum($obj_status)==0){$data = array("status"=>0);}
						elseif (array_product($obj_status)== pow(2,count($obj_status))){$data["status"]=2;}
						else {$data["status"]=1;}
					}
					else
					{
						$data = $a_set["objects"][$obj_id];
						if($data["percentage"] == "0")
						{
							$data["percentage"] = NULL;
						}
					}
$data["percentage"] = NULL;  // suppression de l'affichage du pourcentage pour l'objet à coté du statut général
					if($data['status'] != ilLpStatus::LP_STATUS_COMPLETED_NUM)
					{
						$timing = $this->showTimingsWarning($this->ref_ids[$obj_id], $a_set["usr_id"]);
						if($timing)
						{
							if($timing !== true)
							{
								$timing = ": ".ilDatePresentation::formatDate(new ilDate($timing, IL_CAL_UNIX));
							}
							else
							{
								$timing = "";
							}
							$this->tpl->setCurrentBlock('warning_img');
							$this->tpl->setVariable('WARNING_IMG', ilUtil::getImagePath('time_warn.png'));
							$this->tpl->setVariable('WARNING_ALT', $this->lng->txt('trac_time_passed').$timing);
							$this->tpl->parseCurrentBlock();
						}
					}

					$this->tpl->setCurrentBlock("objects");
					$this->tpl->setVariable("VAL_STATUS", $this->parseValue("status", $data["status"], ""));
					$this->tpl->setVariable("VAL_PERCENTAGE", $this->parseValue("percentage", $data["percentage"], ""));
					$this->tpl->parseCurrentBlock();
					break;


				case (substr($c, 0, 8) == "objdisc_"):
					$obj_id = substr($c, 8);
					$data = $a_set["disc"][$obj_id];
					$this->tpl->setCurrentBlock("objects");
					$this->tpl->setVariable("VAL_STATUS", $this->parseValue("status", (int) $data["status"], ""));
					if ($data["status"] > 0)
					{
						$this->tpl->setVariable("VAL_PERCENTAGE", $this->parseValue("percentage", $data["percentage"], ""));
						array_push($obj_status,(int)$data["status"]);
						
					}
					else
					{
						$this->tpl->setVariable("VAL_PERCENTAGE", "&nbsp;&nbsp;&nbsp;");
					}
					$this->tpl->parseCurrentBlock();
					break;
			}
		}
	}

	protected function fillHeaderExcel(ilExcel $a_excel, &$a_row) // VINCENT SAYAH
	{
		global $ilObjDataCache;
		
		// $worksheet->write($a_row, 0, $this->lng->txt("login"));
		$a_excel->setBold($a_row, 0, $this->lng->txt("login")); // VINCENT SAYAH
		$labels = $this->getSelectableColumns();
		$cnt = 1;
		foreach ($this->getSelectedColumns() as $c)
		{
			//$worksheet->write($a_row, $cnt, $labels[$c]["txt"]);
			$a_excel->setCell($a_row, $cnt, $labels[$c]["txt"]); // VINCENT SAYAH
			$cnt++;
		}
	}

	protected function fillRowExcel(ilExcel $a_excel, &$a_row, $a_set) // VINCENT SAYAH
	{
		//$worksheet->write($a_row, 0, $a_set["login"]);
		$a_excel->setCell($a_row, 0, $a_set["login"]); // VINCENT SAYAH

		$cnt = 1;
		foreach ($this->getSelectedColumns() as $c)
		{
			include_once("./Services/Tracking/classes/class.ilLearningProgressBaseGUI.php");
			switch($c)
			{
				case "last_access":
				case "spent_seconds":
				case "status_changed":
					$val = $this->parseValue($c, $a_set[$c], "user");
					break;
					
				case (substr($c, 0, 4) == "obj_"):
					$obj_id = substr($c, 4);
					$val = ilLearningProgressBaseGUI::_getStatusText((int)$a_set["objects"][$obj_id]["status"]);
					break;
				
				case (substr($c, 0, 6) == "objtv_"):
				case (substr($c, 0, 7) == "objsco_"):
					$obj_id = $c;
					$val = ilLearningProgressBaseGUI::_getStatusText((int)$a_set["objects"][$obj_id]["status"]);
					break;
					
				case (substr($c, 0, 8) == "objdisc_"):
					$obj_id = substr($c, 8);
					$data = $a_set["disc"][$obj_id];
					$val = $this->parseValue("percentage", $data["percentage"], "");
					break;
			}
			//$worksheet->write($a_row, $cnt, $val);
			$a_excel->setCell($a_row, $cnt, $val); // VINCENT SAYAH
			$cnt++;
		}
	}

	protected function fillHeaderCSV($a_csv)
	{
		global $ilObjDataCache;
		
		$a_csv->addColumn($this->lng->txt("login"));

		$labels = $this->getSelectableColumns();
		foreach ($this->getSelectedColumns() as $c)
		{
			$a_csv->addColumn($labels[$c]["txt"]);
		}

		$a_csv->addRow();
	}

	protected function fillRowCSV($a_csv, $a_set)
	{
		$a_csv->addColumn($a_set["login"]);

		include_once("./Services/Tracking/classes/class.ilLearningProgressBaseGUI.php");
		
		foreach ($this->getSelectedColumns() as $c)
		{
			switch($c)
			{
				case "last_access":
				case "spent_seconds":
				case "status_changed":
					$val = $this->parseValue($c, $a_set[$c], "user");
					break;
					
				case (substr($c, 0, 4) == "obj_"):
					$obj_id = substr($c, 4);
					$val = ilLearningProgressBaseGUI::_getStatusText((int)$a_set["objects"][$obj_id]["status"]);
					break;
				
				case (substr($c, 0, 6) == "objtv_"):
				case (substr($c, 0, 7) == "objsco_"):
					$obj_id = $c;
					$val = ilLearningProgressBaseGUI::_getStatusText((int)$a_set["objects"][$obj_id]["status"]);
					break;
	
				case (substr($c, 0, 8) == "objdisc_"):
					$obj_id = substr($c, 8);
					$data = $a_set["disc"][$obj_id];
					$val = $this->parseValue("percentage", $data["percentage"], "");
					break;

			}
			$a_csv->addColumn($val);
		}

		$a_csv->addRow();
	}
}

?>
