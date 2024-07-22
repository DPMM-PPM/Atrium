<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Atrium tracking data class
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 */
class ilAtriumTrackingData
{

	protected $personal_data_arr = array();
	protected $discipline_arr = array();
	protected $totals_arr = array();
	protected $average_arr = array(); // crypted average
	protected $avg = array();		// decrypted average
	
	/**
	 * Constructor
	 *
	 * @param
	 * @return
	 */
	function __construct($a_obj_id = 0, $a_usr_id = 0)
	{
		$this->setObjectId($a_obj_id);
		$this->setUserId($a_usr_id);
		if ($a_obj_id > 0 && $a_usr_id > 0)
		{
			$this->read();
		}
	}
	
	/**
	 * Set object id
	 *
	 * @param int $a_val object id	
	 */
	function setObjectId($a_val): void
	{
		$this->obj_id = $a_val;
	}
	
	/**
	 * Get object id
	 *
	 * @return int object id
	 */
	function getObjectId(): int
	{
		return $this->obj_id;
	}
	
	/**
	 * Set user id
	 *
	 * @param int $a_val user id	
	 */
	function setUserId($a_val): void
	{
		$this->user_id = $a_val;
	}
	
	/**
	 * Get user id
	 *
	 * @return int user id
	 */
	function getUserId(): int
	{
		return $this->user_id;
	}

	/**
	 * Get total connections
	 *
	 * @return int nr of total connections
	 */
	function getTotalConnections(): int
	{
		return (int) $this->totals_arr[0];
	}
	
	/**
	 * Get total time
	 *
	 * @return int total time in seconds
	 */
	function getTotalTime(): int
	{
		return (int) $this->totals_arr[1];
	}
	
	/**
	 * Get first connection in total
	 *
	 * @param
	 * @return
	 */
	function getTotalFirstConnection()
	{
		return $this->total_first_connection;
	}
	
	
	/**
	 * Reset
	 */
	function reset()
	{
		$this->personal_data_arr = array();
		$this->discipline_arr = array();
		$this->totals_arr = array();
		$this->average_arr = array();
	}
	
	
	/**
	 * Parse raw tracking array
	 *
	 * @param
	 * @return
	 */
	function parse($a_tracking_arr)
	{
	global $ilLog;
//		$ilLog->write("Dans parse");
		$this->reset();
		
		$cnt = count($a_tracking_arr);
		
		// $a_tracking_arr[0] is cbt id now
		
		// personal data
		$this->personal_data_arr = $a_tracking_arr[0];
		
		// disciplines
		$this->discipline_arr = array();
		for ($i = 1; $i < $cnt - 3; $i++)
		{
			$this->discipline_arr[] = $a_tracking_arr[$i];
		}
//var_dump($this->discipline_arr[2]); exit;
		// totals
		$this->totals_arr = $a_tracking_arr[$cnt-2];
		
		// average
		$this->average_arr = $a_tracking_arr[$cnt-1];
	}

