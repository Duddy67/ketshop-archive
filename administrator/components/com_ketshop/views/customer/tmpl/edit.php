<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tabstate');
JHtml::_('formbehavior.chosen', 'select');

//Build a status array.
$status = array();
$status['completed'] = 'COM_KETSHOP_OPTION_COMPLETED_STATUS';
$status['pending'] = 'COM_KETSHOP_OPTION_PENDING_STATUS';
$status['other'] = 'COM_KETSHOP_OPTION_OTHER_STATUS';
$status['cancelled'] = 'COM_KETSHOP_OPTION_CANCELLED_STATUS';
$status['error'] = 'COM_KETSHOP_OPTION_ERROR_STATUS';
$status['no_shipping'] = 'COM_KETSHOP_OPTION_NO_SHIPPING_STATUS';
$status['unfinished'] = 'COM_KETSHOP_OPTION_UNFINISHED_STATUS';
$status['undefined'] = 'COM_KETSHOP_OPTION_UNDEFINED_STATUS';
$status['cartbackup'] = 'COM_KETSHOP_OPTION_CART_BACKUP_STATUS';

$limitItem = JFactory::getApplication()->input->post->get('limit_item', null, 'int');
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'customer.cancel' || document.formvalidator.isValid(document.id('customer-form'))) {
    Joomla.submitform(task, document.getElementById('customer-form'));
  }
  else {
    alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
  }
}


function setLimitItem(this_)
{
  var limitItem = document.getElementsByName('limit_item')[0];
  limitItem.value = this_.value; 

  this_.form.submit();
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=customer&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="customer-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_KETSHOP_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span4">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('username');
		  echo $this->form->getControlGroup('email');
		  echo $this->form->getControlGroup('registerDate');
		  echo $this->form->getControlGroup('lastvisitDate');
		  echo $this->form->getControlGroup('user_id');
	      ?>
	  </div>
	</div>
	<div class="span3">
	  <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


  <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'shipping', JText::_('COM_USER_KETSHOP_CUSTOMER_SLIDER_SHIPPING_LABEL', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('street_sh');
		  echo $this->form->getControlGroup('city_sh');
		  echo $this->form->getControlGroup('postcode_sh');
		  echo $this->form->getControlGroup('region_code_sh');
		  echo $this->form->getControlGroup('country_code_sh');
		  echo $this->form->getControlGroup('phone_sh');
		  echo $this->form->getControlGroup('note_sh');
	      ?>
	  </div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

  <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'billing', JText::_('COM_USER_KETSHOP_CUSTOMER_SLIDER_BILLING_LABEL', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('street_bi');
		  echo $this->form->getControlGroup('city_bi');
		  echo $this->form->getControlGroup('postcode_bi');
		  echo $this->form->getControlGroup('region_code_bi');
		  echo $this->form->getControlGroup('country_code_bi');
		  echo $this->form->getControlGroup('phone_bi');
		  echo $this->form->getControlGroup('note_bi');
	      ?>
	  </div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

  <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'orders', JText::_('COM_KETSHOP_FIELDSET_ORDERS', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span8">
	  <div class="form-vertical">

	    <?php if(!is_null($limitItem)) : ?>
	      <?php $this->form->setValue('limit_item', null, $limitItem); ?>
	    <?php endif; ?>
	    <?php echo $this->form->getInput('limit_item'); ?>

	    <table class="table table-striped">
	    <thead>
	    <th width="20%"><?php echo JText::_('COM_KETSHOP_HEADING_ORDER_NUMBER'); ?></th>
	    <th width="10%"><?php echo JText::_('COM_KETSHOP_HEADING_CART'); ?></th>
	    <th width="10%"><?php echo JText::_('COM_KETSHOP_HEADING_ORDER'); ?></th>
	    <th width="10%"><?php echo JText::_('COM_KETSHOP_HEADING_PAYMENT'); ?></th>
	    <th width="10%"><?php echo JText::_('COM_KETSHOP_HEADING_SHIPPING'); ?></th>
	    <th width="15%"><?php echo JText::_('COM_KETSHOP_HEADING_TOTAL'); ?></th>
	    <th width="15%"><?php echo JText::_('JDATE'); ?></th>
	    </thead>

	    <?php foreach($this->orders as $key => $order) : ?>

	      <tr><td><?php echo $order->name; ?></td>
	      <td><?php echo JText::_($status[$order->cart_status]); ?></td>
	      <td><?php echo JText::_($status[$order->order_status]); ?></td>
	      <td><?php echo JText::_($status[$order->payment_status]); ?></td>
	      <td><?php echo JText::_($status[$order->shipping_status]); ?></td>
	      <td><?php echo UtilityHelper::formatNumber($order->total).' '.UtilityHelper::getCurrency($order->currency_code); ?></td>
	      <td><?php echo JHTML::_('date',$order->created, JText::_('COM_KETSHOP_DATE_FORMAT')); ?></td></tr>
	    <?php endforeach; ?>
	    </table>

	  </div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
  </div>

  <?php //Required for the dynamical Javascript setting. ?>
  <input type="hidden" name="form_type" id="form-type" value="jform" />
  <input type="hidden" name="hidden_region_code_sh" id="hidden-region-code-sh" value="<?php echo $this->form->getValue('region_code_sh'); ?>" />
  <input type="hidden" name="hidden_region_code_bi" id="hidden-region-code-bi" value="<?php echo $this->form->getValue('region_code_bi'); ?>" />

  <input type="hidden" name="task" value="" />
  <input type="hidden" name="limit_item" value="<?php echo $limitItem; ?>" />
  <?php echo JHtml::_('form.token'); ?>
</form>

<?php
$doc = JFactory::getDocument();
//Load the jQuery scripts.
$doc->addScript(JURI::root().'components/com_ketshop/js/setregions.js');

