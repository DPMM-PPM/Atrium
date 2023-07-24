<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Atrium utility class 
 *
 * @author Alex Killing <alex.killing@gmx.de>
 */
class ilAtrUtil
{
public static function hex2str($hex) {
    $str = '';
    for($i=0;$i < strlen($hex);$i+=2) {
      $str.=chr(hexdec(substr($hex,$i,2)));
    }
    return $str;
}

	/**
	 * Decrypt score
	 *
	 * @param
	 * @return
	 */
	function decrypt($a_score_encrypted)
	{
		// score will not be encrypted anymore
		return $a_score_encrypted;
		
		if ($a_score_encrypted == "")
		{
			return 0;
		}
		return rand(1,5);
	}

	/**
	 * Lookup users for matriculation number
	 *
	 * @param string $a_mat matricualtion number
	 *
	 * @return array array of user ids (integer)
	 */
	public static function lookupUsersForMatriculation($a_mat)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT usr_id FROM usr_data ".
			" WHERE matriculation = ".$ilDB->quote($a_mat, "text")
			);
		$user_ids = array();
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$user_ids[] = $rec["usr_id"];
		}
		
		return $user_ids;
	}
	
	/**
	 * Get datetime from tracking date
	 *
	 * @param
	 * @return
	 */
	function getDatetime($a_tracking_date)
	{
		$dt = null;
		if ($a_tracking_date != "")
		{
			$sep = "";
			if (is_int(strpos($a_tracking_date, ".")))
			{
				$sep = ".";
			}
			if (is_int(strpos($a_tracking_date, "/")))
			{
				$sep = "/";
			}
			 
			$dt_arr = explode($sep, $a_tracking_date);
			$dt = $dt_arr[2]."-".
				str_pad($dt_arr[1], 2 , "0" , STR_PAD_LEFT)."-".
				str_pad($dt_arr[0], 2 , "0" , STR_PAD_LEFT);
		}
		return $dt;
	}
	
}

?>
