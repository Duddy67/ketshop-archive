<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

//Get all the session variables needed for building the order.
$session = JFactory::getSession();
$settings = $session->get('settings', array(), 'ketshop'); 
$shippable = ShopHelper::isShippable();

//Get the current url.
$currentUrl = JURI::getInstance();

//Set, or create if it doesn't exist, the current query url needed
//to redirect the customer after a task is done.
$session->set('location', $currentUrl->getQuery(), 'ketshop');
?>

<div class="blog">
  <h1><?php echo JText::_('COM_KETSHOP_CHECK_ADDRESS_TITLE'); ?></h1>

<?php if($shippable): ?>
  <p class="main-information">
      <?php echo JText::_('COM_KETSHOP_SHIPPING_ADDRESS_INFORMATION'); ?>
  </p>
<?php else : ?>
  <p class="main-information">
      <?php echo JText::_('COM_KETSHOP_BILLING_ADDRESS_INFORMATION_2'); ?>
  </p>
<?php endif;?>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&task=address.updateAddress');?>"
	method="post" id="ketshop_address" class="form-validate form-horizontal well">

<?php foreach ($this->form->getFieldsets() as $fieldset): // Iterate through the form fieldsets and display each one.?>
  <?php $fields = $this->form->getFieldset($fieldset->name);?>
  <?php if(count($fields)):?>
    <fieldset>
    <?php if(isset($fieldset->label)):// If the fieldset has a label set, display it as the legend. ?>
	    <legend><?php echo JText::_($fieldset->label);?></legend>
    <?php endif;?>
    <?php foreach($fields as $field):// Iterate through the fields in the set and display them.?>
      <?php if ($field->hidden):// If the field is hidden, just display the input.?>

	<?php if($field->name === 'ketshopprofile[billing_address_information]') : //Display billing address information. ?>
	  <?php echo JText::_('COM_KETSHOP_BILLING_ADDRESS_INFORMATION_1'); ?>
	<?php endif;?>

	<?php echo $field->input;?>
      <?php else:?>
	<div class="control-group">
	  <div class="control-label">
	    <?php echo $field->label; ?>
	    <?php if(!$field->required && $field->type!='Spacer'): ?>
	      <span class="optional"><?php
	      echo JText::_('COM_KETSHOP_ADDRESS_OPTIONAL'); ?></span>
	    <?php endif; ?>
	  </div>
	  <div class="controls">
	     <?php if($field->name == 'ketshopprofile[region_code_sh]') : //Required for the dynamical Javascript setting. ?>
		<input type="hidden" name="hidden_region_code_sh" id="hidden-region-code-sh" value="<?php echo $field->value; ?>" />
	     <?php endif; ?> 

	     <?php if($field->name == 'ketshopprofile[region_code_bi]') : //Idem. ?>
		<input type="hidden" name="hidden_region_code_bi" id="hidden-region-code-bi" value="<?php echo $field->value; ?>" />
	     <?php endif; ?> 

	    <?php echo ($field->type!='Spacer') ? $field->input : "&#160;"; ?>
	  </div>
	</div>
      <?php endif;?>
    <?php endforeach;?>
    </fieldset>
  <?php endif;?>
<?php endforeach;?>

  <div class="control-group">
    <div class="controls">
      <input class="btn btn-success" id="submit-button" type="submit" onclick="return checkForm(this.form);"
	      value="<?php echo JText::_('COM_KETSHOP_VALIDATE'); ?>" />
      <a class="btn" href="<?php echo JRoute::_('');?>" title="<?php echo JText::_('JCANCEL');?>"><?php echo JText::_('JCANCEL');?></a>
    </div>
  </div>

  <input type="hidden" name="form_type" id="form-type" value="ketshopprofile" />
  <?php echo JHtml::_('form.token');?>
    
  </form>
</div>

<?php 

$doc = JFactory::getDocument();
$doc->addScript(JURI::root().'components/com_ketshop/js/setregions.js');

