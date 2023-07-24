<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");
include_once("./Services/Tracking/interfaces/interface.ilLPStatusPlugin.php");
include_once("class.ilAtrUtil.php");
include './Customizing/global/plugins/Services/Repository/RepositoryObject/Atrium/vendor/autoload.php';
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Rijndael;
use phpseclib3\Crypt\Random;
/**
 * Application class for Atrium repository object.
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @author Alex Killing <killing@leifos.com>
 *
 * $Id$
 */
class ilObjAtrium extends ilObjectPlugin implements ilLPStatusPluginInterface
{


	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
	}

	/**
	 * Get type.
	 */
	final function initType()
	{
		$this->setType("xatr");
	}
	
	/**
	 * Create object
	 */
	function doCreate()
	{
		global $ilDB;
		
		$ilDB->manipulate("INSERT INTO rep_robj_xatr_data ".
			"(id, is_online, cbt_id, cbt_key) VALUES (".
			$ilDB->quote($this->getId(), "integer").",".
			$ilDB->quote(0, "integer").",".
			$ilDB->quote($this->getCbtId(), "text").",".
			$ilDB->quote($this->getCbtKey(), "text").
			")");
	}
	
	/**
	 * Read data from db
	 */
	function doRead()
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT * FROM rep_robj_xatr_data ".
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
			);
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$this->setOnline($rec["is_online"]);
			$this->setCbtId($rec["cbt_id"]);
			$this->setCbtKey($rec["cbt_key"]);
		}
	}
	
	/**
	 * Update data
	 */
	function doUpdate()
	{
		global $ilDB;
		
		$ilDB->manipulate($up = "UPDATE rep_robj_xatr_data SET ".
			" is_online = ".$ilDB->quote($this->getOnline(), "integer").",".
			" cbt_id = ".$ilDB->quote($this->getCbtId(), "text").",".
			" cbt_key = ".$ilDB->quote($this->getCbtKey(), "text").
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
			);
	}
	
	/**
	 * Delete data from db
	 */
	function doDelete()
	{
		global $ilDB;
		
		$ilDB->manipulate("DELETE FROM rep_robj_xatr_data WHERE ".
			" id = ".$ilDB->quote($this->getId(), "integer")
			);
		
	}
	
	/**
	 * Do Cloning
	 */
	function doClone($a_target_id,$a_copy_id,$new_obj)
	{
		global $ilDB;
		
		$new_obj->setOnline($this->getOnline());
		$new_obj->setCbtId($this->getCbtId());
		$new_obj->setCbtKey($this->getCbtKey());
		$new_obj->update();
	}
	
	/**
	 * Set online
	 *
	 * @param boolean online
	 */
	function setOnline($a_val)
	{
		$this->online = $a_val;
	}
	
	/**
	 * Get online
	 *
	 * @return boolean online
	 */
	function getOnline()
	{
		return $this->online;
	}
	
	/**
	 * Set cbt id
	 *
	 * @param string $a_val CBT ID	
	 */
	function setCbtId($a_val)
	{
		$this->cbt_id = $a_val;
	}
	
	/**
	 * Get cbt id
	 *
	 * @return string CBT ID
	 */
	function getCbtId()
	{
		return $this->cbt_id;
	}
	
	/**
	 * Set cbt key
	 *
	 * @param string $a_val CBT Key	
	 */
	function setCbtKey($a_val)
	{
		$this->cbt_key = $a_val;
	}
	
	/**
	 * Get cbt key
	 *
	 * @return string CBT Key
	 */
	function getCbtKey()
	{
		return $this->cbt_key;
	}
	
	/**
	 * Process LP File
	 *
	 * @param
	 * @return
	 */
	function processLPFile($a_file, $a_tutor = false)
	{
		$rinj = new \phpseclib3\Crypt\Rijndael('ecb');
		$rinj->setKey($this->getCbtKey());
		$rinj->disablePadding();

		global $ilUser, $ilDB;
		
		$this->plugin->includeClass("../exceptions/class.ilAtriumException.php");
		if ($a_file["name"] == "" || !is_file($a_file["tmp_name"]))
		{
			throw new ilAtriumException($this->plugin->txt("no_file_given"));
		}
		
		$content = file_get_contents($a_file["tmp_name"]);
		unlink($a_file["tmp_name"]);
//echo ("<script>console.log('test')</script>");//"avant décryptage, \$content est en : .mb_detect_encoding($content)."<br>";
		if ($content == "")
		{
			throw new ilAtriumException($this->plugin->txt("no_content_found"));
		}
		/* cryptage en rijndael128 ------------------------------------------------------------------------*/
		//$json_string = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->getCbtKey(),	ilAtrUtil::hex2str($content), MCRYPT_MODE_ECB));
		$json_string = trim($rinj->decrypt(ilAtrUtil::hex2str($content)));
		//echo "après decryptage php, \$json_string est en : ".mb_detect_encoding($json_string)."<br>";
		/* cryptage en rijndael 256  ------------------------------------------------------------------------	
		$json_string = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->getCbtKey(), $content, MCRYPT_MODE_ECB));
		/*---------------------------------------------------------------------------------------------------------*/
		if ($json_string == "")
		{
			throw new ilAtriumException($this->plugin->txt("could_not_decrypt_file_content"));
		}
//echo $json_string."<br>";
//echo ("<script>console.log($json_string)</script>");
		$lp = json_decode($json_string);
//echo "last_error_msg ".json_last_error()."<br>";
//echo "lp".$lp;
		if ($lp == null)
		{
			throw new ilAtriumException($this->plugin->txt("could_not_decode_file_content"));
		}

		// check cbt id
		if ($lp[0][7] != $this->getCbtId())
		{
			throw new ilAtriumException($this->plugin->txt("cbt_id_does_not_match"));
		}
		
		// check matriculation data
		$track_mat = $lp[0][3];
		if ($track_mat == "")
		{
			throw new ilAtriumException($this->plugin->txt("no_matriculation_given"));
		}
		
		if (!$a_tutor)	// no tutor, only upload for user himself is possible
		{
			if ($track_mat != $ilUser->getMatriculation())
			{
				throw new ilAtriumException($this->plugin->txt("matriculation_does_not_match"));
			}
			$user_id = $ilUser->getId();
		}
		// tutor, check if matriculation number exists and is unique
		else
		{
			$user_ids = ilAtrUtil::lookupUsersForMatriculation($track_mat);
			if (count($user_ids) == 0)
			{
				throw new ilAtriumException($this->plugin->txt("no_user_found_for_matriculation").": ".$track_mat);
			}
			if (count($user_ids) > 1)
			{
				throw new ilAtriumException($this->plugin->txt("multiple_user_found_for_matriculation").": ".$track_mat);
			}
			$user_id = $user_ids[0];
		}
		
		// parse and save tracking data
		$this->plugin->includeClass("class.ilAtriumTrackingData.php");
		$track = new ilAtriumTrackingData($this->getId(), $user_id);
		$track->parse($lp);
		$track->save();
		
		// update read event
		include_once("./Services/Tracking/classes/class.ilChangeEvent.php");
		ilChangeEvent::_recordReadEvent($this->getType(),
			$this->getRefId(), $this->getId(), $user_id,
			true, $track->getTotalConnections(), $track->getTotalTime());
		
		// hack first access
		if ($track->getTotalFirstConnection() != "")
		{
			$ilDB->manipulate("UPDATE read_event SET ".
				" first_access = ".$ilDB->quote($track->getTotalFirstConnection()." 12:00:00" , "timestamp").
				" WHERE obj_id = ".$ilDB->quote($this->getId(), "integer").
				" AND usr_id = ".$ilDB->quote($user_id, "integer")
				);
		}
		// update lp status
		include_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");
		ilLPStatusWrapper::_updateStatus($this->getId(), $user_id, $this, true);
		
		// write percentage
		
		return $user_id;
	}
	
	
	
	// 
	// LP
	//
	
	public function getLPCompleted()
	{
		$this->plugin->includeClass("class.ilAtriumTrackingData.php");
		return ilAtriumTrackingData::lookupUsersForStatus($this->getId(), 2);
	}
	
	public function getLPNotAttempted()
	{
		// what should returned here? all users?
		return array();
	}
	
	public function getLPFailed()
	{
		return array();
	}
	
	public function getLPInProgress()
	{
		$this->plugin->includeClass("class.ilAtriumTrackingData.php");
		return ilAtriumTrackingData::lookupUsersForStatus($this->getId(), 1);
	}
	
	public function getLPStatusForUser($a_user_id)
	{
		$this->plugin->includeClass("class.ilAtriumTrackingData.php");
		return ilAtriumTrackingData::lookupStatus($this->getId(), $a_user_id);
	}
	
	public function getPercentageForUser($a_user_id)
	{
		$this->plugin->includeClass("class.ilAtriumTrackingData.php");
		return ilAtriumTrackingData::lookupPercentage($this->getId(), $a_user_id);
	}
	
	//
	// Custom tracking functions
	//
	
	/**
	 * Track read event. Only if user has never accessed.
	 *
	 * @param
	 * @return
	 */
	function trackReadEvent()
	{
		global $ilUser;

		include_once("./Services/Tracking/classes/class.ilChangeEvent.php");

		if (!ilChangeEvent::hasAccessed($this->getId(), $ilUser->getId()))
		{
			ilChangeEvent::_recordReadEvent($this->getType(),
				$this->getRefId(), $this->getId(), $ilUser->getId());		
			
			include_once("./Services/Tracking/classes/class.ilLPStatus.php");
			ilLPStatus::setInProgressIfNotAttempted($this->getId(), $ilUser->getId());
		}
	}
	
	/**
	 * Export lp user details as excel
	 *
	 * @param
	 * @return
	 */
	function exportUserDetailsExcel($a_user_id)
	{
		global $lng, $ilDB, $lng, $ilLog;

		include_once("./Services/Calendar/classes/class.ilDatePresentation.php");
		ilDatePresentation::setUseRelativeDates(false);
		
		$this->plugin->includeClass("class.ilAtriumNames.php");
		$lng->loadLanguageModule("trac");
		
		if (ilObject::_lookupType($a_user_id) != "usr")
		{
			return "";
		}
		
		$user = new ilObjUser($a_user_id);
		
		include_once "./Services/Excel/classes/class.ilExcel.php";
		$excelFile = new ilExcel();
		$excelFile->addSheet("Feiulle de note individuelle",true);

		// get tracking data
		include_once("./Services/Tracking/classes/class.ilTrQuery.php");		
		include_once "Services/Tracking/classes/class.ilLPStatusFactory.php";
		$lp_data = ilTrQuery::getObjectsStatusForUser($a_user_id, array($this->getId() => array($this->getRefId())));
		$lp_data = $lp_data[0];
		$set = $ilDB->query("SELECT * FROM read_event ".
			" WHERE obj_id = ".$ilDB->quote($this->getId(), "integer").
			" AND usr_id = ".$ilDB->quote($a_user_id, "integer")
			);
		$re_data = $ilDB->fetchAssoc($set);
		$this->plugin->includeClass("class.ilAtriumTrackingData.php");
		$tr_data = new ilAtriumTrackingData($this->getId(), $a_user_id);
		
		// header row
		$col = 0;
		$row = 1;	
 		$excelFile->setCell($row,0,$this->plugin->txt("general_information"),null);
		$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
		$row++;
		$row++;
		$excelFile->setCell($row,0,$lng->txt("login"),null);
		$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
		$excelFile->setCell($row,1,$user->getLogin(),null);
		$row++;
		$excelFile->setCell($row,0,$lng->txt("name"),null);
		$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
		$excelFile->setCell($row,1,$user->getLastname().", ".$user->getFirstname(),null);
		$row++;
		$excelFile->setCell($row,0,$this->plugin->txt("total_time"),null);
		$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
		$excelFile->setCell($row,1,$this->sec2String((int)$tr_data->getTotalTime()),null);
		$row++;
		$excelFile->setCell($row,0,$this->plugin->txt("total_conn"),null);
		$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
		$excelFile->setCell($row,1,$tr_data->getTotalConnections(),null);
		$row++;
		$excelFile->setCell($row,0,$this->plugin->txt("first_access"),null);
		$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
		//$excelFile->setCell($row,1,ilDatePresentation::formatDate(new ilDate(substr($re_data["first_access"], 0, 10))),null);
		$excelFile->setCell($row,1,substr($re_data["first_access"], 0, 10),null);
		$row++;
		$excelFile->setCell($row,0,$lng->txt("last_access"),null);
		$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
		$excelFile->setCell($row,1,ilDatePresentation::formatDate(new ilDateTime($re_data["last_access"])),null);
		$row++;
		$excelFile->setCell($row,0,$this->plugin->txt("val_percentage"),null);
		$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
		$excelFile->setCell($row,1,$lp_data["percentage"],null);
		$row++;
		$excelFile->setCell($row,0,$this->plugin->txt("general_average"),null);
		$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
		$excelFile->setCell($row,1,ilAtriumTrackingData::lookupAveragePoints($this->getId(), $a_user_id),null);
		
		foreach ($tr_data->getDisciplineData() as $disc)
		{
			$row++;
			$row++;
			$excelFile->setCell($row,0,ilAtriumNames::lookup($disc[0], $this->getId()),null);
			$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
			$row++;
			$excelFile->setCell($row,0,$lng->txt("trac_spent_seconds"),null);
			$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
			$excelFile->setCell($row,1,$this->sec2String((int) $disc[4][0]),null);
			$row++;
			$excelFile->setCell($row,0,$this->plugin->txt("val_percentage"),null);
			$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
			$excelFile->setCell($row,1,(int) $disc[6],null);
			$row++;
			$excelFile->setCell($row,0,$this->plugin->txt("nb_discipline_connexions"),null);
			$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
			$excelFile->setCell($row,1,(int) $disc[4][1],null);
			$row++;
			$excelFile->setCell($row,0,$this->plugin->txt("average"),null);
			$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row));
			$excelFile->setCell($row,1,$disc[8],null);
			$row++;
			$excelFile->setCell($row+1,0,$lng->txt("modules"),null);
			$excelFile->setBold($excelFile->getCoordByColumnAndRow(0, $row+1));
			$excelFile->setCell($row,3,$lng->txt("final_test"),null);
			$excelFile->setBold($excelFile->getCoordByColumnAndRow(3, $row));
			$row++;
			$excelFile->setCell($row,3,$this->plugin->txt("date"),null);
			$excelFile->setBold($excelFile->getCoordByColumnAndRow(3, $row));
			$excelFile->setCell($row,4,$this->plugin->txt("points"),null);
			$excelFile->setBold($excelFile->getCoordByColumnAndRow(4, $row));
			$excelFile->setCell($row,5,$this->plugin->txt("tries"),null);
			$excelFile->setBold($excelFile->getCoordByColumnAndRow(5, $row));
			$modes = array(2 => "PRE", 3 => "FINAL");
			$modules = array();
			foreach ($modes as $k => $m)
			{
				if (is_array($disc[$k]))
				{
					foreach ($disc[$k] as $tk => $test)
					{
						$modules[$test[0]][$m] = $test;
					}
				}
			}
			foreach ($modules as $k => $m)
			{
				$row++;
				$excelFile->setCell($row,0,ilAtriumNames::lookup($k, $this->getId()),null);
				if ($m["PRE"][1] != 99)
				{
					$excelFile->setCell($row,1,$m["PRE"][2],null);
					$excelFile->setCell($row,2,$m["PRE"][1],null);
				}
				if ($m["FINAL"][1] != 99)
				{
					$excelFile->setCell($row,3,$m["FINAL"][2],null);
					$excelFile->setCell($row,4,$m["FINAL"][1],null);
					$excelFile->setCell($row,5,$m["FINAL"][4],null);
				}
			}
		}
		$exc_name = ilUtil::getASCIIFilename($user->getLastName()."_".$user->getFirstname());
		$finalFile=$excelFile->writeToTmpFile();
		ilUtil::deliverFile($finalFile, $exc_name.".xls", "application/vnd.ms-excel");
	}
	
	/**
	 * Seconds to duration string 
	 */
	function sec2String($playtimeseconds) {
		
		$contentseconds = round((($playtimeseconds / 60) - floor($playtimeseconds / 60)) * 60);
		$contentminutes = floor((($playtimeseconds / 3600) - floor($playtimeseconds /3600)) * 60);
		$contenthours = floor($playtimeseconds / 3600);
		if ($contentseconds >=3600)
		{
			$contentseconds -=3600;
			$contenthours++;
		}
		if ($contentseconds >= 60)
		{
			$contentseconds -= 60;
			$contentminutes++;
		}
		return intval($contenthours).':'.intval($contentminutes).':'.str_pad($contentseconds, 2, 0, STR_PAD_LEFT);
	}

}
?>
