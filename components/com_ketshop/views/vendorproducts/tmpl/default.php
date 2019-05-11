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

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=vendorproducts');?>" method="post" name="adminForm" id="adminForm">

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
      <table class="table table-striped">
	<thead>
	  <th>
	  <?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_NAME', 'vp.name', $listDirn, $listOrder); ?>
	  </th>
	  <th width="8%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_STATUS', 'vp.published', $listDirn, $listOrder); ?>
	  </th>
	  <th width="12%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_BASE_PRICE', 'vp.base_price', $listDirn, $listOrder); ?>
	  </th>
	  <th width="12%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_SALE_PRICE', 'vp.sale_price', $listDirn, $listOrder); ?>
	  </th>
	  <th width="8%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_STOCK', 'vp.stock', $listDirn, $listOrder); ?>
	  </th>
	  <th width="8%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_SALES', 'vp.sales', $listDirn, $listOrder); ?>
	  </th>
	  <th width="10%">
	  <?php echo JHtml::_('searchtools.sort', 'JDATE', 'vp.created', $listDirn, $listOrder); ?>
	  </th>
	  <th width="5%">
	  <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'vp.id', $listDirn, $listOrder); ?>
	  </th>
	</thead>

	<tbody>
	<?php foreach ($this->items as $i => $item) : ?>

	<tr class="row-<?php echo $i % 2; ?>"><td>
		<a href="index.php?option=com_ketshop&view=vendorproduct&id=<?php echo $item->id; ?>">
			<?php echo $this->escape($item->name); ?></a>
		</td>
		<td>
		  <?php echo $item->published; ?>
		</td>
		<td>
		  <?php echo UtilityHelper::floatFormat($item->base_price).' '.$this->shopSettings['currency']; ?>
		</td>
		<td>
		  <?php echo UtilityHelper::floatFormat($item->sale_price).' '.$this->shopSettings['currency']; ?>
		</td>
		<td class="center">
		  <?php echo $item->stock; ?>
		</td>
		<td class="center">
		  <?php echo $item->sales; ?>
		</td>
		<td>
		  <?php echo JHTML::_('date',$item->created, JText::_('COM_KETSHOP_DATE_FORMAT')); ?>
		</td>
		<td class="center">
		  <?php echo (int) $item->id; ?>
		</td></tr>

	<?php endforeach; ?>
	<tr>
	    <td colspan="8"><?php echo $this->pagination->getListFooter(); ?></td>
	</tr>
	</tbody>
      </table>
  <?php endif; ?>

<input type="hidden" name="option" value="com_ketshop" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

