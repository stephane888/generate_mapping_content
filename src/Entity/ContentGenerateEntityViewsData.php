<?php

namespace Drupal\generate_mapping_content\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Contenu generer pour le referencement entities.
 */
class ContentGenerateEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
