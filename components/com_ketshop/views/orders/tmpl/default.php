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

//Build a status array.
$status = array();
$status['completed'] = 'COM_KETSHOP_OPTION_COMPLETED_STATUS';
$status['pending'] = 'COM_KETSHOP_OPTION_PENDING_STATUS';
$status['other'] = 'COM_KETSHOP_OPTION_OTHER_STATUS';
$status['cancelled'] = 'COM_KETSHOP_OPTION_CANCELLED_STATUS';
$status['error'] = 'COM_KETSHOP_OPTION_ERROR_STATUS';
$status['no_shipping'] = 'COM_KETSHOP_OPTION_NO_SHIPPING_STATUS';
$status['unfinished'] = 'COM_KETSHOP_OPTION_UNFINISHED_STATUS';
$status['undefined'] = 'COM_KETSHOP_OPTION_UNDEFINED_STATUS';
$status['cartbackup'] = 'COM_KETSHOP_OPTION_CART_BACKUP_STATUS';
?>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=orders');?>" method="post" name="adminForm" id="adminForm">

<?php
// Search tools bar 
echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
?>
  <br />
  <table class="table table-striped">
    <thead>
      <th width="20%">
      <?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_ORDER_NUMBER', 'order_nb', $listDirn, $listOrder); ?>
      </th>
      <th width="20%">
      <?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_ORDER_STATUS', 'order_status', $listDirn, $listOrder); ?>
      </th>
      <th width="20%">
      <?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_PAYMENT_STATUS', 'payment_status', $listDirn, $listOrder); ?>
      </th>
      <th width="20%">
      <?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_SHIPPING_STATUS', 'shipping_status', $listDirn, $listOrder); ?>
      </th>
      <th width="10%">
      <?php echo JHtml::_('searchtools.sort', 'JDATE', 'o.created', $listDirn, $listOrder); ?>
      </th>
      <th width="5%">
      <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'o.id', $listDirn, $listOrder); ?>
      </th>
    </thead>

    <tbody>
    <?php foreach ($this->items as $i => $item) : ?>

    <tr class="row-<?php echo $i % 2; ?>"><td>
	    <a href="index.php?option=com_ketshop&task=order.editCustomerNote&order_id=<?php echo $item->id; ?>">
		    <?php echo $this->escape($item->order_nb); ?></a>
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
	<td colspan="6"><?php echo $this->pagination->getListFooter(); ?></td>
    </tr>
    </tbody>
  </table>

<input type="hidden" name="option" value="com_ketshop" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

