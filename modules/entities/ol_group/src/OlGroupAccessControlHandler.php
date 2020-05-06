<?php

namespace Drupal\ol_group;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Group entity.
 *
 * @see \Drupal\ol_group\Entity\OlGroup.
 */
class OlGroupAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_group\Entity\OlGroupInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol group entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol group entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol group entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol group entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol group entities');
  }


}
