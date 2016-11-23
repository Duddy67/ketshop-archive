<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.tooltip');
//JHtml::_('script','system/multiselect.js',false,true);
JHtml::_('formbehavior.chosen', 'select');


$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

//Get variable from url query.
$idNb = JFactory::getApplication()->input->get->get('id_nb', 0, 'int');

//Note: This modal window is called from both product and translation edit views.
//Translation doesn't provide idNb GET variable and just needs id and name
//attribute. So we use 2 functions to return attribute data accordingly.

//Set the Javascript function to call.  
if(!$idNb) {
  $function = JFactory::getApplication()->input->get('function', 'selectItem');
}
else {
  $function = JFactory::getApplication()->input->get('function', 'jQuery.selectAttribute');
}
?>


<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=attributes&layout=modal&tmpl=component&function='.$function.'&id_nb='.$idNb);?>" method="post" name="adminForm" id="adminForm">

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
  </fieldset>

  <?php if (empty($this->items)) : ?>
	<div class="alert alert-no-items">
		<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
  <?php else : ?>
    <table class="table table-striped table-condensed">
      <thead>
	<tr>
	  <th class="title">
		  <?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.name', $listDirn, $listOrder); ?>
	  </th>
	  <th width="15%">
		  <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_CREATED_BY', 'author', $listDirn, $listOrder); ?>
	  </th>
	  <th width="15%" class="center nowrap">
		  <?php echo JHtml::_('grid.sort', 'JDATE', 'a.created', $listDirn, $listOrder); ?>
	  </th>
	  <th width="1%" class="center nowrap">
		  <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
	  </th>
	</tr>
      </thead>
      <tfoot>
	<tr>
	  <td colspan="5">
	    <?php echo $this->pagination->getListFooter(); ?>
	  </td>
	</tr>
      </tfoot>

      <tbody>
      <?php foreach ($this->items as $i => $item) : ?>
      <tr class="row<?php echo $i % 2; ?>">
	      <td>
	      <?php if(!$idNb) : //Provide only id and name of the product. ?>
		<a class="pointer" style="color:#025a8d;" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>(<?php echo $item->id; ?>, '<?php echo $this->escape(addslashes($item->name)); ?>');" >
		    <?php echo $this->escape($item->name); ?></a>
	      <?php else : //Invoke selectAttribute function. ?>
      <a class="pointer" style="color:#025a8d;" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>(<?php echo $item->id; ?>, '<?php echo $this->escape(addslashes($item->name)); ?>',<?php echo $idNb; ?>);" >
		    <?php echo $this->escape($item->name); ?></a>
	      <?php endif; ?>
	      </td>
	      <td>
		<?php echo $this->escape($item->user); ?>
	      </td>
	      <td>
		<?php echo JHTML::_('date',$item->created, JText::_('COM_KETSHOP_DATE_FORMAT')); ?>
	      </td>
	      <td class="center">
		<?php echo (int) $item->id; ?>
	      </td></tr>

      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
<?php echo JHtml::_('form.token'); ?>
</form>
