<?php

namespace Drupal\generate_mapping_content;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Contenu generer pour le referencement entity.
 *
 * @see \Drupal\generate_mapping_content\Entity\ContentGenerateEntity.
 */
class ContentGenerateEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\generate_mapping_content\Entity\ContentGenerateEntityInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished contenu generer pour le referencement entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published contenu generer pour le referencement entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit contenu generer pour le referencement entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete contenu generer pour le referencement entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add contenu generer pour le referencement entities');
  }


}
