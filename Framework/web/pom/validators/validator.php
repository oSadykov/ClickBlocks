<?php
/**
 * ClickBlocks.PHP v. 1.0
 *
 * Copyright (C) 2014  SARITASA LLC
 * http://www.saritasa.com
 *
 * This framework is free software. You can redistribute it and/or modify
 * it under the terms of either the current ClickBlocks.PHP License
 * viewable at theclickblocks.com) or the License that was distributed with
 * this file.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the ClickBlocks.PHP License
 * along with this program.
 *
 * @copyright  2007-2014 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 */

namespace ClickBlocks\Web\POM;

use ClickBlocks\Core,
    ClickBlocks\MVC;

/**
 * The base class of all validator controls.
 * It contains the base attributes and method validate() that common for all validators.
 *
 * @version 1.0.0
 * @package cb.web.pom
 */
abstract class Validator extends Control
{
  // Error message templates.
  const ERR_VAL_1 = 'Control with ID = "[{var}]" is not found.';
  
  /**
   * Associative array of control validation statuses (valid/invalid => TRUE/FALSE).
   * The array keys are control unique identifiers and the array values are control validation statuses.
   *
   * @var array $result
   * @access protected
   */
  protected $result = [];
  
  /**
   * Non-standard control attributes, that should be rendered as "data-" attributes on the web page.
   *
   * @var array $dataAttributes
   * @access protected
   */
  protected $dataAttributes = ['controls' => 1, 'groups' => 1, 'mode' => 1, 'index' => 1, 'hiding' => 1, 'text' => 1, 'state' => 1, 'locked' => 1];
  
  /**
   * Constructor. Initializes the validator attributes.
   *
   * @param string $id - the logic identifier of the validator.
   * @access public
   */
  public function __construct($id)
  {
    parent::__construct($id);
    $this->attributes['index'] = 0;
    $this->attributes['mode'] = 'AND';
    $this->attributes['hiding'] = false;
    $this->attributes['locked'] = false;
    $this->attributes['controls'] = null;
    $this->attributes['groups'] = 'default';
    $this->attributes['text'] = null;
    $this->attributes['state'] = true;
    $this->properties['tag'] = 'div';
  }

  /**
   * Returns TRUE if the given value is valid and FALSE if it isn't.
   * This method is automatically invoked during the validation process.
   *
   * @param mixed $value - the value to validate.
   * @return boolean
   * @access public
   * @abstract
   */
  abstract public function check($value);

  /**
   * Returns array of the validator control identifiers.
   *
   * @return array
   * @access public
   */
  public function getControls()
  {
    if (!isset($this->attributes['controls'])) return [];
    return array_map('trim', explode(',', $this->attributes['controls']));
  }
  
  /**
   * Returns array of the validator groups.
   *
   * @return array
   * @access public
   */
  public function getGroups()
  {
    if (!isset($this->attributes['groups'])) return [];
    return array_map('trim', explode(',', $this->attributes['groups']));
  }
  
  /**
   * Returns the associative array of control validation statuses in which
   * its keys are control unique identifiers and its values are control validation statuses.
   *
   * @return array
   * @access public
   */
  public function getResult()
  {
    return $this->result;
  }
  
  /**
   * Sets result of validation of the validator controls.
   * This method should be used inside custom validation function to set the validation result of the validator controls.
   *
   * @param array $result - the associative array of control validation statuses.
   * @return self
   * @access public
   */
  public function setResult(array $result)
  {
    $this->result = $result;
    return $this;
  }
  
  /**
   * Locks the validator.
   * The locked validator does not participate in the control validation.
   *
   * @param boolean $flag - determines whether the validator will be locked or unlocked.
   * @return self
   * @access public
   */
  public function lock($flag = true)
  {
    $this->attributes['locked'] = (bool)$flag;
    return $this;
  }
  
  /**
   * Returns TRUE if the validator is locked and FALSE if it isn't.
   *
   * @return boolean
   * @access public
   */
  public function isLocked()
  {
    return !empty($this->attributes['locked']);
  }
  
  /**
   * Sets the validator status to valid.
   *
   * @return self
   * @access public
   */
  public function clean()
  {
    $this->attributes['state'] = true;
    return $this;
  }
  
  /**
   * Validates the validator controls according to the given mode (attribute "mode") and the validator type.
   * The method returns TRUE if all validator controls are valid and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function validate()
  {
    $this->result = [];
    $ids = $this->getControls(); 
    $len = count($ids);
    if ($len == 0) 
    {
      $this->attributes['state'] = true;
      return true;
    }
    $view = MVC\Page::$current->view;
    switch (isset($this->attributes['mode']) ? strtoupper($this->attributes['mode']) : 'AND')
    {
      default:
      case 'AND':
        $flag = true;
        for ($i = 0; $i < $len; $i++) 
        {
          $ctrl = $view->get($ids[$i]);
          if ($ctrl === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]); 
          if ($ctrl->validate($this)) $this->result[$ctrl->attr('id')] = true;
          else $this->result[$ctrl->attr('id')] = $flag = false;
        }
        break;
      case 'OR':
        $flag = false;
        for ($i = 0; $i < $len; $i++) 
        {
          $ctrl = $view->get($ids[$i]);
          if ($ctrl === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]); 
          if ($ctrl->validate($this)) $this->result[$ctrl->attr('id')] = $flag = true;
          else $this->result[$ctrl->attr('id')] = false;
        }
        break;
      case 'XOR':
        $n = 0;
        for ($i = 0; $i < $len; $i++)
        {
          $ctrl = $view->get($ids[$i]);
          if ($ctrl === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]); 
          if ($ctrl->validate($this))
          {
            $this->result[$ctrl->attr('id')] = $n < 1;
            $n++;
          }
          else $this->result[$ctrl->attr('id')] = false;
        }
        $flag = $n == 1;
        break;
    }
    return $this->attributes['state'] = $flag;
  }

  /**
   * Renders HTML of the validator.
   *
   * @return string
   * @access public
   */
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    $ids = $this->getControls();
    foreach ($ids as &$id) 
    {
      $ctrl = MVC\Page::$current->view->get($id);
      if ($ctrl) $id = $ctrl->attr('id');
    }
    $this->attributes['controls'] = implode(',', $ids);
    if (empty($this->attributes['hiding']))
    {
      return '<' . $this->properties['tag'] . $this->renderAttributes() . '>' . ($this->attributes['state'] ? '' : htmlspecialchars_decode($this->attributes['text'])) . '</' . $this->properties['tag'] . '>';
    }
    if (!empty($this->attributes['state'])) $this->addStyle('display', 'none');
    else $this->removeStyle('display');
    if (!isset($this->attributes['text'])) $this->attributes['text'] = '';
    return '<' . $this->properties['tag'] . $this->renderAttributes() . '>' . htmlspecialchars_decode($this->attributes['text']) . '</' . $this->properties['tag'] . '>';
  }
}