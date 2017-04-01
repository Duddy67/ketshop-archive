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
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//The aim of this Ajax script is to simulate the checking for an unique alias in the table file. 
//This avoid the users to loose the attributes and images they've just set in case of
//error (handle in tables/product.php).

//Get the required variables.
$id = JFactory::getApplication()->input->get->get('id', 0, 'uint');
$catid = JFactory::getApplication()->input->get->get('catid', 0, 'uint');
$name = JFactory::getApplication()->input->get->get('name', '', 'string');
$alias = JFactory::getApplication()->input->get->get('alias', '', 'string');

//Create a sanitized alias, (see stringURLSafe function for details).
$alias = JFilterOutput::stringURLSafe($alias);
//In case no alias has been defined, create a sanitized alias from the name field.
if(empty($alias)) {
  $alias = JFilterOutput::stringURLSafe($name);
}

$db = JFactory::getDbo();
$query = $db->getQuery(true);
//Check for unique alias.
$query->select('COUNT(*)')
      ->from('#__ketshop_product')
      ->where('alias='.$db->Quote($alias).' AND catid='.(int)$catid.' AND id!='.(int)$id);
$db->setQuery($query);

echo json_encode($db->loadResult());

