<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.tooltip');
JHtml::_('script','system/multiselect.js',false,true);
JHtml::_('behavior.tabstate');

$user = JFactory::getUser();

$items = array('products' => 'star-empty',
	       'attributes' => 'price-tag',
	       'taxes' => 'pie-chart',
	       'currencies' => 'coin-dollar',
	       'countries' => 'flag',
	       'pricerules' => 'calculator',
	       'coupons' => 'barcode',
	       'orders' => 'cart',
	       'customers' => 'users',
	       'paymentmodes' => 'credit-card',
	       'shippings' => 'truck',
	       'shippers' => 'move-up',
	       'translations' => 'earth');
?>

<form action="<?php echo JRoute::_('index.php?option=com_ketshop&view=ketshop');?>" method="post" name="adminForm" id="adminForm">

<div id="cpanel" class="row-fluid"> 

<?php foreach($items as $name => $icon) : ?>
  <div class="thumbnail">
  <a href="index.php?option=com_ketshop&view=<?php echo $name; ?>">
  <span class="icon-shop-<?php echo $icon; ?>" style="font-size:32px;"></span>
  <span class="ketshop-icon-title"><?php echo JText::_('COM_KETSHOP_KETSHOP_'.strtoupper($name).'_TITLE'); ?></span></a>
  </div>
<?php endforeach; ?>

<div class="thumbnail">
<a href="index.php?option=com_categories&extension=com_ketshop">
<span class="icon-folder" style="font-size:32px;"></span>
<span class="ketshop-icon-title"><?php echo JText::_('COM_KETSHOP_KETSHOP_CATEGORIES_TITLE'); ?></span></a>
</div>

</div>


<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_ketshop" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

