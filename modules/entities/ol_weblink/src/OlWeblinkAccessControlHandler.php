<?php

namespace Drupal\ol_weblink;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Weblink entity.
 *
 * @see \Drupal\ol_weblink\Entity\OlWeblink.
 */
class OlWeblinkAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_weblink\Entity\OlWeblinkInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol weblink entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol weblink entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol weblink entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol weblink entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol weblink entities');
  }


}
