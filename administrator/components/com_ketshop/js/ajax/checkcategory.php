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
//Required for the isDuplicateCat funtion.
require_once (JPATH_BASE.'/administrator/components/com_ketshop/helpers/ketshop.php');
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//The aim of this Ajax script is to simulate the setting of the product category. 
//This avoid the users to loose the attributes and images they've just set in case of
//error (handle in tables/product.php).

//Get the required variables.
$id = JFactory::getApplication()->input->get->get('id', 0, 'uint');
$catid = JFactory::getApplication()->input->get->get('catid', 0, 'uint');
$refProdId = JFactory::getApplication()->input->get->get('ref_prod_id', 0, 'uint');
$variants = JFactory::getApplication()->input->get->get('variants', 0, 'uint');

$return = 1;

if(KetshopHelper::isDuplicateCat($id, $refProdId, $catid)) {
  $return = 0;
}

echo json_encode($return);

