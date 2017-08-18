<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>


<ol class="nav nav-tabs nav-stacked">
<?php foreach ($this->link_items as &$item) : ?>
	<li>
	  <a href="<?php echo JRoute::_(KetshopHelperRoute::getProductRoute($item->slug, $item->tag_ids, $item->language, true)); ?>">
		      <?php echo $item->name; ?></a>
	</li>
<?php endforeach; ?>
</ol>

