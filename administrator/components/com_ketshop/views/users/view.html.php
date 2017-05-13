<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View class for a list of users.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_users
 * @since		1.6
 */
class KetshopViewUsers extends JViewLegacy
{
  protected $items;
  protected $pagination;
  protected $state;

  /**
   * Display the view
   */
  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->pagination = $this->get('Pagination');
    $this->state = $this->get('State');

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode("\n", $errors));
      return false;
    }

    // Include the component HTML helpers.
    JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');

    parent::display($tpl);
  }
}
