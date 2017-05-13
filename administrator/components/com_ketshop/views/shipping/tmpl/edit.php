<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.tabstate');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

$canDo = KetshopHelper::getActions();

$weightUnit = $this->config->get('shipping_weight_unit');
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'shipping.cancel') {
    Joomla.submitform(task, document.getElementById('shipping-form'));
  }
  else if(document.formvalidator.isValid(document.id('shipping-form'))) {
    if(!checkMinMax('jform_min_weight','jform_max_weight', 'Float', true)) {
      alert('<?php echo $this->escape(JText::_('COM_KETSHOP_ERROR_MIN_MAX_VALUES'));?>');
    }
    else if(!checkMinMax('jform_min_product', 'jform_max_product', 'Int', false)) {
      alert('<?php echo $this->escape(JText::_('COM_KETSHOP_ERROR_MIN_MAX_VALUES_ZERO_NOT_ALLOWED'));?>');
    }
    else {
      Joomla.submitform(task, document.getElementById('shipping-form'));
    }
  }
  else {
    alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=shipping&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="shipping-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_KETSHOP_TAB_DETAILS')); ?>
      <div class="row-fluid">
	<div class="span6">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('delivery_type');
		  echo $this->form->getControlGroup('delivpnt_cost');
		  echo $this->form->getControlGroup('min_weight');
		  echo $this->form->getControlGroup('max_weight');
		  echo $this->form->getControlGroup('min_product');
		  echo $this->form->getControlGroup('max_product');
		  echo $this->form->getControlGroup('min_delivery_delay');
		  echo $this->form->getControlGroup('description');
	      ?>
	  </div>
	</div>
	<div class="span4">
	  <div class="form-vertical">
	  <?php 
		echo $this->form->getControlGroup('published');
		$this->form->setValue('default_language', null, UtilityHelper::getLanguage());
		echo $this->form->getControlGroup('default_language');
	    ?>
	  </div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <div class="form-vertical">
	  <?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
	  </div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'address-title', JText::_('COM_KETSHOP_FIELDSET_ADDRESS')); ?>
	<div class="row-fluid form-vertical">
	  <div class="span6" id="address">
	    <?php
		  echo $this->form->getControlGroup('street');
		  echo $this->form->getControlGroup('city');
		  echo $this->form->getControlGroup('postcode');
		  echo $this->form->getControlGroup('region_code');
		  echo $this->form->getControlGroup('country_code');
		  echo $this->form->getControlGroup('phone');
	      ?>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'postcode-title', JText::_('COM_KETSHOP_FIELDSET_POSTCODES', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6" id="postcode">
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'city-title', JText::_('COM_KETSHOP_FIELDSET_CITIES', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6" id="city">
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'region-title', JText::_('COM_KETSHOP_FIELDSET_REGIONS', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6" id="region">
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'country-title', JText::_('COM_KETSHOP_FIELDSET_COUNTRIES', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6" id="country">
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'continent-title', JText::_('COM_KETSHOP_FIELDSET_CONTINENTS', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6" id="continent">
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'globalcost-title', JText::_('COM_KETSHOP_FIELDSET_GLOBAL')); ?>
	<div class="row-fluid form-vertical">
	  <div class="span6" id="globalcost">
	    <?php
		  echo $this->form->getControlGroup('global_cost');
	      ?>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
  </div>

  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

<?php
$doc = JFactory::getDocument();
//Load the jQuery scripts.
$doc->addScript(JURI::base().'components/com_ketshop/js/common.js');
$doc->addScript(JURI::base().'components/com_ketshop/js/shipping.js');

