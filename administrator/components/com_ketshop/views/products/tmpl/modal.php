<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.framework', true);
JHtml::_('formbehavior.chosen', 'select');

JLoader::register('KetshopHelperRoute', JPATH_ROOT.'/components/com_ketshop/helpers/route.php');

$jinput = JFactory::getApplication()->input;

$idNb = $jinput->get->get('id_nb', 0, 'int');
//The type of the calling item.
$type = $jinput->get->get('type', '', 'string');
$productType = $jinput->get->get('product_type', '', 'string');

//Set the Javascript function to call.  
if($type == 'translation') {
  $function = $jinput->get('function', 'selectItem');
}
elseif($type == 'order') {
  $function = $jinput->get('function', 'jQuery.selectProduct');
}
else {
  $function = $jinput->get('function', 'jQuery.selectItem');
  //Build the needed GET variable. 
  if(!empty($productType)) {
    $productType = '&product_type='.$productType;
  }
}

if(!empty($type)) {
  $typeVariable = '&type='.$type;
}

$currency = UtilityHelper::getCurrency();

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=products&layout=modal&tmpl=component&function='.$function.'&'.JSession::getFormToken().'=1'.$typeVariable);?>" method="post" name="adminForm" id="adminForm" class="form-inline">

  <fieldset class="filter clearfix">
    <div class="btn-toolbar">
      <div class="btn-group pull-left">
	      <label for="filter_search">
		      <?php echo JText::_('JSEARCH_FILTER_LABEL'); ?>
	      </label>
      </div>
      <div class="btn-group pull-left">
	      <input type="text" name="filter_search" id="filter_search" value="<?php echo
	      $this->escape($this->state->get('filter.search')); ?>" size="30"
	      title="<?php echo JText::_('COM_KETSHOP_FILTER_SEARCH_DESC'); ?>" />
      </div>
      <div class="btn-group pull-left">
	      <button type="submit" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>" data-placement="bottom">
		      <span class="icon-search"></span><?php echo '&#160;' . JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
	      <button type="button" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" data-placement="bottom" onclick="document.id('filter_search').value='';this.form.submit();">
		      <span class="icon-remove"></span><?php echo '&#160;' . JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
      </div>
	<div class="clearfix"></div>
    </div>
    <hr class="hr-condensed" />
    <div class="filters pull-left">
      <select name="filter_access" class="input-medium" onchange="this.form.submit()">
	<option value=""><?php echo JText::_('JOPTION_SELECT_ACCESS');?></option>
	<?php echo JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'));?>
      </select>

      <select name="filter_published" class="input-medium" onchange="this.form.submit()">
	<option value=""><?php echo JText::_('JOPTION_SELECT_PUBLISHED');?></option>
	<?php echo JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true);?>
      </select>

      <select name="filter_category_id" class="input-medium" onchange="this.form.submit()">
	<option value=""><?php echo JText::_('JOPTION_SELECT_CATEGORY');?></option>
	<?php echo JHtml::_('select.options', JHtml::_('category.options', 'com_ketshop'), 'value', 'text', $this->state->get('filter.category_id'));?>
      </select>
      <select name="filter_tag" class="input-medium" onchange="this.form.submit()">
	<option value=""><?php echo JText::_('JOPTION_SELECT_TAG');?></option>
	<?php echo JHtml::_('select.options', JHtml::_('tag.options', 'com_ketshop'), 'value', 'text', $this->state->get('filter.tag'));?>
      </select>
    </div>
  </fieldset>

  <table class="table table-striped table-condensed">
    <thead>
      <tr>
	<th class="title">
		<?php echo JHtml::_('grid.sort', 'COM_KETSHOP_FIELD_NAME_LABEL', 'p.name', $listDirn, $listOrder); ?>
	</th>
	<th width="10%">
	  <?php echo JText::_('COM_KETSHOP_HEADING_BASE_PRICE'); ?>
	</th>
	<th width="10%">
	  <?php echo JText::_('COM_KETSHOP_HEADING_SALE_PRICE'); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('grid.sort', 'COM_KETSHOP_HEADING_STOCK', 'p.stock', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('grid.sort', 'JDATE', 'p.created', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'p.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
    </thead>
    <tfoot>
      <tr>
	<td colspan="7">
	  <?php echo $this->pagination->getListFooter(); ?>
	</td>
      </tr>
    </tfoot>
    <tbody>
    <?php foreach($this->items as $i => $item) : ?>
      <tr class="row<?php echo $i % 2; ?>">
	      <td class="has-context">
		<div class="pull-left">

	    <?php if($type == 'translation' || $type == 'order') : //Provide only product id and product name. ?>
	      <a href="javascript:void(0)" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->name)); ?>');">

	    <?php elseif($type == 'bundleproduct') : //A bundle is created, (we need stock quantity). ?>
	      <a href="javascript:void(0)" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>('<?php echo
		  $item->id; ?>', '<?php echo $this->escape(addslashes($item->name)); ?>', '<?php echo $this->escape($idNb); ?>', '<?php echo $this->escape($type); ?>','<?php echo $item->stock; ?>');">
	    <?php else : //Price rule ?>
	      <a href="javascript:void(0)" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->name)); ?>', '<?php echo $this->escape($idNb); ?>', '<?php echo $this->escape($type); ?>');">
	    <?php endif; ?>
		  <?php echo $this->escape($item->name); ?>
	    <?php if($type == 'order' && !empty($item->variant_name)) : //. ?>
	      <span class="small">&nbsp;<?php echo $this->escape($item->variant_name); ?></span>
	    <?php endif; ?></a>

	    <?php if($type == 'order' && !empty($item->variants)) : //. ?>
	      <div class="small">
	      <table>
		<thead><tr>
		  <th><?php echo JText::_('COM_KETSHOP_HEADING_OPTIONS'); ?></th>
		  <th><?php echo JText::_('COM_KETSHOP_HEADING_STOCK'); ?></th>
		  <th><?php echo JText::_('COM_KETSHOP_HEADING_BASE_PRICE'); ?></th>
		  <th><?php echo JText::_('COM_KETSHOP_HEADING_SALE_PRICE'); ?></th>
		</tr></thead>
	      
	      <?php foreach($item->variants as $variant) :  
		      $prodIds = $variant['prod_id'].'_'.$variant['var_id']; ?>
		      <tr><td>
			<a href="javascript:void(0)" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>('<?php echo $prodIds; ?>', '<?php echo $this->escape(addslashes($item->name)); ?>');"><?php echo $this->escape($variant['variant_name']); ?></a></td>
			<td><?php echo $variant['stock']; ?></td>
			<?php $basePrice = $salePrice = '-';
			      if($variant['base_price'] > 0) { 
				$basePrice = UtilityHelper::formatNumber($variant['base_price']).' '.$currency;
			      }
			
			      if($variant['sale_price'] > 0) { 
				$salePrice = UtilityHelper::formatNumber($variant['sale_price']).' '.$currency;
			      }
			?>
			<td><?php echo $basePrice; ?></td><td><?php echo $salePrice; ?></td></tr>
	      <?php endforeach; ?> 
	      </table>
	      </div>
	    <?php endif; ?>

		  <span class="small break-word">
		    <?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
		  </span>
		  <div class="small">
		    <?php echo JText::_('JCATEGORY') . ": ".$this->escape($item->category_title); ?>
		  </div>
		</div>
	      </td>
	      <td>
		<?php echo UtilityHelper::formatNumber($item->base_price).' '.$currency; ?>
	      </td>
	      <td>
		<?php echo UtilityHelper::formatNumber($item->sale_price).' '.$currency; ?>
	      </td>
	      <td class="hidden-phone">
		<?php echo $item->stock; ?>
	      </td>
	      <td  class="small hidden-phone">
		<?php echo $this->escape($item->access_level); ?>
	      </td>
	      <td  class="small hidden-phone">
		<?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
	      </td>
	      <td class="center">
		<?php echo (int) $item->id; ?>
	      </td>
      </tr>
    <?php endforeach; ?>
      </tbody>
    </table>

  <div>
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
    <?php echo JHtml::_('form.token'); ?>
  </div>
</form>

