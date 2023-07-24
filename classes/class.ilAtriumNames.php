<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Handling discipline and module names
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$ 
 */
class ilAtriumNames
{
	/**
	 * Parse input file
	 *
	 * @param
	 * @return
	 */
	function parseFile($a_file, $a_obj_id)
	{
		global $ilDB;
		
		$tmp_file = ilUtil::ilTempnam();
		move_uploaded_file($a_file, $tmp_file);
		
		if (is_file($tmp_file))
		{
			$ilDB->manipulate("DELETE FROM rep_robj_xatr_md_name WHERE obj_id = ".$ilDB->quote($a_obj_id, "integer"));
			
			$content = file_get_contents($tmp_file);

			$lines = explode("\n", $content);
			foreach ($lines as $line)
			{
				$line = trim($line);
				$fields = explode(";", $line);
				$key = trim($fields[0]);
				$val = trim($fields[1]);
				
				if ($key != "" && $val != "")
				{
					$ilDB->manipulate("INSERT INTO rep_robj_xatr_md_name ".
						"(name_id, name, obj_id) VALUES (".
						$ilDB->quote($key, "text").",".
						$ilDB->quote($val, "text").",".
						$ilDB->quote($a_obj_id, "integer").
						")");
				}
			}
			unlink($tmp_file);
		}
	}
	
	/**
	 * Lookup name
	 *
	 * @param
	 * @return
	 */
	static function lookup($a_key, $a_obj_id)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT * FROM rep_robj_xatr_md_name ".
			" WHERE name_id = ".$ilDB->quote($a_key, "text").
			" AND obj_id = ".$ilDB->quote($a_obj_id, "integer")
			);
		$rec = $ilDB->fetchAssoc($set);

		if ($rec["name"] != "")
		{
			return $rec["name"];
		}
		else
		{
			return "-".$a_key."-";
		}
	}
	
	/**
	 * Get names
	 *
	 * @param
	 * @return
	 */
	static function getNames($a_obj_id)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT * FROM rep_robj_xatr_md_name ".
			" WHERE obj_id = ".$ilDB->quote($a_obj_id, "integer").
			" ORDER BY name_id "
			);
		$names = array();
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$names[] = $rec;
		}
		return $names;
	}
	
	
}

?>
