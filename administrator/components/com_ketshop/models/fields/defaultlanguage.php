<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');


//Script which build the input html tag containing the default admin language.

class JFormFieldDefaultLanguage extends JFormField
{
  protected $type = 'defaultlanguage';

  protected function getInput()
  {
    //Get the default language code of the admin.
    $params = JComponentHelper::getParams('com_languages');
    $langTag = $params->get('administrator');

    //Get the xml file path then parse it to get the language name.
    $file = JPATH_BASE.'/language'.'/'.$langTag.'/'.$langTag.'.xml';
    $info = JApplicationHelper::parseXMLLangMetaFile($file);
    $langName = $info['name'];

    //In case the xml parse has failed we display the language code.
    if(empty($langName)) {
      $value = $langTag;
    }
    else {
      $value = $langName;
    }

    return '<input name="default_language" readonly="true" class="readonly" type="text" value="'.$value.'" >';
  }
}



