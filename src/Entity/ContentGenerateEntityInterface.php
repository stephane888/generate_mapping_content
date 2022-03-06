<?php

namespace Drupal\generate_mapping_content\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Contenu generer pour le referencement entities.
 *
 * @ingroup generate_mapping_content
 */
interface ContentGenerateEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Contenu generer pour le referencement name.
   *
   * @return string
   *   Name of the Contenu generer pour le referencement.
   */
  public function getName();

  /**
   * Sets the Contenu generer pour le referencement name.
   *
   * @param string $name
   *   The Contenu generer pour le referencement name.
   *
   * @return \Drupal\generate_mapping_content\Entity\ContentGenerateEntityInterface
   *   The called Contenu generer pour le referencement entity.
   */
  public function setName($name);

  /**
   * Gets the Contenu generer pour le referencement creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Contenu generer pour le referencement.
   */
  public function getCreatedTime();

  /**
   * Sets the Contenu generer pour le referencement creation timestamp.
   *
   * @param int $timestamp
   *   The Contenu generer pour le referencement creation timestamp.
   *
   * @return \Drupal\generate_mapping_content\Entity\ContentGenerateEntityInterface
   *   The called Contenu generer pour le referencement entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Contenu generer pour le referencement revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Contenu generer pour le referencement revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\generate_mapping_content\Entity\ContentGenerateEntityInterface
   *   The called Contenu generer pour le referencement entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Contenu generer pour le referencement revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Contenu generer pour le referencement revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\generate_mapping_content\Entity\ContentGenerateEntityInterface
   *   The called Contenu generer pour le referencement entity.
   */
  public function setRevisionUserId($uid);

}
