<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class KetshopModelOrders extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'o.id',
	      'order_nb',
	      'order_status', 'o.order_status',
	      'payment_status',
	      'shipping_status',
	      'created', 'o.created',
      );
    }

    parent::__construct($config);
  }


  protected function populateState($ordering = null, $direction = null)
  {
    // Initialise variables.
    $app = JFactory::getApplication();
    $session = JFactory::getSession();

    // Adjust the context to support modal layouts.
    if($layout = JFactory::getApplication()->input->get('layout')) {
      $this->context .= '.'.$layout;
    }

    //Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $orderStatus = $app->getUserStateFromRequest($this->context.'.filter.order_status', 'filter_order_status');
    $this->setState('filter.order_status', $orderStatus);

    $paymentStatus = $app->getUserStateFromRequest($this->context.'.filter.payment_status', 'filter_payment_status');
    $this->setState('filter.payment_status', $paymentStatus);

    $shippingStatus = $app->getUserStateFromRequest($this->context.'.filter.shipping_status', 'filter_shipping_status');
    $this->setState('filter.shipping_status', $shippingStatus);

    // List state information.
    parent::populateState('o.created', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.order_status');
    $id .= ':'.$this->getState('filter.payment_status');
    $id .= ':'.$this->getState('filter.shipping_status');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'o.id, o.name AS order_nb, o.created,o.published, o.user_id,'.
				   'o.order_status, t.status AS payment_status, d.status AS shipping_status'));

    $query->from('#__ketshop_order AS o');

    //Join over delivery and transaction tables.
    $query->join('LEFT', '#__ketshop_delivery AS d ON o.id=d.order_id');
    $query->join('LEFT', '#__ketshop_transaction AS t ON o.id=t.order_id');

    //Get the user.
    $user = JFactory::getUser();

    //Display only published orders.
    $query->where('o.published = 1 AND o.user_id = '.(int)$user->id);
    $query->where('o.user_id = '.(int)$user->id);
    $query->where('o.cart_status = "completed"');

    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('o.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(o.name LIKE '.$search.')');
      }
    }

    //Filter by order status.
    $orderStatus = $this->getState('filter.order_status');
    if(!empty($orderStatus)) {
      $query->where('o.order_status='.$db->Quote($orderStatus));
    }

    //Filter by payment status.
    $paymentStatus = $this->getState('filter.payment_status');
    if(!empty($paymentStatus)) {
      $query->where('t.status='.$db->Quote($paymentStatus));
    }

    //Filter by shipping status.
    $shippingStatus = $this->getState('filter.shipping_status');
    if(!empty($shippingStatus)) {
      $query->where('d.status='.$db->Quote($shippingStatus));
    }


    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'order_nb');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


