<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.modal');
JHtml::_('behavior.tabstate');
JHtml::_('behavior.calendar');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

// Create shortcut to parameters.
$params = $this->state->get('params');
?>

<script type="text/javascript">
//Global variable. It will be set as function in product.js file.
var checkAlias;

Joomla.submitbutton = function(task)
{
  if(task == 'product.cancel' || document.formvalidator.isValid(document.id('product-form'))) {
    Joomla.submitform(task, document.getElementById('product-form'));
  }
  else {
    alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
  }
}
</script>

<div class="edit-product <?php echo $this->pageclass_sfx; ?>">
  <?php if($params->get('show_page_heading')) : ?>
    <div class="page-header">
      <h1>
	<?php echo $this->escape($params->get('page_heading')); ?>
      </h1>
    </div>
  <?php endif; ?>

  <form action="<?php echo JRoute::_('index.php?option=com_ketshop&p_id='.(int)$this->item->id); ?>" 
   method="post" name="adminForm" id="product-form" enctype="multipart/form-data" class="form-validate form-vertical">

      <div class="btn-toolbar">
	<div class="btn-group">
	  <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('product.save')">
		  <span class="icon-ok"></span>&#160;<?php echo JText::_('JSAVE') ?>
	  </button>
	</div>
	<div class="btn-group">
	  <button type="button" class="btn" onclick="Joomla.submitbutton('product.cancel')">
		  <span class="icon-cancel"></span>&#160;<?php echo JText::_('JCANCEL') ?>
	  </button>
	</div>
	<?php if ($params->get('save_history', 0)) : ?>
	<div class="btn-group">
		<?php echo $this->form->getInput('contenthistory'); ?>
	</div>
	<?php endif; ?>
      </div>

      <fieldset>

	<ul class="nav nav-tabs">
		<li class="active"><a href="#details" data-toggle="tab"><?php echo JText::_('COM_KETSHOP_TAB_DETAILS') ?></a></li>
		<li><a href="#stock-quantities" data-toggle="tab"><?php echo JText::_('COM_KETSHOP_TAB_STOCK_QUANTITIES') ?></a></li>
		<li><a href="#weight-dimensions" data-toggle="tab"><?php echo JText::_('COM_KETSHOP_TAB_WEIGHT_DIMENSIONS') ?></a></li>
		<li><a href="#attributes" data-toggle="tab"><?php echo JText::_('COM_KETSHOP_TAB_ATTRIBUTES') ?></a></li>
		<li><a href="#images" data-toggle="tab"><?php echo JText::_('COM_KETSHOP_TAB_IMAGES') ?></a></li>
		<?php if($this->item->id) : //Existing product  ?>
		  <li><a href="#options" data-toggle="tab"><?php echo JText::_('COM_KETSHOP_TAB_OPTIONS') ?></a></li>
		<?php endif; ?>
		<li><a href="#publishing" data-toggle="tab"><?php echo JText::_('COM_KETSHOP_TAB_PUBLISHING') ?></a></li>
		<li><a href="#language" data-toggle="tab"><?php echo JText::_('JFIELD_LANGUAGE_LABEL') ?></a></li>
		<li><a href="#metadata" data-toggle="tab"><?php echo JText::_('COM_KETSHOP_TAB_METADATA') ?></a></li>
	</ul>

	<div class="tab-content">
	    <div class="tab-pane active" id="details">
	      <?php echo $this->form->renderField('name'); ?>
	      <?php echo $this->form->renderField('alias'); ?>

	      <?php if($this->form->getValue('id') != 0) : //Existing item. ?>

	      <?php endif; ?>

	      <?php
		echo $this->form->getControlGroup('base_price');
		echo $this->form->getControlGroup('sale_price');
		echo $this->form->getControlGroup('tax_id');
		echo $this->form->getControlGroup('code');
		echo $this->form->getControlGroup('producttext');
		//Hidden field.
		echo $this->form->getInput('type');
	      ?>
	      </div>

	      <div class="tab-pane" id="stock-quantities">
		<?php echo JLayoutHelper::render('edit.stockquantities', $this, JPATH_ROOT.'/administrator/components/com_ketshop/layouts/'); ?>
	      </div>

	      <div class="tab-pane" id="weight-dimensions">
		<?php echo JLayoutHelper::render('edit.weightdimensions', $this, JPATH_ROOT.'/administrator/components/com_ketshop/layouts/'); ?>
	      </div>

	      <div class="tab-pane" id="attributes">
		<div class="span10" id="attribute">
		</div>
	      </div>

	      <div class="tab-pane" id="images">
		<div class="span10" id="image">
		<?php echo $this->form->getInput('imageurl'); //Must be loaded to call the overrided media file.
		      echo $this->form->getControlGroup('img_reduction_rate');
		?>
		</div>
	      </div>

	      <?php if($this->item->id) : //Existing product  ?>
		  <div class="tab-pane" id="options">
		    <div class="span12" id="option">
		      <?php 
			    echo $this->form->getControlGroup('attribute_group');
			    echo $this->form->getControlGroup('option_name');
		      ?>
		    </div>
		  </div>
	      <?php endif; ?>

	      <div class="tab-pane" id="publishing">
		<?php echo $this->form->getControlGroup('catid'); ?>
		<?php echo $this->form->getControlGroup('tags'); ?>
		<?php echo $this->form->getControlGroup('access'); ?>
		<?php echo $this->form->getControlGroup('id'); ?>

		<?php if($this->item->params->get('access-change')) : ?>
		  <?php echo $this->form->getControlGroup('published'); ?>
		  <?php echo $this->form->getControlGroup('publish_up'); ?>
		  <?php echo $this->form->getControlGroup('publish_down'); ?>
		<?php endif; ?>
	      </div>

	      <div class="tab-pane" id="language">
		<?php echo $this->form->getControlGroup('language'); ?>
	      </div>

	      <div class="tab-pane" id="metadata">
		<?php echo $this->form->getControlGroup('metadesc'); ?>
		<?php echo $this->form->getControlGroup('metakey'); ?>
	      </div>
	    </div>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="return" value="<?php echo $this->return_page; ?>" />
    <?php if($this->params->get('enable_category', 0) == 1) :?>
      <input type="hidden" name="jform[catid]" value="<?php echo $this->params->get('catid', 1); ?>" />
    <?php endif; ?>
    <input type="hidden" id="base-url" name="base_url" value="<?php echo JURI::root(); ?>" />
    <input type="hidden" id="is-admin" name="is_admin" value="0" />
    <?php if(!$this->item->id) : //New item. Get the type of the product from the current url query.  ?>
      <input type="hidden" id="product-type" name="product_type" value="<?php echo JFactory::getApplication()->input->get('type', '', 'string'); ?>" />
    <?php endif; ?>
    <?php echo JHtml::_('form.token'); ?>
    </fieldset>
  </form>
</div>

<?php
$doc = JFactory::getDocument();

//Load the jQuery scripts.
$doc->addScript(JURI::root().'administrator/components/com_ketshop/js/common.js');
$doc->addScript(JURI::root().'administrator/components/com_ketshop/js/product.js');

