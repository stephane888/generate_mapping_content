<?php

namespace Drupal\generate_mapping_content;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\generate_mapping_content\Entity\ContentGenerateEntityInterface;

/**
 * Defines the storage handler class for Contenu generer pour le referencement entities.
 *
 * This extends the base storage class, adding required special handling for
 * Contenu generer pour le referencement entities.
 *
 * @ingroup generate_mapping_content
 */
class ContentGenerateEntityStorage extends SqlContentEntityStorage implements ContentGenerateEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ContentGenerateEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {content_generate_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {content_generate_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ContentGenerateEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {content_generate_entity_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('content_generate_entity_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
