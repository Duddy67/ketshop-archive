<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
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
      <?php echo '<span itemprop="genre">'.$this->escape($displayData->category_title).'</span>'; ?>
    </td></tr>
  <?php endif; ?>

  </table>
<?php endif; ?>


