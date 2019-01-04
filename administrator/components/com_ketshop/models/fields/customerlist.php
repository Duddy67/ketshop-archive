<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


//Script which build the select html tag containing the login name and id of the customers.

class JFormFieldCustomerList extends JFormFieldList
{
  protected $type = 'customerlist';

  protected function getOptions()
  {
    $options = array();
      
    //Get the customers data.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('c.user_id, u.name, u.username')
	  ->from('#__ketshop_customer AS c')
	  ->join('LEFT', '#__users AS u ON c.user_id=u.id')
	  ->order('u.username');
    $db->setQuery($query);
    $customers = $db->loadObjectList();

    //Build the select options.
    foreach($customers as $customer) {
      $options[] = JHtml::_('select.option', $customer->user_id, $customer->username.' - '.$customer->name);
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



