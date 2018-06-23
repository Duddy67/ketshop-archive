<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * JSON Tag View class. Mainly used for Ajax request. 
 */
class KetshopViewTag extends JViewLegacy
{
  public function display($tpl = null)
  {
    $jinput = JFactory::getApplication()->input;
    $search = $jinput->get('search', '', 'str');

    // Get some data from the models
    $model = $this->getModel();
    $results = $model->getAutocompleteSuggestions($search);

    echo new JResponseJson($results);
  }
}

