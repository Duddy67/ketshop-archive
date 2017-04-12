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
?>


<?php if($params->get('show_price') && !empty($displayData->base_price) && !empty($displayData->sale_price)) : ?>

  <?php if(!empty($displayData->pricerules) && $displayData->pricerules[0]['show_rule']) : //Display rules info if any. ?>
    <?php foreach($displayData->pricerules as $ruleInfo) : ?>
      <?php if($params->get('show_rule_name')) : ?>
	<span class="rule-name"><?php echo $ruleInfo['name']; ?></span>
      <?php endif; ?>
	<span class="label label-warning">
	  <?php echo UtilityHelper::formatPriceRule($ruleInfo['operation'], $ruleInfo['value']); ?>
	</span>
	<span class="space-1"></span>
	<?php if(!empty($ruleInfo['description'])) : ?>
	  <div class="rule-description"><?php echo $ruleInfo['description']; ?></div>
	<?php endif; ?>
    <?php endforeach; ?>

    <span class="space-1"></span>

    <span class="striked-price">
      <?php echo UtilityHelper::formatNumber($displayData->sale_price); ?>
      <?php echo $displayData->shop_settings['currency']; ?></span>
    <span class="space">&nbsp;</span>
  <?php endif; ?>

    <span class="price"><?php echo UtilityHelper::formatNumber($displayData->final_price); ?></span>
    <span class="currency"><?php echo $displayData->shop_settings['currency']; ?></span>

    <?php if($displayData->shop_settings['tax_method'] == 'excl_tax') : ?>
      <span class="label label-default"><?php echo JText::_('COM_KETSHOP_FIELD_EXCLUDING_TAXES'); ?></span>
    <?php else : ?>
      <span class="label label-default"><?php echo JText::_('COM_KETSHOP_FIELD_INCLUDING_TAXES'); ?></span>
    <?php endif; ?>

    <?php if($displayData->shop_settings['tax_method'] == 'excl_tax' && $params->get('show_price_with_taxes')) : ?>
      <span class="space-1"></span>
      <span class="price-incl-tax small"><?php echo UtilityHelper::formatNumber($displayData->final_price_with_taxes); ?>
      <?php echo $displayData->shop_settings['currency']; ?>
      <?php echo JText::_('COM_KETSHOP_FIELD_INCLUDING_TAXES'); ?></span>
    <?php endif; ?>

<?php endif; ?>
