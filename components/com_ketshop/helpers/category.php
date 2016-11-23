<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * KetShop Component Category Tree
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_ketshop
 * @since       1.6
 */
class KetshopCategories extends JCategories
{
  public function __construct($options = array())
  {
    $options['table'] = '#__ketshop_product';
    $options['extension'] = 'com_ketshop';

    /* IMPORTANT: By default publish parent function invoke a field called "state" to
     *            publish/unpublish (but also archived, trashed etc...) an item.
     *            Since our field is called "published" we must informed the 
     *            JCategories publish function in setting the "statefield" index of the 
     *            options array
    */
    $options['statefield'] = 'published';

    parent::__construct($options);
  }
}
