<?php

namespace ClickBlocks\Web\POM;

use ClickBlocks\MVC;

class Slider extends Control
{
  protected $ctrl = 'slider';
  
  protected $dataAttributes = ['settings' => 1];

  public function __construct($id)
  {
    parent::__construct($id);
    $this->attributes['settings'] = [];
  }
  
  public function init()
  {
    $this->view->addCSS(['href' => \CB::url('framework-web') . '/js/jquery/nouislider/jquery.nouislider.css']);
    $this->view->addJS(['src' => \CB::url('framework-web') . '/js/jquery/nouislider/jquery.nouislider.min.js']);
    return $this;
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<div' . $this->renderAttributes() . '></div>';
  }
}