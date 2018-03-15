<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
JHtml::_('behavior.framework');

//Create shortcut for params.
$params = $this->item->params;
?>

<div class="product-item">
  <?php echo JLayoutHelper::render('product.title', array('item' => $this->item, 'params' => $params, 'now_date' => $this->nowDate)); ?>

  <?php echo JLayoutHelper::render('product.image', array('item' => $this->item, 'params' => $params)); ?>

  <?php if($params->get('show_tags') && !empty($this->item->tags->itemTags)) : ?>
    <?php echo JLayoutHelper::render('tags', array('item' => $this->item)); ?>
  <?php endif; ?>

  <?php echo $this->item->intro_text; ?>

  <?php echo JLayoutHelper::render('product.availability', array('item' => $this->item, 'params' => $params, 'view' => 'tag')); ?>
  <?php echo JLayoutHelper::render('product.price', $this->item); ?>

  <?php if($this->item->attribute_group) : //Check for product options. ?>
    <span class="space-2"></span>
    <a href="<?php echo JRoute::_(KetshopHelperRoute::getProductRoute($this->item->slug, $this->item->tag_ids, $this->item->language)); ?>">
      <span class="label btn-info">
      <?php echo JText::_('COM_KETSHOP_CHOOSE_OPTIONS'); ?>
      </span>
    </a>
    <span class="space-2"></span>
  <?php endif; ?>

  <?php if($params->get('show_product_page_link')) :
	  if($params->get('access-view')) :
	    $link = JRoute::_(KetshopHelperRoute::getProductRoute($this->item->slug, $this->item->tag_ids, $this->item->language));
	  else : //Redirect the user to the login page.
	    $menu = JFactory::getApplication()->getMenu();
	    $active = $menu->getActive();
	    $itemId = $active->id;
	    $link = new JUri(JRoute::_('index.php?option=com_users&view=login&Itemid='.$itemId, false));
	    $link->setVar('return', base64_encode(JRoute::_(KetshopHelperRoute::getProductRoute($this->item->slug, $this->item->tag_ids, $this->item->language), false)));
	  endif; ?>

  <?php echo JLayoutHelper::render('product.product_page', array('item' => $this->item, 'params' => $params, 'link' => $link)); ?>
  <?php echo JLayoutHelper::render('product.details', $this->item); ?>
  <?php echo JLayoutHelper::render('product.weight_dimensions', $this->item); ?>
  <?php echo JLayoutHelper::render('product.attributes', $this->item); ?>
  <?php endif; ?>


</div>

