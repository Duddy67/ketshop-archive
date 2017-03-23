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
	      'name', 'o.name',
	      'order_status', 'o.order_status',
	      'cart_status', 'o.cart_status',
	      'payment_status', 'shipping_status',
	      'created', 'o.created',
	      'published', 'o.published',
	      'customer', 'user_id'
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
    if($layout = JRequest::getVar('layout')) {
      $this->context .= '.'.$layout;
    }

    //Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $cartStatus = $app->getUserStateFromRequest($this->context.'.filter.cart_status', 'filter_cart_status');
    $this->setState('filter.cart_status', $cartStatus);

    $orderStatus = $app->getUserStateFromRequest($this->context.'.filter.order_status', 'filter_order_status');
    $this->setState('filter.order_status', $orderStatus);

    $paymentStatus = $app->getUserStateFromRequest($this->context.'.filter.payment_status', 'filter_payment_status');
    $this->setState('filter.payment_status', $paymentStatus);

    $shippingStatus = $app->getUserStateFromRequest($this->context.'.filter.shipping_status', 'filter_shipping_status');
    $this->setState('filter.shipping_status', $shippingStatus);

    $custId = $app->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
    $this->setState('filter.user_id', $custId);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    // List state information.
    parent::populateState('o.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.cart_status');
    $id .= ':'.$this->getState('filter.order_status');
    $id .= ':'.$this->getState('filter.payment_status');
    $id .= ':'.$this->getState('filter.shipping_status');
    $id .= ':'.$this->getState('filter.user_id');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'o.id, o.name, o.created, o.cart_status,'.
				   'o.published, o.user_id, o.order_status, o.payment_status,'.
				   'o.checked_out, o.checked_out_time'));

    $query->from('#__ketshop_order AS o');

    $query->select('d.status AS shipping_status');
    $query->join('LEFT', '#__ketshop_delivery AS d ON d.order_id = o.id');

    //Get the customer name.
    $query->select('u.name AS customer');
    $query->join('LEFT', '#__users AS u ON u.id = o.user_id');

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=o.checked_out');

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

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('o.published= '.(int)$published);
    }
    elseif($published === '') {
      $query->where('(o.published IN (0, 1))');
    }

    //Filter by cart status.
    $cartStatus = $this->getState('filter.cart_status');
    if(!empty($cartStatus)) {
      $query->where('o.cart_status='.$db->Quote($cartStatus));
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

    //Filter by customer.
    $custId = $this->getState('filter.user_id');
    if(is_numeric($custId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('o.user_id'.$type.(int) $custId);
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }


  //Build the order status list for the filter.
  public function getOrderStatus()
  {
    $completed = new JObject;
    $completed->value = 'completed';
    $completed->text = JText::_('COM_KETSHOP_OPTION_COMPLETED_STATUS');

    $pending = new JObject;
    $pending->value = 'pending';
    $pending->text = JText::_('COM_KETSHOP_OPTION_PENDING_STATUS');

    $other = new JObject;
    $other->value = 'other';
    $other->text = JText::_('COM_KETSHOP_OPTION_OTHER_STATUS');

    $error = new JObject;
    $error->value = 'error';
    $error->text = JText::_('COM_KETSHOP_OPTION_ERROR_STATUS');

    $cancelled = new JObject;
    $cancelled->value = 'cancelled';
    $cancelled->text = JText::_('COM_KETSHOP_OPTION_CANCELLED_STATUS');

    $status = array();
    $status[] = $completed;
    $status[] = $pending;
    $status[] = $cancelled;
    $status[] = $error;
    $status[] = $other;

    return $status;
  }


  //Build the payment status list for the filter.
  public function getPaymentStatus()
  {
    $completed = new JObject;
    $completed->value = 'completed';
    $completed->text = JText::_('COM_KETSHOP_OPTION_COMPLETED_STATUS');

    $pending = new JObject;
    $pending->value = 'pending';
    $pending->text = JText::_('COM_KETSHOP_OPTION_PENDING_STATUS');

    $error = new JObject;
    $error->value = 'error';
    $error->text = JText::_('COM_KETSHOP_OPTION_ERROR_STATUS');

    $unfinished = new JObject;
    $unfinished->value = 'unfinished';
    $unfinished->text = JText::_('COM_KETSHOP_OPTION_UNFINISHED_STATUS');

    $status = array();
    $status[] = $completed;
    $status[] = $pending;
    $status[] = $error;
    $status[] = $unfinished;

    return $status;
  }


  //Build the shipping status list for the filter.
  public function getShippingStatus()
  {
    $completed = new JObject;
    $completed->value = 'completed';
    $completed->text = JText::_('COM_KETSHOP_OPTION_COMPLETED_STATUS');

    $pending = new JObject;
    $pending->value = 'pending';
    $pending->text = JText::_('COM_KETSHOP_OPTION_PENDING_STATUS');

    $cancelled = new JObject;
    $cancelled->value = 'cancelled';
    $cancelled->text = JText::_('COM_KETSHOP_OPTION_CANCELLED_STATUS');

    $noShipping = new JObject;
    $noShipping->value = 'no_shipping';
    $noShipping->text = JText::_('COM_KETSHOP_OPTION_NO_SHIPPING_STATUS');

    $status = array();
    $status[] = $completed;
    $status[] = $pending;
    $status[] = $cancelled;
    $status[] = $noShipping;

    return $status;
  }


  //Build the cart status list for the filter.
  public function getCartStatus()
  {
    $completed = new JObject;
    $completed->value = 'completed';
    $completed->text = JText::_('COM_KETSHOP_OPTION_COMPLETED_STATUS');

    $pending = new JObject;
    $pending->value = 'pending';
    $pending->text = JText::_('COM_KETSHOP_OPTION_PENDING_STATUS');

    $status = array();
    $status[] = $completed;
    $status[] = $pending;

    return $status;
  }
}


