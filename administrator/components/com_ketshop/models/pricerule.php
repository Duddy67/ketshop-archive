<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');


class KetshopModelPricerule extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_KETSHOP';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Pricerule', $prefix = 'KetshopTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_ketshop.pricerule', 'pricerule', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_ketshop.edit.pricerule.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  /**
   * Prepare and sanitise the table data prior to saving.
   *
   * @param   JTable  $table  A JTable object.
   *
   * @return  void
   *
   * @since   1.6
   */
  protected function prepareTable($table)
  {
    // Set the publish date to now
    if($table->published == 1 && (int)$table->publish_up == 0) {
      $table->publish_up = JFactory::getDate()->toSql();
    }

    if($table->published == 1 && intval($table->publish_down) == 0) {
      $table->publish_down = $this->getDbo()->getNullDate();
    }
  }


  public function getRecipientData($pk = null, $recipientType) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    //Sets attribute and table names according to the recipient type. 
    $name = 'name AS item_name';
    $table = '#__users';
    if($recipientType == 'customer_group') {
      $name = 'title AS item_name';
      $table = '#__usergroups';
    }

    $query->select('item_id,'.$name)
	  ->from('#__ketshop_prule_recipient')
	  ->join('INNER', $table.' ON id=item_id')
	  ->where('prule_id='.(int)$pk);
    $db->setQuery($query);

    return $db->loadAssocList();
  }


  public function getTargetData($pk = null, $targetType) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Sets attribute and table names according to the target type. 
    $name = 'CONCAT_WS(" ", p.name, pv.name) AS item_name';
    $table = '#__ketshop_product AS p';

    if($targetType == 'product_cat') {
      $name = 'c.title AS item_name';
      $table = '#__categories AS c';
    }

    $query->select('pt.item_id, pt.var_id, '.$name)
	  ->from('#__ketshop_prule_target AS pt')
	  ->join('INNER', $table.' ON id=pt.item_id');

    if($targetType == 'product') {
      // Gets the variant name from the variant table.
      $query->join('INNER', '#__ketshop_product_variant AS pv ON pv.prod_id=pt.item_id AND pv.var_id=pt.var_id');
    }

    $query->where('pt.prule_id='.(int)$pk);
    $db->setQuery($query);

    return $db->loadAssocList();
  }


  public function getConditionData($pk = null, $conditionType) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    //Build the SQL query according to the condition type.
    $join = '';
    if($conditionType == 'product_cat_amount') {
      $select = 'item_id, title AS item_name, operator, TRUNCATE(item_amount, 2) AS item_amount';
      $join = '#__categories ON id=item_id';
    }
    elseif($conditionType == 'product_cat') {
      $select = 'item_id, title AS item_name, operator, item_qty';
      $join = '#__categories ON id=item_id';
    }
    else {
      $select = 'item_id, var_id, name AS item_name, operator, item_qty';
      $join = '#__ketshop_product ON id=item_id';
    }

    $query->select($select)
	  ->from('#__ketshop_prule_condition');

    if(!empty($join)) {
      $query->join('INNER', $join);
    }

    $query->where('prule_id='.(int)$pk);
    $db->setQuery($query);

    return $db->loadObjectList();
  }
}

