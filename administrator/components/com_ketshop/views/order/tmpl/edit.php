<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tabstate');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.modal');

//Build a status array.
$status = array();
$status['completed'] = JText::_('COM_KETSHOP_OPTION_COMPLETED_STATUS');
$status['pending'] = JText::_('COM_KETSHOP_OPTION_PENDING_STATUS');
$status['other'] = JText::_('COM_KETSHOP_OPTION_OTHER_STATUS');
$status['cancelled'] = JText::_('COM_KETSHOP_OPTION_CANCELLED_STATUS');
$status['error'] = JText::_('COM_KETSHOP_OPTION_ERROR_STATUS');
$status['no_shipping'] = JText::_('COM_KETSHOP_OPTION_NO_SHIPPING_STATUS');
$status['unfinished'] = JText::_('COM_KETSHOP_OPTION_UNFINISHED_STATUS');
$status['undefined'] = JText::_('COM_KETSHOP_OPTION_UNDEFINED_STATUS');

$currency = $this->item->currency;

if($this->item->cart_status != 'pending') {
  //Calculate the order total amount.
  $orderAmount = UtilityHelper::floatFormat($this->item->fnl_crt_amt_incl_tax + $this->delivery->final_shipping_cost);
}

//Build the url used for transaction delivery and customer links.
$url = JURI::base();
$customerUrl = 'index.php?option=com_ketshop&amp;view=customer&amp;layout=modal&amp;tmpl=component&id='.$this->item->cust_id;

$canDo = KetshopHelper::getActions();
?>
<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'order.cancel' || document.formvalidator.isValid(document.id('order-form'))) {
    Joomla.submitform(task, document.getElementById('order-form'));
  }
  else {
    alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
  }
}
</script>

<div id="ajax-waiting-screen" style="visibility: hidden;display: none;">
  <img src="../media/com_ketshop/images/ajax-loader.gif" width="31" height="31" />
</div>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=order&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="order-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_KETSHOP_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span3">
	  <div class="form-vertical">
	    <?php 
		  echo $this->form->getControlGroup('order_status');
		  $this->form->setValue('cart_status', null, $status[$this->item->cart_status]);
		  echo $this->form->getControlGroup('cart_status');
		  $this->form->setValue('customer_name', null, $this->item->cust_name);
		  echo $this->form->getControlGroup('customer_name'); 
		  echo $this->form->getControlGroup('vendor_note'); 
		  echo $this->form->getControlGroup('customer_note'); ?>
		  <span class="btn">
		  <a href="<?php echo $customerUrl; ?>" class="modal" rel="{handler: 'iframe', size: {x: 800, y: 500}}">
		  <span class="icon-notification-2"></span><?php echo JText::_('COM_KETSHOP_FIELD_INFORMATION_LABEL'); ?></a></span>
	  </div>
	</div>
	<div class="span8">
	  <div class="form-vertical purchase">
	    <?php echo $this->loadTemplate('details'); ?>
	  </div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php //Don't display transaction item if something wrong occured while the user was about to pay or if cart is still pending. ?>
      <?php if($this->item->cart_status != 'pending' && $this->item->payment_status != 'unfinished') : ?>
	<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'transaction', JText::_('COM_KETSHOP_FIELDSET_TRANSACTION_DETAIL', true)); ?>
        <?php echo $this->form->getControlGroup('payment_status'); ?>
	    <?php echo JLayoutHelper::render('edit.transaction', $this->transaction, JPATH_COMPONENT.'/layouts/'); ?>
	<?php echo JHtml::_('bootstrap.endTab'); ?>
      <?php endif; ?>

      <?php //Don't display shipping item if no shipping is needed or if cart is still pending. ?>
      <?php if($this->item->cart_status != 'pending' && $this->delivery->status != 'no_shipping') : ?>
	<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'delivery', JText::_('COM_KETSHOP_FIELDSET_DELIVERY_DETAIL', true)); ?>
	    <?php echo JLayoutHelper::render('edit.delivery', $this->delivery, JPATH_COMPONENT.'/layouts/'); ?>
	<?php echo JHtml::_('bootstrap.endTab'); ?>
      <?php endif; ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span4">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('created'); 
		  echo $this->form->getControlGroup('modified'); 
		  echo $this->form->getControlGroup('modified_by'); 
		  echo $this->form->getControlGroup('id'); 
	    ?>
	  </div>
	</div>
	<div class="span6">
	  <div class="form-vertical">
	    <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
	  </div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php if($this->billingAddress) : ?>
	<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'billing-address', JText::_('COM_KETSHOP_BILLING_ADDRESS_TITLE', true)); ?>
	<div class="row-fluid form-horizontal-desktop">
	  <div class="span6">
	    <div class="form-vertical">

	      <table class="table">
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_STREET_LABEL'); ?>
	      </td><td>
	      <?php echo $this->billingAddress['street']; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_POSTCODE_LABEL'); ?>
	      </td><td>
	      <?php echo $this->billingAddress['postcode']; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_CITY_LABEL'); ?>
	      </td><td>
	      <?php echo $this->billingAddress['city']; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_REGION_LABEL'); ?>
	      </td><td>
	      <?php echo $this->billingAddress['region']; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_COUNTRY_LABEL'); ?>
	      </td><td>
	      <?php echo JText::_($this->billingAddress['country_lang_var']); ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_PHONE_LABEL'); ?>
	      </td><td>
	      <?php echo JText::_($this->billingAddress['phone']); ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_NOTE_LABEL'); ?>
	      </td><td>
	      <?php echo $this->billingAddress['note']; ?>
	      </td></tr>
	      </table>
	    </div>
	  </div>
	</div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>
      <?php endif; ?>
  </div>

  <input type="hidden" name="customer" id="user_id" value="<?php echo $this->item->user_id; ?>" />
  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token', array('id' => 'token')); ?>
</form>

<?php
$doc = JFactory::getDocument();
//Load jQuery script.
$doc->addScript(JURI::base().'components/com_ketshop/js/order.js');

