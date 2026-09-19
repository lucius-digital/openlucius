<?php

namespace Drupal\ol_task;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ol task entity.
 *
 * @see \Drupal\ol_task\Entity\OlTask.
 */
class OlTaskAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_task\Entity\OlTaskInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol task entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol task entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol task entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol task entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol task entities');
  }


}
