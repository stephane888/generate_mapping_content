<?php

namespace Drupal\generate_mapping_content;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Contenu generer pour le referencement entities.
 *
 * @ingroup generate_mapping_content
 */
class ContentGenerateEntityListBuilder extends EntityListBuilder {
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Contenu generer pour le referencement ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\generate_mapping_content\Entity\ContentGenerateEntity $entity */
    $row['id'] = Link::createFromRoute('Voir : ' . $entity->id(), 'entity.content_generate_entity.canonical', [
      'content_generate_entity' => $entity->id()
    ]);
    $row['name'] = Link::createFromRoute($entity->label(), 'entity.content_generate_entity.edit_form', [
      'content_generate_entity' => $entity->id()
    ]);
    return $row + parent::buildRow($entity);
  }
  
}
