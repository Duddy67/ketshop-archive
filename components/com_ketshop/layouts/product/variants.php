<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

$product = $displayData;

if($product->nb_variants > 1) : 

// Create a shortcut for product and variants.
$variants = $displayData->variants;

$tabs = array();

foreach($variants as $variant) {
  //$tabs[$variant['var_id']] = 
}
?>

<?php endif; ?>


