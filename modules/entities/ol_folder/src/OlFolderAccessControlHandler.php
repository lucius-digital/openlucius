<?php

namespace Drupal\ol_folder;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Folder entity.
 *
 * @see \Drupal\ol_folder\Entity\OlFolder.
 */
class OlFolderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_folder\Entity\OlFolderInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol folder entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol folder entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol folder entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol folder entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol folder entities');
  }


}
