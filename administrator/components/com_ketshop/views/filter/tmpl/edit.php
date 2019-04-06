<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.modal');
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'filter.cancel' || document.formvalidator.isValid(document.getElementById('filter-form'))) {
    Joomla.submitform(task, document.getElementById('filter-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=filter&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="filter-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_KETSHOP_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span4">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('main_tag_id');
		  echo $this->form->getControlGroup('description');
	      ?>
	  </div>
	</div>
	<div class="span3">
	  <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'attributes', JText::_('COM_KETSHOP_SUBMENU_ATTRIBUTES', true)); ?>
	<div class="row-fluid form-horizontal-desktop">
	  <div class="span12" id="attribute">
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

  </div>

  <input type="hidden" name="task" value="" />
  <input type="hidden" name="root_location" id="root-location" value="<?php echo JUri::root(); ?>" />
  <?php echo JHtml::_('form.token', array('id' => 'token')); ?>
</form>

<?php
$doc = JFactory::getDocument();

// Loads the required scripts.
$doc->addScript(JURI::base().'components/com_ketshop/js/omkod-ajax.js');
$doc->addScript(JURI::base().'components/com_ketshop/js/omkod-dynamic-item.js');
$doc->addScript(JURI::base().'components/com_ketshop/js/filter.js');

