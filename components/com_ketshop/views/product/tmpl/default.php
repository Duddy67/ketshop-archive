<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

// Create some shortcuts.
$params = $this->item->params;
$item = $this->item;

//Grab the user session.
$session = JFactory::getSession();

//Get the current location.
$location = ShopHelper::getLocation();
//This session variable is used by the addToCart controller function 
//to redirect the customer after a task is done.
//It's also used by the cart view for the link which brings back the customer to
//his previous location.
$session->set('location', $location, 'ketshop');
?>

<div class="item-page<?php echo $this->pageclass_sfx; ?>" itemscope itemtype="http://schema.org/Product">
  <?php if($item->params->get('show_page_heading')) : ?>
    <div class="page-header">
      <h1><?php echo $this->escape($params->get('page_heading')); ?></h1>
    </div>
  <?php endif; ?>

  <?php echo JLayoutHelper::render('product.title', array('item' => $item, 'params' => $params, 'now_date' => $this->nowDate)); 
        echo JLayoutHelper::render('product.icons', array('item' => $this->item, 'user' => $this->user, 'uri' => $this->uri)); 
        echo JLayoutHelper::render('product.image', array('item' => $this->item, 'params' => $params));
  ?>

  <?php if($params->get('show_tags') && !empty($this->item->tags->itemTags)) : ?>
    <?php echo JLayoutHelper::render('product.tags', array('item' => $this->item)); ?>
  <?php endif; ?>

  <?php if($item->params->get('show_intro')) : ?>
    <?php echo $item->intro_text; ?>
  <?php endif; ?>

  <?php if(!empty($item->full_text)) : ?>
    <?php echo $item->full_text; ?>
  <?php endif; ?>

  <?php echo JLayoutHelper::render('product.availability', array('item' => $this->item, 'params' => $params, 'view' => 'product')); 
        echo JLayoutHelper::render('product.price', $this->item); 
        echo JLayoutHelper::render('product.variants', $this->item); 
        echo JLayoutHelper::render('product.tabs', $this->item);
  ?>
</div>

<?php
//Load jQuery library before our script.
JHtml::_('jquery.framework'); 
$doc = JFactory::getDocument();
//Load the jQuery scripts.
$doc->addScript(JURI::root().'components/com_ketshop/js/variants.js');

