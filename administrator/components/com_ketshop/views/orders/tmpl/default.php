<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.tabstate');


$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$archived = $this->state->get('filter.published') == 2 ? true : false;
$trashed = $this->state->get('filter.published') == -2 ? true : false;

//Build a status array.
$status = array();
$status['completed'] = 'COM_KETSHOP_OPTION_COMPLETED_STATUS';
$status['pending'] = 'COM_KETSHOP_OPTION_PENDING_STATUS';
$status['other'] = 'COM_KETSHOP_OPTION_OTHER_STATUS';
$status['cancelled'] = 'COM_KETSHOP_OPTION_CANCELLED_STATUS';
$status['error'] = 'COM_KETSHOP_OPTION_ERROR_STATUS';
$status['no_shipping'] = 'COM_KETSHOP_OPTION_NO_SHIPPING_STATUS';
$status['unfinished'] = 'COM_KETSHOP_OPTION_UNFINISHED_STATUS';
$status['cartbackup'] = 'COM_KETSHOP_OPTION_CART_BACKUP_STATUS';
$status['undefined'] = 'COM_KETSHOP_OPTION_UNDEFINED_STATUS';
?>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=orders');?>" method="post" name="adminForm" id="adminForm">

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
    <table class="table table-striped" id="orderList">
      <thead>
      <tr>
	<th width="1%">
	<input type="checkbox" name="checkall-toggle" value="" onclick="checkAll(this)" />
	</th>
	<th width="5%">
	<?php echo JHtml::_('searchtools.sort', 'JPUBLISHED', 'o.published', $listDirn, $listOrder); ?>
	</th>
	<th>
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_ORDER_NUMBER', 'o.name', $listDirn, $listOrder); ?>
	</th>
	<th width="15%">
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_CUSTOMER', 'customer', $listDirn, $listOrder); ?>
	</th>
	<th width="12%">
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_CART_STATUS', 'o.cart_status', $listDirn, $listOrder); ?>
	</th>
	<th width="12%">
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_ORDER_STATUS', 'o.order_status', $listDirn, $listOrder); ?>
	</th>
	<th width="12%">
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_PAYMENT_STATUS', 'payment_status', $listDirn, $listOrder); ?>
	</th>
	<th width="12%">
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_SHIPPING_STATUS', 'shipping_status', $listDirn, $listOrder); ?>
	</th>
	<th width="10%">
	<?php echo JHtml::_('searchtools.sort', 'JDATE', 'o.created', $listDirn, $listOrder); ?>
	</th>
	<th width="1%">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'o.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :

	    $canCheckin = $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
	    //Check only against component permission as order items have no categories.
	    $canChange = ($user->authorise('core.edit.state', 'com_ketshop') && $canCheckin);
	    $canEdit = $user->authorise('core.edit', 'com_ketshop');

	    //
	    if($item->cart_status != 'completed') {
	      $item->payment_status = 'undefined'; 
	      $item->shipping_status = 'undefined'; 
	    }
      ?>
      <tr class="row<?php echo $i % 2; ?>">
	      <td class="center">
		      <?php echo JHtml::_('grid.id', $i, $item->id); ?>
	      </td>
	      <td class="center">
		<div class="btn-group">
		  <?php echo JHtml::_('jgrid.published', $item->published, $i, 'orders.', $canChange, 'cb'); ?>
		  <?php
		  // Create dropdown items
		  $action = $archived ? 'unarchive' : 'archive';
		  JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'orders');

		  $action = $trashed ? 'untrash' : 'trash';
		  JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'orders');

		  // Render dropdown list
		  echo JHtml::_('actionsdropdown.render', $this->escape($item->name));
		  ?>
		</div>
	      </td>
	      <td>
	    <?php if ($item->checked_out) : ?>
	      <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'orders.', $canCheckin); ?>
	    <?php endif; ?>

	    <?php if($canEdit || $canEditOwn) : ?>
	      <a href="<?php echo JRoute::_('index.php?option=com_ketshop&task=order.edit&id='.$item->id);?>">
		      <?php echo $this->escape($item->name); ?></a>
	    <?php else : ?>
	      <?php echo $this->escape($item->name); ?>
	    <?php endif; ?>
	      </td>
	      <td>
		<?php echo $this->escape($item->customer); ?>
	      </td>
	      <td>
		<?php echo JText::_($status[$item->cart_status]); ?>
	      </td>
	      <td>
		<?php echo JText::_($status[$item->order_status]); ?>
	      </td>
	      <td>
		<?php echo JText::_($status[$item->payment_status]); ?>
	      </td>
	      <td>
		<?php echo JText::_($status[$item->shipping_status]); ?>
	      </td>
	      <td>
		<?php echo JHTML::_('date',$item->created, JText::_('COM_KETSHOP_DATE_FORMAT')); ?>
	      </td>
	      <td class="center">
		<?php echo (int) $item->id; ?>
	      </td></tr>

      <?php endforeach; ?>
      <tr>
	  <td colspan="10"><?php echo $this->pagination->getListFooter(); ?></td>
      </tr>
      </tbody>
    </table>
  <?php endif;?>

<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_ketshop" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

