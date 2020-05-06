<?php

namespace Drupal\ol_file;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ol file entity.
 *
 * @see \Drupal\ol_file\Entity\OlFile.
 */
class OlFileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_file\Entity\OlFileInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol file entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol file entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol file entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol file entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol file entities');
  }


}
