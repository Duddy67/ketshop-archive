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

  foreach($variants as $key => $variant) {
    //$tabs[] = ''; 
    $obj = (object)$variant;
    $obj->params = $product->params;
    $obj->shop_settings = $product->shop_settings;
    $rendering = JLayoutHelper::render('product.price', $obj);
    $rendering .= JLayoutHelper::render('product.availability', array('item' => $obj, 'params' => $obj->params, 'view' => 'product')); 
    $rendering .= JLayoutHelper::render('product.tabs', $obj);
    $rendering = trim($rendering);
    $tabs[] = $rendering;
  }
//echo '<pre>';
//var_dump($tabs);
//echo '</pre>';


  //Displays the tabs.
  if(!empty($tabs)) :
    //The very first tab must be the active one.
    $active = 'class="active"';

    //Builds the tabs.
  ?>
    <ul class="nav nav-tabs">
  <?php foreach($tabs as $key => $tab) : ?>
      <li <?php echo $active; ?>>
      <a data-toggle="tab" href="#<?php echo 'variant-'.$key; ?>"><?php echo $variants[$key]['name']; ?></a></li>
  <?php
    //Only the first tab can be active.
    $active = '';
    endforeach;

    //The very first tab content must be the active one.
    $active = ' in active';
  ?>
    </ul>

    <div class="tab-content">
  <?php foreach($tabs as $key => $tab) : ?>
      <div id="<?php echo 'variant-'.$key; ?>" class="tab-pane fade<?php echo $active; ?>">
      <?php echo $tab; ?>
      </div>
  <?php
    //Only the first tab content can be active.
    $active = '';
    endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>


