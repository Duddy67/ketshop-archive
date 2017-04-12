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
$query = $db->getQuery(true);

//Get the recipient type.
$query->select('recipient')
      ->from('#__ketshop_price_rule')
      ->where('id='.(int)$priceRuleId);
$db->setQuery($query);
$recipientType = $db->loadResult();
$query->clear();

//Build query for customer_group recipient type.
$name = 'name';
$table = '#__users';
if($recipientType == 'customer_group') {
  $name = 'title AS name';
  $table = '#__usergroups';
}

$query->select('item_id AS id,'.$name)
      ->from('#__ketshop_prule_recipient')
      ->join('INNER', $table.' ON id=item_id')
      ->where('prule_id='.(int)$priceRuleId);
$db->setQuery($query);
$data['recipient'] = $db->loadAssocList();
$query->clear();


if($priceRuleType == 'cart') {
  //Get the condition type of the price rule.
  //Warning: condition field must be "backticked" as it's a SQL reserved word.
  $query->select($db->quoteName('condition'))
	->from('#__ketshop_price_rule')
	->where('id='.(int)$priceRuleId);
  $db->setQuery($query);
  $conditionType = $db->loadResult();
  $query->clear();

  //Build the SQL query according to the condition type.
  $join = '';
  if($conditionType == 'total_prod_amount') {
    $select = 'item_id AS id, operator, item_amount';
  } elseif($conditionType == 'total_prod_qty') {
    $select = 'item_id AS id, operator, item_qty';
  } elseif($conditionType == 'product_cat_amount') {
    $select = 'item_id AS id, title AS name, operator, item_amount';
    $join = '#__categories ON id=item_id';
  } elseif($conditionType == 'product_cat') {
    $select = 'item_id AS id, title AS name, operator, item_qty';
    $join = '#__categories ON id=item_id';
  } else {
    $select = 'item_id AS id, name, operator, item_qty';
    $join = '#__ketshop_product ON id=item_id';
  }

  $query->select($select)
	->from('#__ketshop_prule_condition');

  if(!empty($join)) {
    $query->join('INNER', $join);
  }

  $query->where('prule_id='.(int)$priceRuleId);
  $db->setQuery($query);
  $data['condition'] = $db->loadObjectList();
  $query->clear();
} else { //catalog
  //Note: Dynamical target items are only used with catalog price rule type.
  //Regarding cart price rule type, target items are managed with a select html tag.

  //Get the target type of the the price rule.
  $query->select('target')
	->from('#__ketshop_price_rule')
	->where('id='.(int)$priceRuleId);
  $db->setQuery($query);
  $targetType = $db->loadResult();
  $query->clear();

  //Build query for product_cat target type.
  $name = 'name';
  $table = '#__ketshop_product';
  if($targetType == 'product_cat') {
    $name = 'title AS name';
    $table = '#__categories';
  }

  $query->select('item_id AS id,'.$name)
	->from('#__ketshop_prule_target')
	->join('INNER', $table.' ON id=item_id')
	->where('prule_id='.(int)$priceRuleId);
  $db->setQuery($query);
  $data['target'] = $db->loadAssocList();
}

echo json_encode($data);

