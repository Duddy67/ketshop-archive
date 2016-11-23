<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

// Create a shortcut for params.
$params = $displayData->params;

//Test if product weight and/or dimensions must be displayed. 
$weight = $dimensions = false;
if($params->get('show_weight')
    && ($params->get('weight_location') == $displayData->weight_location || $params->get('weight_location') == 'both')
    && $displayData->weight != 0) {
  $weight = true;
}

if($params->get('show_dimensions')
   && ($params->get('dimensions_location') == $displayData->dimensions_location
   || $params->get('dimensions_location') == 'both')
   && $displayData->length != 0 && $displayData->width != 0 && $displayData->height != 0) {
  $dimensions = true;
}
?>

<?php if($weight || $dimensions) : ?>
  <table class="table table-condensed small">
  <caption class="text-left font-bold"><?php echo JText::_('COM_KETSHOP_PRODUCT_WEIGHT_DIMENSIONS'); ?></caption>
  <?php if($weight) : ?>
    <tr><td>
      <?php echo JText::_('COM_KETSHOP_PRODUCT_WEIGHT'); ?>
    </td><td>
      <?php echo UtilityHelper::formatNumber($displayData->weight); ?>
      <?php echo $displayData->weight_unit; ?>
    </td></tr>
  <?php endif; ?>

  <?php if($dimensions) : ?>
    <tr><td>
      <?php echo JText::_('COM_KETSHOP_PRODUCT_LENGTH'); ?>
    </td><td>
      <?php echo UtilityHelper::formatNumber($displayData->length); ?>
      <?php echo $displayData->dimensions_unit; ?>
    </td></tr>
    <tr><td>
      <?php echo JText::_('COM_KETSHOP_PRODUCT_WIDTH'); ?>
    </td><td>
      <?php echo UtilityHelper::formatNumber($displayData->width); ?>
      <?php echo $displayData->dimensions_unit; ?>
    </td></tr>
    <tr><td>
      <?php echo JText::_('COM_KETSHOP_PRODUCT_HEIGHT'); ?>
    </td><td>
      <?php echo UtilityHelper::formatNumber($displayData->height); ?>
      <?php echo $displayData->dimensions_unit; ?>
    </td></tr>
  <?php endif; ?>
  </table>
<?php endif; ?>

