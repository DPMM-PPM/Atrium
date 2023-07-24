<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");

/**
 * User Interface class for Atrium repository object.
 *
 * User interface classes process GET and POST parameter and call
 * application classes to fulfill certain tasks.
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 *
 * $Id$
 *
 * Integration into control structure:
 * - The GUI class is called by ilRepositoryGUI
 * - GUI classes used by this class are ilPermissionGUI (provides the rbac
 *   screens) and ilInfoScreenGUI (handles the info screen).
 *
 * @ilCtrl_isCalledBy ilObjAtriumGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjAtriumGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI
 * @ilCtrl_Calls ilObjAtriumGUI: ilCommonActionDispatcherGUI, ilLearningProgressGUI, ilAtriumLPUsersTableGUI, ilAtriumLPMatrixTableGUI, ilAtriumLPSummaryTableGUI
 */
class ilObjAtriumGUI extends ilObjectPluginGUI
{
	/**
	* Initialisation
	*/
	protected function afterConstructor()
	{
		// anything needed after object has been constructed
		// - example: append my_id GET parameter to each request
		//   $ilCtrl->saveParameter($this, array("my_id"));
	}
	
	/**
	* Get type.
	*/
	final function getType()
	{
		return "xatr";
	}
	
	/**
	* Handles all commmands of this class, centralizes permission checks
	*/
	function performCommand($cmd)
	{
		global $ilCtrl, $ilUser, $ilTabs, $tpl;
		
		$this->plugin->includeClass("class.ilAtrUtil.php");
		
		$next_class = $ilCtrl->getNextClass($this);
		
		$tpl->setDescription($this->object->getDescription());

		switch ($next_class)
		{
			case "ilatriumlpuserstablegui":
				$this->checkPermission("write");
				//$this->ctrl->setParameter($this, "details_id", $this->details_id);
				$this->plugin->includeClass("class.ilAtriumLPUsersTableGUI.php");
			    $table_gui = new ilAtriumLPUsersTableGUI($this, "showLPUsers",
			    	$this->object->getId(), $this->object->getRefId(), $this->plugin);
				$ilCtrl->forwardCommand($table_gui);
				break;
				
			case "ilatriumlpmatrixtablegui":
				$this->checkPermission("write");
				//$this->ctrl->setParameter($this, "details_id", $this->details_id);
				$this->plugin->includeClass("class.ilAtriumLPMatrixTableGUI.php");
			    $table_gui = new ilAtriumLPMatrixTableGUI($this, "showLPMatrix",
			    	$this->object->getRefId(), $this->plugin);
				$ilCtrl->forwardCommand($table_gui);
				break;
				
			case "ilatriumlpsummarytablegui":
				$this->checkPermission("write");
				//$this->ctrl->setParameter($this, "details_id", $this->details_id);
				$this->plugin->includeClass("class.ilAtriumLPSummaryTableGUI.php");
			    $table_gui = new ilAtriumLPSummaryTableGUI($this, "showLPSummary",
			    	$this->object->getRefId(), $this->plugin);
				$ilCtrl->forwardCommand($table_gui);
				break;
				
			default:
				switch ($cmd)
				{
					case "editProperties":		// list all commands that need write permission here
					case "updateProperties":
					case "showLPUsers":
					case "showLPMatrix":
					case "showLPSummary":
					case "editNames":
					case "saveNames":
						$this->checkPermission("write");
						$this->$cmd();
						break;
					
					case "showContent":			// list all commands that need read permission here
					case "uploadLPFile":
					case "showLPUserDetails":
					case "showLPUserDiscDetails":
					case "exportUserDetailsExcel":
					//case "...":
						$this->checkPermission("read");
						$this->$cmd();
						break;
				}
				break;
		}
	}

	/**
	 * After object has been created -> jump to this command
	 */
	function getAfterCreationCmd()
	{
		return "editProperties";
	}

	/**
	 * Get standard command
	 */
	function getStandardCmd()
	{
		return "showContent";
	}
	
//
// DISPLAY TABS
//
	
