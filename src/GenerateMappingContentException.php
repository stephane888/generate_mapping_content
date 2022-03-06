<?php

namespace Drupal\generate_mapping_content;

use LogicException;

class GenerateMappingContentException extends LogicException implements \Throwable {
  protected $content;
  
  function __construct($message = null, $code = null, $previous = null, $content = null) {
    $this->content = $content;
    parent::__construct($message, $code, $previous);
  }
  
  function getContentToDebug() {
    return $this->content;
  }
  
  function setContentToDebug($val) {
    $this->content = $val;
  }
  
  function getError() {
    return $this->getMessage();
  }
  
  function getErrorCode() {
    if (empty($this->getCode()))
      return 431;
    return $this->getCode();
  }
  
}


