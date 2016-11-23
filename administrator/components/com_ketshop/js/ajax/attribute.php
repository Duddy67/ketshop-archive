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

//Get the fields and their values of the selected attribute.
$db = JFactory::getDbo();
$query = $db->getQuery(true);
$query->select('field_type_1,value_type,field_value_1,field_text_1,multiselect,field_type_2,field_value_2,field_text_2,published')
      ->from('#__ketshop_attribute')
      ->where('id='.(int)$attributeId);
$db->setQuery($query);
//Get results as an associative array.
$attributeFields = $db->loadAssoc();
//Add empty selected value for each fields as no value has been selected yet.
$attributeFields['selected_value_1'] = '';
$attributeFields['selected_value_2'] = '';


echo json_encode($attributeFields);

