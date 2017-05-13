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

$canDo = KetshopHelper::getActions();
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'translation.cancel' ||
     (document.formvalidator.isValid(document.id('translation-form')) && document.getElementById('jform_item_id_id').value != 0)) {
    Joomla.submitform(task, document.getElementById('translation-form'));
  }
  else {
    if(document.getElementById('jform_item_id_id').value == 0) {
      document.getElementById('jform_item_id_name').style.borderColor='red';
    }

    alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=translation&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="translation-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_KETSHOP_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span6">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('item_type');
		  echo $this->form->getControlGroup('item_id');
		  echo $this->form->getControlGroup('product_description');
		  echo $this->form->getControlGroup('description');
		  echo $this->form->getControlGroup('information');
	      ?>
	  </div>
	</div>
	<div class="span3">
	  <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
      <div class="row-fluid form-vertical">
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'metadata-title', JText::_('COM_KETSHOP_TAB_METADATA', true)); ?>
      <div class="row-fluid  form-vertical">
	<div class="span6" id="metadata">
	  <?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
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
$doc->addScript(JURI::base().'components/com_ketshop/js/translation.js');

