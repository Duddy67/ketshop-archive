<?php

//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/administrator/components/com_ketshop/js');
//Turn the length number into a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));

//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//We need to use Joomla's database class 
require_once (JPATH_BASE.'/libraries/joomla/factory.php');
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//Get the required variable.
$attributeId = JFactory::getApplication()->input->get->get('attribute_id', 0, 'uint');

//Get the group ids linked to the attribute.
$db = JFactory::getDbo();
$query = $db->getQuery(true);

$query->select('group_id')
      ->from('#__ketshop_attrib_group')
      ->where('attrib_id='.(int)$attributeId)
      ->order('group_id');
$db->setQuery($query);
//Get results as an array.
$groupIds = $db->loadColumn();


echo json_encode($groupIds);


