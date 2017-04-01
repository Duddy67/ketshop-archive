<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

//Prevent params layout (layouts/joomla/edit/params.php) to display twice some fieldsets.
$this->ignore_fieldsets = array('details', 'permissions', 'jmetadata');

//$this->ignore_fields = array('metadata');
//$this->ignore_fieldsets = array('jmetadata', 'details');

//Set the tax method information.
if($this->config->get('tax_method') == 'incl_tax') {
  $taxMethod = JText::_('COM_KETSHOP_SPAN_INCLUDING_TAXES');
  $taxMethodTitle = JText::_('COM_KETSHOP_SPAN_INCLUDING_TAXES_TITLE');
}
else {
  $taxMethod = JText::_('COM_KETSHOP_SPAN_EXCLUDING_TAXES');
  $taxMethodTitle = JText::_('COM_KETSHOP_SPAN_EXCLUDING_TAXES_TITLE');
}

//By default template is named after the product type.
$template = $this->form->getValue('type');


?>

<script type="text/javascript">
//Global variable. It will be set as function in product.js file.
var checkAlias;

Joomla.submitbutton = function(task)
{
  if(task == 'product.cancel' || document.formvalidator.isValid(document.getElementById('product-form'))) {
    //Check if the alias is unique before submiting form.
    if(!checkAlias()) {
      alert('<?php echo JText::_('COM_KETSHOP_DATABASE_ERROR_PRODUCT_UNIQUE_ALIAS'); ?>');
      document.getElementById('jform_alias').style.borderColor='#fa5858';
      document.getElementById('jform_alias-lbl').style.color='#fa5858';
      return;
    }

    Joomla.submitform(task, document.getElementById('product-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=product&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="product-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_KETSHOP_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span8">
	    <div class="form-vertical">
	    <?php
		echo $this->form->getControlGroup('base_price');
		echo $this->form->getControlGroup('sale_price');
		echo $this->form->getControlGroup('tax_id');
		echo $this->form->getControlGroup('code');
		$this->form->setValue('default_language', null, UtilityHelper::getLanguage());
		echo $this->form->getControlGroup('default_language');
		echo $this->form->getControlGroup('producttext');
		//Hidden field.
		echo $this->form->getInput('type');
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
	  <?php echo JLayoutHelper::render('edit.publishingdata', $this, JPATH_COMPONENT.'/layouts/'); ?>
	</div>
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php if($template == 'bundle') : //Bundle ?>
	<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'bundle-product', JText::_('COM_KETSHOP_SUBMENU_BUNDLE_PRODUCT', true)); ?>
	  <div class="row-fluid form-horizontal-desktop">
	    <div class="span8" id="bundleproduct">
	    </div>
	  </div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>
      <?php endif; ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'stock-quantities', JText::_('COM_KETSHOP_SUBMENU_STOCK_QUANTITIES', true)); ?>
	<div class="row-fluid form-horizontal-desktop">
	  <div class="span4">
	    <?php echo JLayoutHelper::render('edit.stockquantities', $this, JPATH_COMPONENT.'/layouts/'); ?>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'weight-dimensions', JText::_('COM_KETSHOP_SUBMENU_WEIGHT_AND_DIMENSIONS', true)); ?>
	<div class="row-fluid form-horizontal-desktop">
	  <div class="span4">
	    <?php echo JLayoutHelper::render('edit.weightdimensions', $this, JPATH_COMPONENT.'/layouts/'); ?>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'attributes', JText::_('COM_KETSHOP_SUBMENU_ATTRIBUTES', true)); ?>
	<div class="row-fluid form-horizontal-desktop">
	  <div class="span6" id="attribute">
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php if($this->item->id) : //Existing product  ?>
	<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'product-options', JText::_('COM_KETSHOP_SUBMENU_PRODUCT_OPTIONS', true)); ?>
	  <div class="row-fluid form-horizontal-desktop">
	    <div class="span9" id="option">
	      <?php 
		    echo $this->form->getControlGroup('attribute_group');
		    echo $this->form->getControlGroup('option_name');
	      ?>
	    </div>
	  </div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>
      <?php endif; ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'images', JText::_('COM_KETSHOP_SUBMENU_IMAGES', true)); ?>
	<div class="row-fluid form-horizontal-desktop">
	  <div class="span6" id="image">
	    <?php echo $this->form->getInput('imageurl'); //Must be loaded to call the overrided media file.
		  echo $this->form->getControlGroup('img_reduction_rate');
	    ?>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('COM_KETSHOP_TAB_PERMISSIONS', true)); ?>
	      <?php echo $this->form->getInput('rules'); ?>
	      <?php echo $this->form->getInput('asset_id'); ?>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
  </div>

  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

<?php
$doc = JFactory::getDocument();

//Load the jQuery scripts.
$doc->addScript(JURI::base().'components/com_ketshop/js/common.js');
$doc->addScript(JURI::base().'components/com_ketshop/js/product.js');

