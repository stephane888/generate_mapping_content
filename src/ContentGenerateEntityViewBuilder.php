<?php

namespace Drupal\generate_mapping_content;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;

class ContentGenerateEntityViewBuilder extends EntityViewBuilder {
  
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $render = parent::view($entity);
    return $render;
  }
  
}