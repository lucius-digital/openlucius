<?php

namespace Drupal\ol_group_user;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Group User entity.
 *
 * @see \Drupal\ol_group_user\Entity\OlGroupUser.
 */
class OlGroupUserAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_group_user\Entity\OlGroupUserInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol group user entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol group user entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol group user entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol group user entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol group user entities');
  }


}
