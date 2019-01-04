<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tags
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * The Tag Controller
 *
 * @since  3.1
 */
class TagsControllerTag extends JControllerForm
{
	/**
	 * Method to check if you can add a new record.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   3.1
	 */
	protected function allowAdd($data = array())
	{
		$user = JFactory::getUser();

		return $user->authorise('core.create', 'com_tags');
	}

	/**
	 * Method to check if you can edit a record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   3.1
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		// Since there is no asset tracking and no categories, revert to the component permissions.
		return parent::allowEdit($data, $key);
	}

	/**
	 * Method to run batch operations.
	 *
	 * @param   object  $model  The model.
	 *
	 * @return  boolean	 True if successful, false otherwise and internal error is set.
	 *
	 * @since   3.1
	 */
	public function batch($model = null)
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Set the model
		$model = $this->getModel('Tag');

		// Preset the redirect
		$this->setRedirect('index.php?option=com_tags&view=tags');

		return parent::batch($model);
	}

	/**
	 * Method to save a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   1.6
	 */
	public function save($key = null, $urlVar = null)
	{
	  /** - KetShop Override - **/

	  JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
	  //Get the jform data.
	  $data = $this->input->post->get('jform', array(), 'array');
	  //Includes the Ketshop helper class.
	  JLoader::register('KetshopHelper', JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/ketshop.php');

	  if((int)$data['id'] && ($data['published'] == 2 || $data['published'] == -2) &&
	     !KetshopHelper::checkMainTags(array($data['id']))) {
	    $this->setRedirect(JRoute::_('index.php?option=com_tags&view=tag'.$this->getRedirectToItemAppend($data['id'],'id'),false));
	    return false;
	  }

	  return parent::save($key = null, $urlVar = null);
	}
}
