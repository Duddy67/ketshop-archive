<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tabstate');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.modal');

$canDo = KetshopHelper::getActions();
?>

<script type="text/javascript">
//Global variables. It will be set as function in product.js file.
var checkPriceRule;

Joomla.submitbutton = function(task)
{
  if(task == 'pricerule.cancel' || document.formvalidator.isValid(document.id('pricerule-form'))) {
    if(task == 'pricerule.cancel' || (checkNumber('jform_value', true) && checkPriceRule())) {
      Joomla.submitform(task, document.getElementById('pricerule-form'));
    }
    else {
      alert('<?php echo $this->escape(JText::_('COM_KETSHOP_ERROR_NUMBER_NOT_VALID'));?>');
    }
  }
}
</script>


<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=pricerule&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="pricerule-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_KETSHOP_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span6">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('type');
		  echo $this->form->getControlGroup('behavior');
		  echo $this->form->getControlGroup('show_rule');
		  $this->form->setValue('default_language', null, UtilityHelper::getLanguage());
		  echo $this->form->getControlGroup('default_language');
		  echo $this->form->getControlGroup('description');
	      ?>
	  </div>
	</div>
	<div class="span3">
	  <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'pricerule-condition', JText::_('COM_KETSHOP_FIELDSET_CONDITION', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6" id="condition">
	  <?php
		echo $this->form->getControlGroup('condition');
		echo $this->form->getControlGroup('logical_opr');
	  ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'pricerule-action', JText::_('COM_KETSHOP_FIELDSET_ACTION', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <?php
		echo $this->form->getControlGroup('operation');
		$this->form->setValue('value', null, UtilityHelper::formatNumber($this->item->value));
		echo $this->form->getControlGroup('value');
		echo $this->form->getControlGroup('modifier');
		echo $this->form->getControlGroup('application');
	  ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'pricerule-target', JText::_('COM_KETSHOP_FIELDSET_ON_WHAT', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6" id="target">
	  <?php echo $this->form->getControlGroup('target'); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'pricerule-recipient', JText::_('COM_KETSHOP_FIELDSET_FOR_WHOM', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6" id="recipient">
	  <?php echo $this->form->getControlGroup('recipient'); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

<?php

$doc = JFactory::getDocument();
//Load the jQuery scripts.
$doc->addScript(JURI::base().'components/com_ketshop/js/common.js');
$doc->addScript(JURI::base().'components/com_ketshop/js/pricerule.js');

