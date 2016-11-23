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
$priceRuleId = JFactory::getApplication()->input->get->get('pricerule_id', 0, 'uint');
$priceRuleType = JFactory::getApplication()->input->get->get('pricerule_type', '', 'string');

$data = array();

$db = JFactory::getDbo();

//Get the recipient type.
$query = 'SELECT recipient FROM #__ketshop_price_rule WHERE id='.$priceRuleId;
$db->setQuery($query);
$recipientType = $db->loadResult();

//Build query for customer_group recipient type.
if($recipientType == 'customer_group') {
  $query = 'SELECT item_id AS id, title AS name FROM #__ketshop_prule_recipient '. 
	   'JOIN #__usergroups  ON id=item_id '.
	   'WHERE prule_id='.$priceRuleId;
} else { //Build query for customer recipient type.
  $query = 'SELECT item_id AS id, name FROM #__ketshop_prule_recipient '. 
	   'JOIN #__users ON id=item_id '. 
	   'WHERE prule_id='.$priceRuleId;
}

$db->setQuery($query);
$data['recipient'] = $db->loadAssocList();


if($priceRuleType == 'cart') {
  //Get the condition type of the price rule.
  //Warning: condition field must be "backticked" as it's a SQL reserved word.
  $query = 'SELECT `condition` FROM #__ketshop_price_rule WHERE id='.$priceRuleId;
  $db->setQuery($query);
  $conditionType = $db->loadResult();

  //Build the SQL query according to the condition type.
  if($conditionType == 'cart_amount') {
    $query = 'SELECT operator, item_amount FROM #__ketshop_prule_condition '. 
	     'WHERE prule_id='.$priceRuleId;
  } elseif($conditionType == 'product_cat_amount') {
    $query = 'SELECT item_id AS id, title AS name, operator, item_amount FROM #__ketshop_prule_condition '. 
	     'JOIN #__categories ON id=item_id '.
	     'WHERE prule_id='.$priceRuleId;
  } elseif($conditionType == 'product_cat') {
    $query = 'SELECT item_id AS id, title AS name, operator, item_qty FROM #__ketshop_prule_condition '. 
	     'JOIN #__categories ON id=item_id '.
	     'WHERE prule_id='.$priceRuleId;
  } else {
    $query = 'SELECT item_id AS id, name, operator, item_qty FROM #__ketshop_prule_condition '. 
	     'JOIN #__ketshop_product ON id=item_id '. 
	     'WHERE prule_id='.$priceRuleId;
  }

  $db->setQuery($query);
  $data['condition'] = $db->loadObjectList();
} else { //catalog
  //Note: Dynamical target items are only used with catalog price rule type.
  //Regarding cart price rule type, target items are managed with a select html tag.

  //Get the target type of the the price rule.
  $query = 'SELECT target FROM #__ketshop_price_rule WHERE id='.$priceRuleId;
  $db->setQuery($query);
  $targetType = $db->loadResult();

  //Build query for product_cat target type.
  if($targetType == 'product_cat') {
    $query = 'SELECT item_id AS id, title AS name FROM #__ketshop_prule_target '. 
	     'JOIN #__categories ON id=item_id '.
	     'WHERE prule_id='.$priceRuleId;
  } else { //Build query for product or bundle target type.
    $query = 'SELECT item_id AS id, name FROM #__ketshop_prule_target '. 
             //Note: In case of reference product we don't get its product variants.
	     'JOIN #__ketshop_product ON id=item_id AND ref_prod_id=0 '. 
	     'WHERE prule_id='.$priceRuleId;
  }

  $db->setQuery($query);
  $data['target'] = $db->loadAssocList();
}

echo json_encode($data);