	/**
	* Set tabs
	*/
	function setTabs()
	{
		global $ilTabs, $ilCtrl, $ilAccess, $lng;
		
		// tab for the "show content" command
		if ($ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showContent"));
		}

		// standard info screen tab
		$this->addInfoTab();

		// a "properties" tab
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
		}

		// a "properties" tab
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("names", $this->txt("names"), $ilCtrl->getLinkTarget($this, "editNames"));
		}

		// learning progress
/*		include_once './Services/Tracking/classes/class.ilLearningProgressAccess.php';
		if (ilLearningProgressAccess::checkAccess($this->object->getRefId(), $is_participant))
		{
			$ilTabs->addTab('learning_progress', $lng->txt("learning_progress"),
				$ilCtrl->getLinkTargetByClass(array('ilobjatriumgui','illearningprogressgui'),'')
			);
		}*/
		
		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"]))
		{
			$ilTabs->addTab('learning_progress', $lng->txt("learning_progress"),
				$ilCtrl->getLinkTarget($this, "showLPUsers"));
		}
		else
		{
			$ilTabs->addTab('learning_progress', $lng->txt("learning_progress"),
				$ilCtrl->getLinkTarget($this, "showLPUserDetails"));
		}

		// standard epermission tab
		$this->addPermissionTab();
	}
	
	/**
	 * setLPSubTabs
	 *
	 * @param
	 * @return
	 */
	function setLPSubTabs($a_active)
	{
		global $ilTabs, $lng, $ilCtrl;
		
		$ilTabs->activateTab("learning_progress");
		
		$ilTabs->addSubTab("lp_users",
			$this->txt("lp_users"),
			$ilCtrl->getLinkTarget($this, "showLPUsers"));

		$ilTabs->addSubTab("lp_matrix",
			$this->txt("lp_matrix"),
			$ilCtrl->getLinkTarget($this, "showLPMatrix"));
/*
		$ilTabs->addSubTab("lp_summary",
			$this->txt("lp_summary"),
			$ilCtrl->getLinkTarget($this, "showLPSummary"));
*/		
		$ilTabs->activateSubTab($a_active);
	}
	
	

// THE FOLLOWING METHODS IMPLEMENT SOME EXAMPLE COMMANDS WITH COMMON FEATURES
// YOU MAY REMOVE THEM COMPLETELY AND REPLACE THEM WITH YOUR OWN METHODS.

//
// Edit properties form
//

	/**
	* Edit Properties. This commands uses the form class to display an input form.
	*/
	function editProperties()
	{
		global $tpl, $ilTabs;
		
		$ilTabs->activateTab("properties");
		$this->initPropertiesForm();
		$this->getPropertiesValues();
		$tpl->setContent($this->form->getHTML());
	}
	
	/**
	* Init  form.
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initPropertiesForm()
	{
		global $ilCtrl;
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
	
		// title
		$ti = new ilTextInputGUI($this->txt("title"), "title");
		$ti->setRequired(true);
		$this->form->addItem($ti);
		
		// description
		$ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
		$this->form->addItem($ta);
		
		// online
		$cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$this->form->addItem($cb);
		
		// cbt id
		$ti = new ilTextInputGUI($this->txt("cbt_id"), "cbt_id");
		$ti->setRequired(true);
		$ti->setMaxLength(80);
		$ti->setSize(40);
		$this->form->addItem($ti);
		
		// cbt key
		$ti = new ilTextInputGUI($this->txt("cbt_key"), "cbt_key");
		$ti->setRequired(true);
		$ti->setMaxLength(80);
		$ti->setSize(40);
		$this->form->addItem($ti);

		$this->form->addCommandButton("updateProperties", $this->txt("save"));
	                
		$this->form->setTitle($this->txt("edit_properties"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}
	
	/**
	* Get values for edit properties form
	*/
	function getPropertiesValues()
	{
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values["online"] = $this->object->getOnline();
		$values["cbt_id"] = $this->object->getCbtId();
		$values["cbt_key"] = $this->object->getCbtKey();
		$this->form->setValuesByArray($values);
	}
	
	/**
	* Update properties
	*/
	public function updateProperties()
	{
		global $tpl, $lng, $ilCtrl;
	
		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->setCbtId($this->form->getInput("cbt_id"));
			$this->object->setCbtKey($this->form->getInput("cbt_key"));
			$this->object->setOnline($this->form->getInput("online"));
			$this->object->update();
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "editProperties");
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}
	
