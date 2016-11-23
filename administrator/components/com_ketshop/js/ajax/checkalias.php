<?php

//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/administrator/components/com_ketshop/js');
//Turn the length number into a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_BASE.'/administrator/components/com_ketshop');

//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
require_once (JPATH_BASE.'/libraries/joomla/factory.php');
//We need to access to product table class.
require_once (JPATH_COMPONENT_ADMINISTRATOR.'/tables/product.php');
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//The aim of this Ajax script is to simulate the setting of the item alias. 
//This avoid the users to loose the attributes and images they've just set in case of
//unique alias error (handle in tables/product.php).

//Get the required variables.
$task = JFactory::getApplication()->input->get->get('task', '', 'string');
$id = JFactory::getApplication()->input->get->get('id', 0, 'uint');
$catid = JFactory::getApplication()->input->get->get('catid', 0, 'uint');
$name = JFactory::getApplication()->input->get->get('name', '', 'string');
$alias = JFactory::getApplication()->input->get->get('alias', '', 'string');

$return = 1;

//Note: name and alias variables have previously been encoded with the encodeURIComponent javascript function.
$name = urldecode($name);
$alias = urldecode($alias);

if($task == 'product.save2variant' || $task == 'product.save2copy') {
  //Get the name of the original item.
  $db = JFactory::getDbo();
  $query = $db->getQuery(true);
  $query->select('name')
	->from('#__ketshop_product')
	->where('id='.(int)$id);
  $db->setQuery($query);
  $origName = $db->loadResult();

  //The name is untouched. We can leave as it will be safely incremented later in the model. 
  if($name == $origName) {
    echo json_encode($return);
    return;
  }
  //The name is different so we reset the alias and start testing.
  else {
    $alias = '';
  }
}

//Run the simulation.

//First we clean up the alias.
$alias = trim($alias);

//No alias has been set by the user.
if(empty($alias)) {
  //Created a sanitized alias from the name field, (see stringURLSafe function for details).
  $alias = JFilterOutput::stringURLSafe($name);
}
else {
  //Make sure the alias is properly set.
  $alias = JFilterOutput::stringURLSafe($alias);
}

// Verify that the alias is unique
$table = JTable::getInstance('Product', 'KetshopTable');
if($table->load(array('alias' => $alias, 'catid' => $catid)) && ($table->id != $id || $id == 0)) {
  $return = 0;
}

echo json_encode($return);

