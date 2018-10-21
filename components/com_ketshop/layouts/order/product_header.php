<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');
?>

    <thead>
    <th><?php echo JText::_('COM_KETSHOP_HEADING_PRODUCT'); ?></th>
    <th class="center quantity"><?php echo JText::_('COM_KETSHOP_HEADING_QUANTITY'); ?></th>

    <?php if($displayData['tax_method'] == 'excl_tax') : //Price should be displayed with and without taxes. ?>
      <th><?php echo JText::_('COM_KETSHOP_HEADING_PRICE_EXCL_TAX'); ?></th>
    <?php endif; ?>

    <th><?php echo JText::_('COM_KETSHOP_HEADING_PRICE_INCL_TAX'); ?></th>

    <th><?php echo JText::_('COM_KETSHOP_HEADING_TAX_RATE'); ?></th>
    <th></th>
    </thead>

