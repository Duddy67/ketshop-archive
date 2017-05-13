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
//Shorcut for product layout path.
$productLayoutPath = JPATH_SITE.'/components/com_ketshop/layouts/product/';
?>

<div class="product-item">
  <?php echo JLayoutHelper::render('title', array('item' => $this->item, 'params' => $params, 'now_date' => $this->nowDate), $productLayoutPath); ?>

  <?php //echo JLayoutHelper::render('icons', array('item' => $this->item, 'user' => $this->user, 'uri' => $this->uri),
				    //JPATH_SITE.'/components/com_ketshop/layouts/'); ?>

  <?php echo JLayoutHelper::render('image', array('item' => $this->item, 'params' => $params),
				    JPATH_SITE.'/components/com_ketshop/layouts/product/'); ?>

  <?php if(($params->get('show_tags') == 'ketshop' || $params->get('show_tags') == 'both') && !empty($this->item->tags->itemTags)) : ?>
    <?php echo JLayoutHelper::render('tags', array('item' => $this->item), JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
  <?php endif; ?>

  <?php echo $this->item->intro_text; ?>

  <?php if($params->get('show_tags') && !empty($this->item->tags->itemTags)) : ?>
	  <?php $this->item->tagLayout = new JLayoutFile('joomla.content.tags'); ?>
	  <?php echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
  <?php endif; ?>

  <?php echo JLayoutHelper::render('availability', array('item' => $this->item, 'params' => $params, 'view' => 'category'), $productLayoutPath); ?>

  <?php if($this->item->attribute_group) : //Check for product options. ?>
    <span class="space-2"></span>
    <a href="<?php echo JRoute::_(KetshopHelperRoute::getProductRoute($this->item->slug, $this->item->catid)); ?>">
      <span class="label btn-info">
      <?php echo JText::_('COM_KETSHOP_CHOOSE_OPTIONS'); ?>
      </span>
    </a>
    <span class="space-2"></span>
  <?php endif; ?>

  <?php if($params->get('show_product_page_link')) :
	  if($params->get('access-view')) :
	    $link = JRoute::_(KetshopHelperRoute::getProductRoute($this->item->slug, $this->item->catid));
	  else : //Redirect the user to the login page.
	    $menu = JFactory::getApplication()->getMenu();
	    $active = $menu->getActive();
	    $itemId = $active->id;
	    $link = new JUri(JRoute::_('index.php?option=com_users&view=login&Itemid='.$itemId, false));
	    $link->setVar('return', base64_encode(JRoute::_(KetshopHelperRoute::getProductRoute($this->item->slug, $this->item->catid), false)));
	  endif; ?>

	<?php echo JLayoutHelper::render('product_page', array('item' => $this->item, 'params' => $params, 'link' => $link), $productLayoutPath); ?>
	<?php echo JLayoutHelper::render('price', $this->item, $productLayoutPath); ?>
	<?php echo JLayoutHelper::render('details', $this->item, $productLayoutPath); ?>
	<?php echo JLayoutHelper::render('weight_dimensions', $this->item, $productLayoutPath); ?>
	<?php echo JLayoutHelper::render('attributes', $this->item, $productLayoutPath); ?>

  <?php endif; ?>
</div>

