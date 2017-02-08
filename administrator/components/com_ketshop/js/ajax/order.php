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

//Get required variables.
$orderId = JFactory::getApplication()->input->get->get('order_id', 0, 'uint');
$prodId = JFactory::getApplication()->input->get->get('product_id', 0, 'uint');
$optId = JFactory::getApplication()->input->get->get('option_id', 0, 'uint');
$task = JFactory::getApplication()->input->get->get('task', '', 'string');
$newQty = JFactory::getApplication()->input->get->get('new_qty', 0, 'uint');

$data = array();

$db = JFactory::getDbo();
$query = $db->getQuery(true);

if($task == 'change_quantity') {
  $query->select('quantity')
	->from('#__ketshop_order_prod')
	->where('order_id='.(int)$orderId)
	->where('prod_id='.(int)$prodId)
	->where('opt_id='.(int)$optId);
  $db->setQuery($query);
  //
  if($newQty == $db->loadResult()) {
    $data['message'] = 'no change';
    echo json_encode($data);
    return;
  }
}


echo json_encode($data);

