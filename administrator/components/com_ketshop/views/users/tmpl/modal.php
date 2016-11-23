<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('formbehavior.chosen', 'select');

$jinput = JFactory::getApplication()->input;
$idNb = $jinput->get->get('id_nb', 0, 'uint');
$type = $jinput->get->get('type', '', 'string');
$field = $jinput->get('field');
$function = $jinput->get('function', 'jQuery.selectItem');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=users&layout=modal&tmpl=component&id_nb='.$idNb.'&type='.$type.'&groups='.JRequest::getVar('groups', '', 'default', 'BASE64').'&excluded='.JRequest::getVar('excluded', '', 'default', 'BASE64'));?>" method="post" name="adminForm" id="adminForm">
	<fieldset class="filter clearfix">
	  <div class="btn-toolbar">
	    <div class="btn-group pull-left">
		    <label for="filter_search">
			    <?php echo JText::_('JSEARCH_FILTER_LABEL'); ?>
		    </label>
	    </div>
	    <div class="btn-group pull-left">
		    <input type="text" name="filter_search" id="filter_search"
		    placeholder="<?php echo JText::_('COM_KETSHOP_ITEMS_SEARCH_FILTER'); ?>"
		    value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
		    size="30" title="<?php echo JText::_('COM_KETSHOP_ITEMS_SEARCH_FILTER'); ?>" />
	    </div>
	    <div class="btn-group pull-left">
		    <button type="submit" class="btn hasTooltip" data-placement="bottom" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>">
			    <span class="icon-search"></span><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
		    <button type="button" class="btn hasTooltip" data-placement="bottom" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.getElementById('filter_search').value='';this.form.submit();">
			    <span class="icon-remove"></span><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
	    </div>
	    <div class="clearfix"></div>
	  </div>

	  <div class="filters pull-left">
	      <ol>
		<li>
		  <label for="filter_group_id">
			  <?php echo
			  JText::_('COM_KETSHOP_FILTER_USER_GROUP'); ?>
		  </label>
		  <?php echo JHtml::_('access.usergroup', 'filter_group_id', $this->state->get('filter.group_id'), 'onchange="this.form.submit()"'); ?>
		</li>
	      </ol>
	  </div>
	</fieldset>

	<table class="table table-striped">
		<thead>
			<tr>
				<th class="left">
					<?php echo JHtml::_('grid.sort', 'COM_KETSHOP_HEADING_NAME', 'a.name', $listDirn, $listOrder); ?>
				</th>
				<th class="nowrap" width="25%">
					<?php echo JHtml::_('grid.sort', 'JGLOBAL_USERNAME', 'a.username', $listDirn, $listOrder); ?>
				</th>
				<th class="nowrap" width="25%">
					<?php echo JHtml::_('grid.sort', 'COM_KETSHOP_HEADING_GROUPS', 'group_names', $listDirn, $listOrder); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="15">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php
			$i = 0;
			foreach ($this->items as $item) : ?>
			<tr class="row<?php echo $i % 2; ?>">
				<td>

	    <a class="pointer" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>('<?php echo
		$item->id; ?>', '<?php echo
		$this->escape(addslashes($item->name)); ?>', '<?php echo
		$this->escape($idNb); ?>', '<?php echo $this->escape($type); ?>');">
		    <?php echo $this->escape($item->name); ?></a>

				</td>
				<td align="center">
					<?php echo $item->username; ?>
				</td>
				<td align="left">
					<?php echo nl2br($item->group_names); ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="field" value="<?php echo $this->escape($field); ?>" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
