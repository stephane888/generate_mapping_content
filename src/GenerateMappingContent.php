<?php

namespace Drupal\generate_mapping_content;

class GenerateMappingContent {
  
  public static function listMappings() {
    $options = [];
    $storageMapping = \Drupal::entityTypeManager()->getStorage('mappings_entity');
    if ($storageMapping) {
      $mappings = $storageMapping->loadMultiple();
      foreach ($mappings as $mapping) {
        $options[$mapping->id()] = $mapping->label();
      }
    }
    return $options;
  }
  
}