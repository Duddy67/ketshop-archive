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

//Computes the tabs rendering.
$tabs = array('details' => '', 'weight_dimensions' => '', 'attributes' => '');

foreach($tabs as $key => $tab) {
  //Gets the tab rendering.
  $rendering = JLayoutHelper::render('product.'.$key, $displayData);
  $rendering = trim($rendering);

  if(!empty($rendering)) {
    //Stores the tab content.
    $tabs[$key] = $rendering;
  }
  else {
    //In case there is nothing to display, the tab is removed from the array.
    unset($tabs[$key]);
  }
}

//Displays the tabs.
if(!empty($tabs)) :
  //The very first tab must be the active one.
  $active = 'class="active"';

  //Builds the tabs.
?>
  <ul class="nav nav-tabs">
<?php foreach($tabs as $key => $tab) : ?>
    <li <?php echo $active; ?>>
    <a data-toggle="tab" href="#<?php echo $key.'-'.$displayData->id; ?>"><?php echo JText::_('COM_KETSHOP_PRODUCT_'.strtoupper($key)); ?></a></li>
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
    <div id="<?php echo $key.'-'.$displayData->id; ?>" class="tab-pane fade<?php echo $active; ?>">
    <?php echo $tab; ?>
    </div>
<?php
  //Only the first tab content can be active.
  $active = '';
  endforeach; ?>
  </div>
<?php endif; ?>

