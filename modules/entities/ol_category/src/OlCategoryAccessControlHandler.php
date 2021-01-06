<?php

namespace Drupal\ol_category;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Category entity.
 *
 * @see \Drupal\ol_category\Entity\OlCategory.
 */
class OlCategoryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_category\Entity\OlCategoryInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol category entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol category entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol category entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol category entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol category entities');
  }


}
