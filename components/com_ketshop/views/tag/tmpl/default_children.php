<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
$class = ' class="first"';
?>
<ul class="subdirectories">
<?php foreach ($this->children as $id => $child) :
        //Check if the current tag has children.
        $hasChildren = false;
	if(isset($this->children[$id + 1]) && $this->children[$id + 1]->level > $child->level) {
	  $hasChildren = true;
	}

	if($this->params->get('show_unused_tags') || $child->numitems || $hasChildren) :
	if(!isset($this->children[$id + 1]) || $this->children[$id + 1]->level < $child->level) {
		$class = ' class="last"';
	}
	?>
	<li<?php echo $class; ?>>
		<?php $class = ''; ?>
		  <span class="item-title"><a href="<?php echo JRoute::_(KetshopHelperRoute::getTagRoute($child->id.':'.$child->alias, $child->path));?>">
				<?php echo $this->escape($child->title); ?></a>
			</span>

			<?php if ($this->params->get('show_subtag_desc') == 1) :?>
			<?php if ($child->description) : ?>
				<div class="category-desc">
					<?php echo JHtml::_('content.prepare', $child->description, '', 'com_ketshop.tag'); ?>
				</div>
			<?php endif; ?>
            <?php endif; ?>

            <?php if ($this->params->get('show_tagged_num_products') == 1) :?>
			<dl class="product-count"><dt>
				<?php echo JText::_('COM_KETSHOP_NUM_PRODUCTS'); ?></dt>
				<dd><?php echo $child->numitems; ?></dd>
			</dl>
		<?php endif; ?>
		</li>
	<?php endif; ?>
	<?php endforeach; ?>
</ul>
