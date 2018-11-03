<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

// Create shortcuts.
$item = $displayData['item'];
$params = $displayData['params'];
//echo '<pre>';
//var_dump($item);
//echo '</pre>';
?>

<?php if($params->get('show_image')) : ?>
  <?php if(!empty($item->img_src)) { //Display the image of the product.
	  $size = ShopHelper::getThumbnailSize($item->img_width,
						     $item->img_height, 
						     $item->img_reduction_rate);
	  $imgSrc = $item->img_src;
	  $imgAlt = $item->img_alt;
	}
	else { //Display a default image.
	  $size = ShopHelper::getThumbnailSize(200, 200, $item->img_reduction_rate); 
	  $imgSrc = 'media/com_ketshop/images/missing-picture.jpg';
	  $imgAlt = JText::_('COM_KETSHOP_IMAGE_UNAVAILABLE');
	}
    ?>

    <?php if($params->get('linked_image') && $params->get('access-view')) : //Create the image link.
	    $link = JRoute::_(KetshopHelperRoute::getProductRoute($item->slug, $item->tagid, $item->language));
	?>
      <a href="<?php echo $link; ?>">
    <?php endif; ?>
      <img class="image" src="<?php echo $imgSrc; ?>" width="<?php echo (int)$size['width']; ?>"
	   height="<?php echo (int)$size['height']; ?>" alt="<?php echo $this->escape($imgAlt); ?>" />

    <?php if($params->get('linked_image') && $params->get('access-view')) : //Close the image link. ?>
      </a>
    <?php endif; ?>

  <span class="space-1"></span>
<?php endif; ?>

