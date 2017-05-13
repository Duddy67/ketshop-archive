<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.tooltip');
JHtml::_('script','system/multiselect.js',false,true);


$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

//Set the Javascript function to call.  
$function = JFactory::getApplication()->input->get('function', 'selectItem');
?>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=shippers&layout=modal&tmpl=component&function='.$function);?>" method="post" name="adminForm" id="adminForm">

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
		  <?php echo JHtml::_('grid.sort', 'COM_KETSHOP_HEADING_NAME', 's.name', $listDirn, $listOrder); ?>
	  </th>
	  <th width="15%">
		  <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_CREATED_BY', 'user', $listDirn, $listOrder); ?>
	  </th>
	  <th width="15%" class="center nowrap">
		  <?php echo JHtml::_('grid.sort', 'JDATE', 's.created', $listDirn, $listOrder); ?>
	  </th>
	  <th width="1%" class="center nowrap">
		  <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 's.id', $listDirn, $listOrder); ?>
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
		<a class="pointer" style="color:#025a8d;" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>(<?php echo $item->id; ?>, '<?php echo $this->escape(addslashes($item->name)); ?>');" >
		    <?php echo $this->escape($item->name); ?></a>
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

    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
    <?php echo JHtml::_('form.token'); ?>
  </form>

  <form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=shippers&layout=modal&tmpl=component&function='.$function);?>" method="post" name="adminForm" id="adminForm">

    <fieldset id="filter-bar">
      <div class="filter-search fltlft">
	<label class="filter-search-lbl" for="filter_search"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?></label>
	<input type="text" name="filter_search" id="filter_search" value="<?php
	echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_KETSHOP_SEARCH_IN_TITLE'); ?>" />
	<button type="submit"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
	<button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
      </div>

    <select name="filter_user_id" class="inputbox" onchange="this.form.submit()">
	    <option value=""><?php echo JText::_('COM_KETSHOP_OPTION_SELECT_USER');?></option>
	    <?php echo JHtml::_('select.options', $this->users, 'value', 'text', $this->state->get('filter.user_id'));?>
    </select>

    <select name="filter_published" class="inputbox" onchange="this.form.submit()">
      <option value=""><?php echo JText::_('JOPTION_SELECT_PUBLISHED');?></option>
      <?php echo JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true);?>
    </select>

    </div>
    </fieldset>

    <div class="clr"> </div>
    <table class="adminlist">
      <thead>
      <tr>
	<th>
	<?php echo JHtml::_('grid.sort', 'COM_KETSHOP_HEADING_NAME', 'd.name', $listDirn, $listOrder); ?>
	</th>
	<th width="15%">
	<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_CREATED_BY', 'user', $listDirn, $listOrder); ?>
	</th>
	<th width="5%">
	<?php echo JHtml::_('grid.sort', 'JDATE', 'd.created', $listDirn, $listOrder); ?>
	</th>
	<th width="1%">
	<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'd.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :

      $ordering = ($listOrder == 'd.ordering');
      $canCheckin = $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
      //Check only against component permission as deliverypoint items have no categories.
      $canChange = ($user->authorise('core.edit.state', 'com_ketshop') && $canCheckin);
      $canEdit = $user->authorise('core.edit', 'com_ketshop');
      $canEditOwn = $user->authorise('core.edit.own', 'com_ketshop') && $item->created_by == $userId;

      ?>
      <tr class="row<?php echo $i % 2; ?>">
	    <td>
	    <?php if ($item->checked_out) : ?>
	      <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'shippers.', $canCheckin); ?>
	    <?php endif; ?>

	    <?php if($canEdit || $canEditOwn) : ?>
		<a class="pointer" style="color:#025a8d;" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>(<?php echo $item->id; ?>,
		   '<?php echo $this->escape(addslashes($item->name)); ?>');" >
		    <?php echo $this->escape($item->name); ?></a>
	    <?php else : ?>
	      <?php echo $this->escape($item->name); ?>
	    <?php endif; ?>
	      </td>
	      <td>
		<?php echo $this->escape($item->user); ?>
	      </td>
	      <td>
		<?php echo JHTML::_('date',$item->created, JText::_('d-m-Y')); ?>
	      </td>
	      <td class="center">
		<?php echo (int) $item->id; ?>
	      </td></tr>

      <?php endforeach; ?>
      <tr>
	  <td colspan="5"><?php echo $this->pagination->getListFooter(); ?></td>
      </tr>
      </tbody>
    </table>
  <?php endif; ?>

<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_ketshop" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
<?php echo JHtml::_('form.token'); ?>
</form>

