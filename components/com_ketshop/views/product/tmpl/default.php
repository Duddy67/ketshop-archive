<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 Lucas Sanner
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

  <?php echo JLayoutHelper::render('product_title', array('item' => $item, 'params' => $params, 'now_date' => $this->nowDate),
				      JPATH_SITE.'/components/com_ketshop/layouts/'); ?>

  <?php //echo JLayoutHelper::render('icons', array('item' => $this->item, 'user' => $this->user, 'uri' => $this->uri),
				    //JPATH_SITE.'/components/com_ketshop/layouts/'); ?>

  <?php echo JLayoutHelper::render('image', array('item' => $this->item, 'params' => $params),
				    JPATH_SITE.'/components/com_ketshop/layouts/product/'); ?>

  <?php if(($params->get('show_tags') == 'ketshop' || $params->get('show_tags') == 'both') && !empty($this->item->tags->itemTags)) : ?>
    <?php echo JLayoutHelper::render('tags', array('item' => $this->item), JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
  <?php endif; ?>

  <?php if($item->params->get('show_intro')) : ?>
    <?php echo $item->intro_text; ?>
  <?php endif; ?>

  <?php if(!empty($item->full_text)) : ?>
    <?php echo $item->full_text; ?>
  <?php endif; ?>

  <?php if(($params->get('show_tags') == 'standard' || $params->get('show_tags') == 'both') && !empty($this->item->tags->itemTags)) : ?>
	  <?php $this->item->tagLayout = new JLayoutFile('joomla.content.tags'); ?>
	  <?php echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
  <?php endif; ?>

  <?php echo JLayoutHelper::render('availability', array('item' => $this->item, 'params' => $params), JPATH_SITE.'/components/com_ketshop/layouts/product/'); ?>

  <?php echo JLayoutHelper::render('price', $this->item, JPATH_SITE.'/components/com_ketshop/layouts/product/'); ?>
  <?php echo JLayoutHelper::render('details', $this->item, JPATH_SITE.'/components/com_ketshop/layouts/product/'); ?>
  <?php echo JLayoutHelper::render('weight_dimensions', $this->item, JPATH_SITE.'/components/com_ketshop/layouts/product/'); ?>
  <?php echo JLayoutHelper::render('attributes', $this->item, JPATH_SITE.'/components/com_ketshop/layouts/product/'); ?>
  <?php echo JLayoutHelper::render('options', $this->item, JPATH_SITE.'/components/com_ketshop/layouts/product/'); ?>
</div>

<?php
//Load jQuery library before our script.
JHtml::_('jquery.framework'); 
$doc = JFactory::getDocument();
//Load the jQuery scripts.
$doc->addScript(JURI::root().'components/com_ketshop/js/options.js');

