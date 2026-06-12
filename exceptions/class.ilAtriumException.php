<?php

/* Copyright (c) 1998-2013 Leifos GmbH GPL2 */
 
require_once ILIAS_ABSOLUTE_PATH.'/components/ILIAS/Exceptions/classes/class.ilException.php'; 
 
/** 
 * Atrium exception class 
 * 
 * @version $Id$ 
 * 
 */
class ilAtriumException extends ilException
{
	/** 
	* Constructor
	* 
	* @param string $a_message message
	*/
	public function __construct($a_message)
	{
		parent::__construct($a_message);
	}
}
?>
