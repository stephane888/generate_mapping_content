<?php

namespace Drupal\generate_mapping_content;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface ContentGenerateEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Contenu generer pour le referencement revision IDs for a specific Contenu generer pour le referencement.
   *
   * @param \Drupal\generate_mapping_content\Entity\ContentGenerateEntityInterface $entity
   *   The Contenu generer pour le referencement entity.
   *
   * @return int[]
   *   Contenu generer pour le referencement revision IDs (in ascending order).
   */
  public function revisionIds(ContentGenerateEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Contenu generer pour le referencement author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Contenu generer pour le referencement revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\generate_mapping_content\Entity\ContentGenerateEntityInterface $entity
   *   The Contenu generer pour le referencement entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ContentGenerateEntityInterface $entity);

  /**
   * Unsets the language for all Contenu generer pour le referencement with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