	/**
	 * Read
	 *
	 * @param
	 * @return
	 */
	function read(): void
	{
		global $ilDB, $ilLog;
//	$ilLog->write("dans read");
		$set = $ilDB->query("SELECT * FROM rep_robj_xatr_tracking ".
			" WHERE obj_id = ".$ilDB->quote($this->getObjectId(), "integer").
			" AND usr_id = ".$ilDB->quote($this->getUserId(), "integer")
			);
/*	$ilLog->write("sql : SELECT * FROM rep_robj_xatr_tracking ".
			" WHERE obj_id = ".$ilDB->quote($this->getObjectId(), "integer").
			" AND usr_id = ".$ilDB->quote($this->getUserId(), "integer"));*/
		$rec  = $ilDB->fetchAssoc($set);
	if ($rec==null){$ilLog->write("table vide");}
	else{
		$this->personal_data_arr = unserialize($rec["raw_pers_data"]);
		$this->totals_arr[0] = (int) $rec["total_connections"];
		$this->totals_arr[1] = (int) $rec["total_time"];
		$this->totals_arr[2] = array();
		$this->totals_arr[2][0] = substr(0, 4, $rec["total_time"]);
		$this->totals_arr[2][1] = substr(5, 2, $rec["total_time"]);
		$this->totals_arr[2][2] = substr(8, 2, $rec["total_time"]);
		
		$this->totals_arr[3] = $rec["percentage"];
		$this->totals_arr[4] = $rec["status"];
		$this->totals_arr[5] = $rec["avg_points"];
		$this->average_arr = unserialize($rec["raw_avg_data"]);
		$this->avg = unserialize($rec["avg_data"]);
		}
		$set = $ilDB->query("SELECT * FROM rep_robj_xatr_tr_disc ".
			" WHERE obj_id = ".$ilDB->quote($this->getObjectId(), "integer").
			" AND usr_id = ".$ilDB->quote($this->getUserId(), "integer").
			" ORDER BY nr "
			);
		
		$disc_cnt = 0;
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$this->discipline_arr[$disc_cnt][0] = $rec["name"];
			$this->discipline_arr[$disc_cnt][2] = array();
			$this->discipline_arr[$disc_cnt][3] = array();
			$this->discipline_arr[$disc_cnt][4][0] = $rec["disc_time"];
			$this->discipline_arr[$disc_cnt][4][1] = $rec["disc_connections"];
			$this->discipline_arr[$disc_cnt][4][2] = (($fc = $rec["first_connection"]) != "")
				? substr($fc, 8, 2)."/".substr($fc,5, 2)."/".substr($fc,0, 4)
				: null;
			$set2 = $ilDB->query("SELECT * FROM rep_robj_xatr_tr_test ".
				" WHERE obj_id = ".$ilDB->quote($this->getObjectId(), "integer").
				" AND usr_id = ".$ilDB->quote($this->getUserId(), "integer").
				" AND discipline = ".$ilDB->quote($rec["name"], "text")
				);
			$test_cnt = array(2 => 0, 3 => 0);
			$test_valid = array(2 => 0, 3 => 0);
			while ($rec2 = $ilDB->fetchAssoc($set2))
			{
				$tk = ($rec2["final"])
					? 3
					: 2;
				$td = (($td2 = $rec2["tdate"]) != "")
					? substr($td2, 8, 2)."/".substr($td2, 5, 2)."/".substr($td2, 0, 4)
					: null;
				$this->discipline_arr[$disc_cnt][$tk][$test_cnt[$tk]] =
					array(
						0 => $rec2["module"],
						1 => $rec2["score"],
						2 => $td,
						3 => (int) $rec2["passed"],
						4 => (int) $rec2["tries"],
						5 => $rec2["score"]
						);
				$test_cnt[$tk]++;
				if ($rec2["passed"])
				{
					$test_valid[$tk]++;
				}
			}
			
			// number of valid tests
			$this->discipline_arr[$disc_cnt][5] = $test_valid[3];

			// percentage (of discipline)
			$this->discipline_arr[$disc_cnt][6] = $rec["percentage"];
			
			// status
			$this->discipline_arr[$disc_cnt][7] = $rec["status"];
			
			// average
			$this->discipline_arr[$disc_cnt][8] = $this->avg[$disc_cnt];
			
			$disc_cnt++;
		}
	}
	
	
	/**
	 * Save
	 *
	 * @param
	 * @return
	 */
	function save(): void
	{
		global $ilDB, $ilLog;
//$ilLog->write("Dans save");
		// clean up
		$ilDB->manipulate("DELETE FROM rep_robj_xatr_tracking WHERE ".
			" obj_id = ".$ilDB->quote($this->getObjectId(), "integer").
			" AND usr_id = ".$ilDB->quote($this->getUserId(), "integer")
			);
		$ilDB->manipulate("DELETE FROM rep_robj_xatr_tr_disc WHERE ".
			" obj_id = ".$ilDB->quote($this->getObjectId(), "integer").
			" AND usr_id = ".$ilDB->quote($this->getUserId(), "integer")
			);
		$ilDB->manipulate("DELETE FROM rep_robj_xatr_tr_test WHERE ".
			" obj_id = ".$ilDB->quote($this->getObjectId(), "integer").
			" AND usr_id = ".$ilDB->quote($this->getUserId(), "integer")
			);
		
		// insert tracking data
		$training_end = ($this->totals_arr[2][0] != "" &&
			$this->totals_arr[2][1] != "" &&
			$this->totals_arr[2][2] != "")
			? $this->totals_arr[2][0]."-".$this->totals_arr[2][1]."-".$this->totals_arr[2][2]
			: null;
			
		$avg_data = array();
		foreach ($this->average_arr as $k => $v)
		{
			$avg_data[] = ilAtrUtil::decrypt($v);
		}
					
		// insert disciplines and tests
		$cnt = 0;
		$disc_valid = 0;
		$all_tests = array();
		$all_valid_tests = array();
		$this->total_first_connection = "";
		if (is_array($this->discipline_arr))
		{
			foreach ($this->discipline_arr as $disc)
			{
				if ($disc[0] != "")
				{
					$cnt++;
					$test_valid = array();
					$disc_tests = array();
					
					$first_connection = ilAtrUtil::getDatetime($disc[4][2]);
					if ($this->total_first_connection == "" || $this->total_first_connection > $first_connection
						&& $first_connection != "")
					{						
						$this->total_first_connection = $first_connection;
					}
//echo "<br>-".$first_connection."-";
//echo "<br>-".$this->total_first_connection."-";
//echo "<pre>".print_r($disc)."</pre>";

					// pre-tests
					if (is_array($disc[2]))
					{
						foreach ($disc[2] as $test)
						{
							$test_date = ilAtrUtil::getDatetime($test[2]);
							$ilDB->insert("rep_robj_xatr_tr_test",
								array(
									"obj_id" => array("integer", $this->getObjectId()),
									"usr_id" => array("integer", $this->getUserId()),
									"discipline" => array("text", $disc[0]),
									"module" => array("text", $test[0]),
									"final" => array("integer", 0),
									"tdate" => array("date", $test_date),
									"passed" => array("integer", (int) $test[3]),
									"tries" => array("integer", (int) $test[4]),
									"score" => array("float", ilAtrUtil::decrypt($test[1]))
									)
								);
							$disc_tests[$test[0]] = 1;
							$all_tests[$test[0]] = 1;
							if ((int) $test[3])
							{
								$test_valid[$test[0]] = 1;
								$all_valid_tests[$test[0]] = 1;
							}
						}
					}
					
					// final tests
					if (is_array($disc[3]))
					{
						foreach ($disc[3] as $test)
						{
							$test_date = ilAtrUtil::getDatetime($test[2]);
							$ilDB->insert("rep_robj_xatr_tr_test",
								array(
									"obj_id" => array("integer", $this->getObjectId()),
									"usr_id" => array("integer", $this->getUserId()),
									"discipline" => array("text", $disc[0]),
									"module" => array("text", $test[0]),
									"final" => array("integer", 1),
									"tdate" => array("date", $test_date),
									"passed" => array("integer", (int) $test[3]),
									"tries" => array("integer", (int) $test[4]),
									"score" => array("float", ilAtrUtil::decrypt($test[1]))
									)
								);
							$disc_tests[$test[0]] = 1;
							$all_tests[$test[0]] = 1;
							if ((int) $test[3])
							{
								$test_valid[$test[0]] = 1;
								$all_valid_tests[$test[0]] = 1;
							}
						}	
					}

					// percentage (of discipline)
					$disc_percentage = 0;
					if (count($disc_tests) > 0)
					{
						$disc_percentage =
							round(100 / count($disc_tests) * count($test_valid));
					}
					
					// status
					$disc_status = 0;
					if ($disc[4][0] > 0)
					{
						$disc_status = 2; // completed
						
						// any tests and percentage < 100?
						if (count($disc[3]) > 0 &&
							$disc_percentage < 100)
						{
							//if ($disc_percentage == 0)
								if ($disc[4][2]="") // bascule le status sur en cours dès que la date de première connexion est renseignée
							{
								$disc_status = 0; // no valid tests should mean grey (mail with first "example of use")
							}
							else
							{
								$disc_status = 1; // in progress
							}
						}
					}
					
					// disciplines
					$ilDB->insert("rep_robj_xatr_tr_disc",
						array(
							"obj_id" => array("integer", $this->getObjectId()),
							"usr_id" => array("integer", $this->getUserId()),
							"name" => array("text", $disc[0]),
							"nr" => array("integer", $cnt),
							"disc_connections" => array("integer", (int) $disc[4][1]),
							"disc_time" => array("integer", (int) $disc[4][0]),
							"first_connection" => array("date", $first_connection),
							"status" => array("integer", $disc_status),
							"percentage" => array("integer", $disc_percentage)
							)
						);
//var_dump($test_valid);
//echo "-".$disc_percentage."-";
					if ($disc_status == 2)
					{
						$disc_valid++;
					}
				}
			}
		}

		if (count($all_tests) > 0)
		{
			$total_percentage = round(100 / count($all_tests) * count($all_valid_tests));
		}
		
		$status = 1;
		if ($total_percentage == 100)
		{
			$status = 2;
		}
//exit;
		$avg_points = 0;
		$pcnt = 0;
		$sum = 0;
		//echo("avg_data".$avg_data);
		if (is_array($avg_data))
		{
			foreach ($avg_data as $av)
			{
			//echo ("$av".$av);
				if ($av !== null) // modification pour ne prendre en compte que les matières avec des notes
				{
					
				//echo ("sum=".$sum);
					$sum += $av;
					$pcnt++;
					//$ilLog->write("nb de notes : ".$pcnt." - ".$av." - ".$sum);
				}
			}
			if (count($avg_data) > 0 && $pcnt!=0)
			{
				$avg_points = $sum / $pcnt;
			}
		}
		
		
		// insert tracking data
		$ilDB->insert("rep_robj_xatr_tracking",
			array(
				"obj_id" => array("integer", $this->getObjectId()),
				"usr_id" => array("integer", $this->getUserId()),
				"raw_pers_data" => array("clob", serialize($this->personal_data_arr)),
				"total_connections" => array("integer", (int) $this->totals_arr[0]),
				"total_time" => array("integer", (int) $this->totals_arr[1]),
				"training_end" => array("date", $training_end),
				"raw_avg_data" => array("clob", serialize($this->average_arr)),
				"avg_data" => array("clob", serialize($avg_data)),
				"percentage" => array("integer", $total_percentage),
				"status" => array("integer", $status),
				"avg_points" => array("float", $avg_points)
				)
			);
	}
	
	/**
	 * Get discipline array
	 *
	 * @param
	 * @return
	 */
	function getDisciplineData($a_disc = ""): array
	{
		if ($a_disc == "")
		{
			return $this->discipline_arr;
		}
		else
		{
			foreach ($this->discipline_arr as $k => $disc)
			{
				if ($disc[0] == $a_disc)
				{
					return $disc;
				}
			}
		}
		return array();
	}

	/**
	 * Lookup status
	 *
	 * @param
	 * @return
	 */
	static public function lookupStatus($a_obj_id, $a_usr_id): int
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT status FROM rep_robj_xatr_tracking ".
			" WHERE obj_id = ".$ilDB->quote($a_obj_id, "integer").
			" AND usr_id = ".$ilDB->quote($a_usr_id, "integer")
			);
		$rec = $ilDB->fetchAssoc($set);
		if ($rec!=null){
		return (int) $rec["status"]; }
		else{return 0;} 
	}
	
	/**
	 * Lookup percentage
	 *
	 * @param
	 * @return
	 */
	static function lookupPercentage($a_obj_id, $a_usr_id): int
	{
		global $ilDB, $ilLog;
//	$ilLog->write("dans lookup pourcentage");	
		$set = $ilDB->query("SELECT percentage FROM rep_robj_xatr_tracking ".
			" WHERE obj_id = ".$ilDB->quote($a_obj_id, "integer").
			" AND usr_id = ".$ilDB->quote($a_usr_id, "integer")
			);
			
		$rec = $ilDB->fetchAssoc($set);
		if ($rec!=null){
//	$ilLog->write("pourcentage = ".$rec["percentage"]);
			return (int) $rec["percentage"];}
		else {
			return 0;}
	}
	
	/**
	 * Lookup average points
	 *
	 * @param
	 * @return
	 */
	static function lookupAveragePoints($a_obj_id, $a_usr_id)
	{
		global $ilDB, $ilLog;
//		$ilLog->write("dans lookupAverage ".$a_obj_id." | ".$a_usr_id);
		$set = $ilDB->query("SELECT avg_points FROM rep_robj_xatr_tracking ".
			" WHERE obj_id = ".$ilDB->quote($a_obj_id, "integer").
			" AND usr_id = ".$ilDB->quote($a_usr_id, "integer")
			);
		$rec = $ilDB->fetchAssoc($set);
		if ($rec!=null){
			return $rec["avg_points"];
			}
		else{
			return 0;
			}
	}
	
	/**
	 * Lookup users for status
	 *
	 * @param
	 * @return
	 */
	static function lookupUsersForStatus($a_obj_id, $a_status): array
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT usr_id FROM rep_robj_xatr_tracking ".
			" WHERE obj_id = ".$ilDB->quote($a_obj_id, "integer").
			" AND status = ".$ilDB->quote($a_status, "integer")
			);
		$users = array();
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$users[] = $rec["usr_id"]; 
		}
		return $users;
	}
	
	/**
	 * Lookup disciplines
	 *
	 * @param
	 * @return
	 */
	static function lookupDisciplines($a_obj_id)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT DISTINCT name FROM rep_robj_xatr_tr_disc ".
			" WHERE obj_id = ".$ilDB->quote($a_obj_id, "integer").
			" ORDER BY nr ASC"
			);
		$disciplines = array();
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$disciplines[] = $rec["name"];
		}
		return $disciplines;
	}
	
	/**
	 * Lookup discipline data for user
	 *
	 * @param
	 * @return
	 */
	static function lookupDisciplineDataForUser($a_obj_id, $a_usr_id, $a_disc)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT * FROM rep_robj_xatr_tr_disc ".
			" WHERE obj_id = ".$ilDB->quote($a_obj_id, "integer").
			" AND usr_id = ".$ilDB->quote($a_usr_id, "integer").
			" AND name = ".$ilDB->quote($a_disc, "text")
			);
		$rec = $ilDB->fetchAssoc($set);
//var_dump($rec);	
		return $rec;
	}
	
}

?>