//
// Show content
//

	/**
	* Show content
	*/
	function showContent($a_form = null)
	{
		global $tpl, $ilTabs, $ilUser;
		
		$ilTabs->activateTab("content");
		
		if ($form == null)
		{
			$form = $this->initUploadForm();
		}
		else
		{
			$form = $a_form;
		}
		
		$tpl->setContent($form->getHTML());
		
//		$this->object->trackReadEvent();
		
		ilUtil::sendInfo($this->txt("please_insert_cd_rom"));
	}
	
	/**
	 * Init upload form.
	 *
	 * @param        int        $a_mode        Edit Mode
	 */
	public function initUploadForm()
	{
		global $lng, $ilCtrl;
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();

		// lp file
		$fi = new ilFileInputGUI($this->txt("learning_progress_file"), "lp_file");
		$fi->setSuffixes(array("mpj"));
		$fi->setRequired(true);
		$form->addItem($fi);
		
		
		$form->addCommandButton("uploadLPFile", $this->txt("upload"));
		$form->setTitle($this->txt("upload_learning_progress_file"));
		$form->setFormAction($ilCtrl->getFormAction($this));
		
		return $form;
	}

	/**
	 * Upload LP File
	 *
	 */
	public function uploadLPFile()
	{
		global $tpl, $lng, $ilCtrl, $ilAccess, $ilUser;
	
		$form = $this->initUploadForm();
		
		if ($form->checkInput())
		{
			try
			{
				$user_id = $this->object->processLPFile($_FILES["lp_file"], $ilAccess->checkAccess("write", "", $_GET["ref_id"]));
			}
			catch (ilException $e)
			{
				ilUtil::sendFailure($e->getMessage());
				$this->showContent($form);
				return;
			}
			
			if ($user_id != $ilUser->getId())
			{
				$fn = " (".$lng->txt("obj_usr").": ".ilObjUser::_lookupFullname($user_id).")";
			}
			
			ilUtil::sendSuccess($lng->txt("msg_obj_modified").$fn, true);
			$ilCtrl->redirect($this, "showContent");
		}
		else
		{
			$this->showContent($form);
		}
	}
	
	//
	// LP
	//
	
	/**
	 * Show LP Users
	 *
	 * @param
	 * @return
	 */
	function showLPUsers()
	{
		global $tpl;
		
		$this->setLPSubTabs("lp_users");
		
		$this->plugin->includeClass("class.ilAtriumLPUsersTableGUI.php");
		$table = new ilAtriumLPUsersTableGUI($this, "showLPUsers", $this->object->getId(),
			$this->object->getRefId(), $this->plugin);
		
		$tpl->setContent($table->getHTML());
	}

	/**
	 * Show lp user details
	 *
	 * @param
	 * @return
	 */
	function showLPUserDetails()
	{
		global $tpl, $ilCtrl, $ilToolbar, $lng, $ilAccess, $ilUser, $ilTabs;
		

		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"]))
		{
			$ilToolbar->addButton($lng->txt("back"),
				$ilCtrl->getLinkTarget($this, "showLPUsers"));
			$user = new ilObjUser((int) $_GET["user_id"]);
			$this->setLPSubTabs("lp_users");
			$ilToolbar->addSeparator();
		}
		else
		{
			$_GET["user_id"] = "";
			$user = $ilUser;
			$ilTabs->activateTab("learning_progress");
		}

		$ilCtrl->saveParameter($this, array("user_id"));
		
		$ilToolbar->addButton($this->plugin->txt("export_excel"),
			$ilCtrl->getLinkTarget($this, "exportUserDetailsExcel"));

		
		$this->plugin->includeClass("class.ilAtriumLPUserDetailsTableGUI.php");
		$table = new ilAtriumLPUserDetailsTableGUI($this, "showLPUserDetails", $this->plugin, $user);
		
		$tpl->setContent($table->getHTML());		
	}
	
	/**
	 * Show lp user/discipline details
	 *
	 * @param
	 * @return
	 */
	function showLPUserDiscDetails()
	{
		global $tpl, $ilToolbar, $lng, $ilCtrl, $ilAccess, $ilUser, $ilTabs;
		
		$ilCtrl->saveParameter($this, array("user_id"));
		
		$ilToolbar->addButton($lng->txt("back"),
			$ilCtrl->getLinkTarget($this, "showLPUserDetails"));
		

		if ($ilAccess->checkAccess("write", "", $_GET["ref_id"]))
		{
			$user = new ilObjUser((int) $_GET["user_id"]);
			$this->setLPSubTabs("lp_users");
		}
		else
		{
			$_GET["user_id"] = "";
			$user = $ilUser;
			$ilTabs->activateTab("learning_progress");
		}

		$det_tpl = $this->plugin->getTemplate("tpl.lp_user_disc_detail.html");
		
		$this->plugin->includeClass("class.ilAtriumNames.php");
		
		$this->plugin->includeClass("class.ilAtriumTrackingData.php");
		$track = new ilAtriumTrackingData($this->object->getId(), $user->getId());
		
		$disc_data = $track->getDisciplineData($_GET["disc"]);

		$modes = array("PRE" => $disc_data[2], "FINAL" => $disc_data[3]);
		$final_tests_done = array();

		foreach ($modes as $mode => $tests)
		{
			if (is_array($tests))
			{
				foreach ($tests as $t1k => $test)
				{
					// skip final tests that already have been listed
					// with the corresponding pre-test
					if ($mode == "FINAL" && in_array($t1k, $final_tests_done))
					{
						continue;
					}
					$status = 0;
					$det_tpl->setCurrentBlock("row");
					$det_tpl->setVariable("MOD_TITLE", ilAtriumNames::lookup($test[0], $this->object->getId()));
					$det_tpl->setVariable("MOD_IMG", ilUtil::img(ilUtil::getImagePath("icon_lm.svg")));
					$det_tpl->setVariable($mode."_DATE", $test[2] ? $test[2] : "-");
					if ($test[5] != 99)
					{
						$det_tpl->setVariable($mode."_POINTS", $test[5]);
						if ($status == 0)
						{
							$status = 3; // status echec si la note est diférente de 99 et que le test n'est pas validé
						}
					}
					if ($test[3] > 0)
					{
						$status = 2;
					}
					if ($mode == "FINAL" && $test[4] > 0)
					{
						$det_tpl->setVariable("FINAL_TRIES", $test[4]);
					}
					
					// look for corresponding final test
					if ($mode == "PRE")
					{
						foreach ($modes["FINAL"] as $t2k => $t2)
						{
							if ($t2[0] == $test[0])
							{
								$det_tpl->setVariable("FINAL_DATE", $t2[2] ? $t2[2] : "-");
								if ($t2[5] != 99)
								{
									$det_tpl->setVariable("FINAL_POINTS", $t2[5]);
									if ($status == 0)
									{
										$status = 1;
									}
								}
								if ($t2[4] > 0)
								{
									$det_tpl->setVariable("FINAL_TRIES", $t2[4]);
								}
								if ($t2[3] > 0)
								{
									$status = 2;
								}
								$final_tests_done[] = $t2k;
							}
						}
						reset($modes["FINAL"]);
					}
					$lng->loadLanguageModule("trac");
					include_once("./Services/Tracking/classes/class.ilLearningProgressBaseGUI.php");
					$path = ilLearningProgressBaseGUI::_getImagePathForStatus($status);
					$text = ilLearningProgressBaseGUI::_getStatusText($status);
					$status_img = ilUtil::img($path, $text);

					$det_tpl->setVariable("STATUS", $status_img);
					
					$det_tpl->parseCurrentBlock();
				}
			}
		}
		
		$det_tpl->setVariable("PRE_TEST", $this->plugin->txt("pre_test"));
		$det_tpl->setVariable("FINAL_TEST", $this->plugin->txt("final_test"));
		$det_tpl->setVariable("MODULE", $this->plugin->txt("module"));
		$det_tpl->setVariable("POINTS", $this->plugin->txt("points"));
		$det_tpl->setVariable("DATE", $this->plugin->txt("date"));
		$det_tpl->setVariable("TRIES", $this->plugin->txt("tries"));
		$det_tpl->setVariable("STATUS", $this->plugin->txt("test_status"));
		
		$det_tpl->setVariable("TAB_TITLE", $this->plugin->txt("details").": ".$this->object->getTitle().
			", ".$user->getFirstname()." ".$user->getLastname());

		
		$tpl->setContent($det_tpl->get());		
	}
	
	
	/**
	 * Show LP Matrix
	 *
	 * @param
	 * @return
	 */
	function showLPMatrix()
	{
		global $tpl;

		$this->setLPSubTabs("lp_matrix");
		
		$this->plugin->includeClass("class.ilAtriumLPMatrixTableGUI.php");
		$table = new ilAtriumLPMatrixTableGUI($this, "showLPMatrix", $this->object->getRefId(), $this->plugin);
		
		$tpl->setContent($table->getHTML());

	}
	
	/**
	 * Show LP Summary
	 *
	 * @param
	 * @return
	 */
	function showLPSummary()
	{
		global $tpl;

		$this->setLPSubTabs("lp_summary");
		
		$this->plugin->includeClass("class.ilAtriumLPSummaryTableGUI.php");
		$table = new ilAtriumLPSummaryTableGUI($this, "showLPSummary", $this->object->getRefId(), $this->plugin);
		
		$tpl->setContent($table->getHTML());
	}

	// edit names
	
	/**
	 * Edit names
	 */
	function editNames()
	{
		global $tpl;
		
		$this->plugin->includeClass("class.ilAtriumNamesTableGUI.php");
		$table = new ilAtriumNamesTableGUI($this, "editNames", $this->plugin, $this->object->getId());
		
		$form = $this->initNamesForm();
		$tpl->setContent($form->getHTML()."<br />".$table->getHTML());
	}

	
	/**
	 * Init names form
	 */
	public function initNamesForm()
	{
		global $lng, $ilCtrl, $ilTabs;
		
		$ilTabs->activateTab("names");
		
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		
		// Import CSV names file
		$fi = new ilFileInputGUI($this->plugin->txt("disc_mat_names_file"), "names_csv");
		$fi->setSuffixes(array("csv"));
		$fi->setInfo($this->plugin->txt("disc_mat_names_file_desc"));
		$fi->setRequired(true);
		$form->addItem($fi);
	
		$form->addCommandButton("saveNames", $lng->txt("save"));
	                
		$form->setTitle($this->plugin->txt("names"));
		$form->setFormAction($ilCtrl->getFormAction($this));
		
		return $form;
	}
	
	/**
	 * Save form input (currently does not save anything to db)
	 *
	 */
	public function saveNames()
	{
		global $tpl, $lng, $ilCtrl;
	
		$form = $this->initNamesForm();
		if ($form->checkInput())
		{
			
			$this->plugin->includeClass("class.ilAtriumNames.php");
			$an = new ilAtriumNames();
			$an->parseFile($_FILES["names_csv"]["tmp_name"], $this->object->getId());
			
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "editNames");
		}
		else
		{
			$form->setValuesByPost();
			$tpl->setContent($form->getHtml());
		}
	}
	
	/**
	 * Export lp user details as excel
	 */
	function exportUserDetailsExcel()
	{
		global $ilUser, $ilAccess;
		
		$user_id = (int) $_GET["user_id"];
		if (!$ilAccess->checkAccess("write", "", $_GET["ref_id"]))
		{
			$user_id = $ilUser->getId();
		}

		$this->object->exportUserDetailsExcel($user_id);
	}
	

}
?>
