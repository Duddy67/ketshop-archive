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
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'attribute.cancel' || document.formvalidator.isValid(document.id('attribute-form'))) {
    //The attribute is used by a product.
    if(task != 'attribute.cancel' && document.getElementById('used-as-attribute').value) {
      if(!confirm('<?php echo $this->escape(JText::_('COM_KETSHOP_WARNING_USED_AS_ATTRIBUTE'));?>')) {
	return false;
      }
    }
    //The attribute is used as a product option.
    if(task != 'attribute.cancel' && document.getElementById('used-as-option').value) {
      if(!confirm('<?php echo $this->escape(JText::_('COM_KETSHOP_WARNING_USED_AS_OPTION'));?>')) {
	return false;
      }
    }

    Joomla.submitform(task, document.getElementById('attribute-form'));
  }
}
</script>


<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=attribute&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="attribute-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_KETSHOP_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span4">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('field_type_1');
		  echo $this->form->getControlGroup('field_value_1');
		  echo $this->form->getControlGroup('field_text_1');
		  echo $this->form->getControlGroup('multiselect');
		  echo $this->form->getControlGroup('value_type');
		  echo $this->form->getControlGroup('field_type_2');
		  echo $this->form->getControlGroup('field_value_2');
		  echo $this->form->getControlGroup('field_text_2');
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
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'attribute-groups', JText::_('COM_KETSHOP_FIELDSET_GROUPS', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6" id="group">
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

  </div>

  <input type="hidden" name="used_as_attribute" id="used-as-attribute" value="<?php echo $this->usedAsAttribute; ?>" />
  <input type="hidden" name="used_as_option" id="used-as-option" value="<?php echo $this->usedAsOption; ?>" />
  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

<?php

$doc = JFactory::getDocument();
//Load the jQuery scripts.
$doc->addScript(JURI::base().'components/com_ketshop/js/common.js');
$doc->addScript(JURI::base().'components/com_ketshop/js/attribute.js');

