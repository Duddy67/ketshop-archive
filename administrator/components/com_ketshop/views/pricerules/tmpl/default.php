<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('behavior.tabstate');
JHtml::_('formbehavior.chosen', 'select');


$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$archived = $this->state->get('filter.published') == 2 ? true : false;
$trashed = $this->state->get('filter.published') == -2 ? true : false;
//Check only against component permission as price rule items have no categories.
$canOrder = $user->authorise('core.edit.state', 'com_ketshop');
$saveOrder = $listOrder == 'pr.ordering';

if($saveOrder) {
  $saveOrderingUrl = 'index.php?option=com_ketshop&task=pricerules.saveOrderAjax&tmpl=component';
  JHtml::_('sortablelist.sortable', 'priceruleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$behavior = array('AND' => 'CUMULATIVE', 'XOR' => 'EXCLUSIVE', 'CPN_XOR' => 'COUPON_EXCLUSIVE', 'CPN_AND' => 'COUPON_CUMULATIVE');
?>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=pricerules');?>" method="post" name="adminForm" id="adminForm">

<?php if (!empty( $this->sidebar)) : ?>
  <div id="j-sidebar-container" class="span2">
	  <?php echo $this->sidebar; ?>
  </div>
  <div id="j-main-container" class="span10">
<?php else : ?>
  <div id="j-main-container">
<?php endif;?>

<?php
// Search tools bar 
echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
?>

  <div class="clr"> </div>
  <?php if (empty($this->items)) : ?>
	<div class="alert alert-no-items">
		<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
  <?php else : ?>
    <table class="table table-striped" id="priceruleList">
      <thead>
	<tr>
	<th width="1%" class="nowrap center hidden-phone">
	<?php echo JHtml::_('searchtools.sort', '', 'pr.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
	</th>
	<th width="1%" class="hidden-phone">
	<?php echo JHtml::_('grid.checkall'); ?>
	</th>
	<th width="1%" style="min-width:55px" class="nowrap center">
	<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'pr.published', $listDirn, $listOrder); ?>
	</th>
	<th>
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_NAME', 'pr.name', $listDirn, $listOrder); ?>
	</th>
	<th width="5%">
	<?php echo JHtml::_('grid.sort', 'COM_KETSHOP_HEADING_TYPE', 'pr.type', $listDirn, $listOrder); ?>
	</th>
	<th width="5%">
	<?php echo JHtml::_('grid.sort', 'COM_KETSHOP_HEADING_OPERATION', 'pr.value', $listDirn, $listOrder); ?>
	</th>
      <th width="5%">
      <?php echo JHtml::_('grid.sort', 'COM_KETSHOP_HEADING_BEHAVIOR', 'pr.behavior', $listDirn, $listOrder); ?>
      </th>
      <th width="15%">
      <?php echo JText::_('COM_KETSHOP_HEADING_TARGET'); ?>
      </th>
      <th width="15%">
      <?php echo JText::_('COM_KETSHOP_HEADING_RECIPIENT'); ?>
      </th>
	<th width="10%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_CREATED_BY', 'user', $listDirn, $listOrder); ?>
	</th>
	<th width="5%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JDATE', 'pr.created', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'pr.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :

      $ordering = ($listOrder == 'pr.ordering');
      $canEdit = $user->authorise('core.edit','com_ketshop.pricerule.'.$item->id);
      $canEditOwn = $user->authorise('core.edit.own', 'com_ketshop.pricerule.'.$item->id) && $item->created_by == $userId;
      $canCheckin = $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
      $canChange = ($user->authorise('core.edit.state','com_ketshop.pricerule.'.$item->id) && $canCheckin) || $canEditOwn; 
      ?>

      <tr class="row<?php echo $i % 2; ?>">
	<td class="order nowrap center hidden-phone">
	  <?php
	  $iconClass = '';
	  if(!$canChange)
	  {
	    $iconClass = ' inactive';
	  }
	  elseif(!$saveOrder)
	  {
	    $iconClass = ' inactive tip-top hasTooltip" title="' . JHtml::tooltipText('JORDERINGDISABLED');
	  }
	  ?>
	  <span class="sortable-handler<?php echo $iconClass ?>">
		  <i class="icon-menu"></i>
	  </span>
	  <?php if($canChange && $saveOrder) : ?>
	      <input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering;?>" class="width-20 text-area-order " />
	  <?php endif; ?>
	  </td>
	  <td class="center hidden-phone">
		  <?php echo JHtml::_('grid.id', $i, $item->id); ?>
	  </td>
	  <td class="center">
	    <div class="btn-group">
	      <?php echo JHtml::_('jgrid.published', $item->published, $i, 'pricerules.', $canChange, 'cb'); ?>
	      <?php
	      // Create dropdown items
	      $action = $archived ? 'unarchive' : 'archive';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'pricerules');

	      $action = $trashed ? 'untrash' : 'trash';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'pricerules');

	      // Render dropdown list
	      echo JHtml::_('actionsdropdown.render', $this->escape($item->name));
	      ?>
	    </div>
	  </td>
	  <td class="has-context">
	    <div class="pull-left">
	      <?php if ($item->checked_out) : ?>
		  <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'pricerules.', $canCheckin); ?>
	      <?php endif; ?>
	      <?php if($canEdit || $canEditOwn) : ?>
		<a href="<?php echo JRoute::_('index.php?option=com_ketshop&task=pricerule.edit&id='.$item->id);?>" title="<?php echo JText::_('JACTION_EDIT'); ?>">
			<?php echo $this->escape($item->name); ?></a>
	      <?php else : ?>
		<?php echo $this->escape($item->name); ?>
	      <?php endif; ?>
	    </div>
	  </td>
	  <td>
	  <?php switch($item->type) {
		  case 'catalog':
		    echo JText::_('COM_KETSHOP_CATALOG_TITLE');
		    break;

		  case 'cart':
		    echo JText::_('COM_KETSHOP_CART_TITLE');
		    break;
		}
	  ?>
	  </td>
	  <td>
	    <?php echo UtilityHelper::formatPriceRule($item->operation, $item->value); ?>
	  </td>
	  <td class="center">
	    <?php echo JText::_('COM_KETSHOP_OPTION_'.$behavior[$item->behavior]); ?>
	  </td>
	  <td>
	  <?php if($item->type == 'cart') : ?>
	    <?php switch($item->target) 
		  {
		    case 'cart_amount':
		      echo JText::_('COM_KETSHOP_CART_AMOUNT_TITLE');
		      break;

		    case 'shipping_cost':
		      echo JText::_('COM_KETSHOP_SHIPPING_COST_TITLE');
		      break;
		  }
	    ?>
	  <?php else : //item->type != cart ?>
	    <?php if(count($item->targets) > 2) : ?>
	      <?php echo JText::_('COM_KETSHOP_MULTIPLE_TARGETS_TITLE');?></p>
	    <?php else : ?>
		<?php for($j = 0; $j < count($item->targets); $j++) : //Display target titles. ?>
		  <?php echo $this->escape($item->targets[$j]); ?>
		      <?php if($j + 1 < count($item->targets)) : //Separate target titles with coma. ?>
			<?php echo ', '; ?>
		      <?php endif; ?>
		<?php endfor; ?>
	    <?php endif; ?>
	  <?php endif; ?>

	  </td>
	  <td>
	  <?php if(count($item->recipients) > 2) : ?>
	    <?php echo JText::_('COM_KETSHOP_MULTIPLE_RECIPIENTS_TITLE');?></p>
	  <?php else : ?>
	      <?php for($j = 0; $j < count($item->recipients); $j++) : //Display recipient titles. ?>
		<?php echo $this->escape($item->recipients[$j]); ?>
		    <?php if($j + 1 < count($item->recipients)) : //Separate recipient titles with coma. ?>
		      <?php echo ', '; ?>
		    <?php endif; ?>
	      <?php endfor; ?>
	  <?php endif; ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo $this->escape($item->user); ?>
	  </td>
	  <td class="nowrap small hidden-phone">
	    <?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
	  </td>
	  <td>
	    <?php echo $item->id; ?>
	  </td></tr>

      <?php endforeach; ?>
      <tr>
	  <td colspan="12"><?php echo $this->pagination->getListFooter(); ?></td>
      </tr>
      </tbody>
    </table>
  <?php endif; ?>

<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_ketshop" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>
