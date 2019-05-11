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
//Check only against component permission as shipping items have no categories.
$canOrder = $user->authorise('core.edit.state', 'com_ketshop');
$saveOrder = $listOrder == 's.ordering';

if($saveOrder) {
  $saveOrderingUrl = 'index.php?option=com_ketshop&task=shippings.saveOrderAjax&tmpl=component';
  JHtml::_('sortablelist.sortable', 'shippingList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
//Build a delivery type array.
$deliveryType = array();
$deliveryType['at_destination'] = 'COM_KETSHOP_OPTION_AT_DESTINATION';
$deliveryType['at_delivery_point'] = 'COM_KETSHOP_OPTION_AT_DELIVERY_POINT';

$weightUnit = $this->config->get('shipping_weight_unit');
?>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=shippings');?>" method="post" name="adminForm" id="adminForm">

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
    <table class="table table-striped" id="shippingList">
      <thead>
	<tr>
	<th width="1%" class="nowrap center hidden-phone">
	<?php echo JHtml::_('searchtools.sort', '', 's.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
	</th>
	<th width="1%" class="hidden-phone">
	<?php echo JHtml::_('grid.checkall'); ?>
	</th>
	<th width="1%" style="min-width:55px" class="nowrap center">
	<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 's.published', $listDirn, $listOrder); ?>
	</th>
	<th>
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_NAME', 's.name', $listDirn, $listOrder); ?>
	</th>
	<th width="10%">
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_DELIVERY_TYPE', 's.delivery_type', $listDirn, $listOrder); ?>
	</th>
	<th width="10%">
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_MINIMUM_WEIGHT', 's.min_weight', $listDirn, $listOrder); ?>
	</th>
	<th width="10%">
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_MAXIMUM_WEIGHT', 's.max_weight', $listDirn, $listOrder); ?>
	</th>
	<th width="10%">
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_MINIMUM_PRODUCT', 's.min_product', $listDirn, $listOrder); ?>
	</th>
	<th width="10%">
	<?php echo JHtml::_('searchtools.sort', 'COM_KETSHOP_HEADING_MAXIMUM_PRODUCT', 's.max_product', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_CREATED_BY', 'user', $listDirn, $listOrder); ?>
	</th>
	<th width="5%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JDATE', 's.created', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 's.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :

      $ordering = ($listOrder == 's.ordering');
      $canEdit = $user->authorise('core.edit','com_ketshop.shipping.'.$item->id);
      $canEditOwn = $user->authorise('core.edit.own', 'com_ketshop.shipping.'.$item->id) && $item->created_by == $userId;
      $canCheckin = $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
      $canChange = ($user->authorise('core.edit.state','com_ketshop.shipping.'.$item->id) && $canCheckin) || $canEditOwn; 
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
	      <?php echo JHtml::_('jgrid.published', $item->published, $i, 'shippings.', $canChange, 'cb'); ?>
	      <?php
	      // Create dropdown items
	      $action = $archived ? 'unarchive' : 'archive';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'shippings');

	      $action = $trashed ? 'untrash' : 'trash';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'shippings');

	      // Render dropdown list
	      echo JHtml::_('actionsdropdown.render', $this->escape($item->name));
	      ?>
	    </div>
	  </td>
	  <td class="has-context">
	    <div class="pull-left">
	      <?php if ($item->checked_out) : ?>
		  <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'shippings.', $canCheckin); ?>
	      <?php endif; ?>
	      <?php if($canEdit || $canEditOwn) : ?>
		<a href="<?php echo JRoute::_('index.php?option=com_ketshop&task=shipping.edit&id='.$item->id);?>" title="<?php echo JText::_('JACTION_EDIT'); ?>">
			<?php echo $this->escape($item->name); ?></a>
	      <?php else : ?>
		<?php echo $this->escape($item->name); ?>
	      <?php endif; ?>
	    </div>
	  </td>
	  <td>
	    <?php echo JText::_($deliveryType[$item->delivery_type]); ?>
	  </td>
	  <td>
	    <?php echo UtilityHelper::floatFormat($item->min_weight).' '.$weightUnit; ?>
	  </td>
	  <td>
	    <?php echo UtilityHelper::floatFormat($item->max_weight).' '.$weightUnit; ?>
	  </td>
	  <td>
	    <?php echo $item->min_product; ?>
	  </td>
	  <td>
	    <?php echo $item->max_product; ?>
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
	  <td colspan="15"><?php echo $this->pagination->getListFooter(); ?></td>
      </tr>
      </tbody>
    </table>
  <?php endif; ?>

<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_ketshop" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

