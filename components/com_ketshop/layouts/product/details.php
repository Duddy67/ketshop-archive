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

<?php if($params->get('show_code') || $params->get('show_hits')  ||
	 $params->get('show_tax') || $params->get('show_categories')) : ?>

  <table class="table table-condensed small">
  <caption class="text-left font-bold"><?php echo JText::_('COM_KETSHOP_PRODUCT_DETAILS'); ?></caption>

  <?php if($params->get('show_code') && !empty($displayData->code)) : ?>
    <tr><td>
      <?php echo JText::_('COM_KETSHOP_PRODUCT_REFERENCE'); ?>
    </td><td>
      <?php echo $this->escape($displayData->code); ?>
    </td></tr>
  <?php endif; ?>

  <?php if($params->get('show_hits')) : ?>
    <tr><td>
      <?php echo JText::_('COM_KETSHOP_PRODUCT_HITS'); ?>
    </td><td>
      <?php echo $displayData->hits; ?>
    </td></tr>
  <?php endif; ?>


  <?php if($params->get('show_tax')) : ?>
    <tr><td>
      <?php echo JText::_('COM_KETSHOP_PRODUCT_TAX'); ?>
    </td><td>
      <?php if($params->get('show_tax_name')) : ?>
	<?php echo $displayData->tax_name; ?>
      <?php endif; ?>
      <?php echo $displayData->tax_rate.' %'; ?>
    </td></tr>
  <?php endif; ?>

  <?php if($params->get('show_category')) : ?>
    <tr><td>
      <?php echo JText::_('COM_KETSHOP_PRODUCT_CATEGORY'); ?>
    </td><td>
      <?php $title = $this->escape($displayData->category_title); ?>
      <?php if ($params->get('link_category') && $displayData->catslug) : ?>
	<?php $url = '<a href="'.JRoute::_(KetshopHelperRoute::getCategoryRoute($displayData->catslug)).'" itemprop="genre">'.$title.'</a>'; ?>
	<?php echo $url; ?>
      <?php else : ?>
	<?php echo '<span itemprop="genre">'.$title.'</span>'; ?>
      <?php endif; ?>
    </td></tr>
  <?php endif; ?>

  </table>
<?php endif; ?>


