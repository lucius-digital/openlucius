<?php

namespace Drupal\ol_stream_item;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Stream Item entity.
 *
 * @see \Drupal\ol_stream_item\Entity\OlStreamItem.
 */
class OlStreamItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_stream_item\Entity\OlStreamItemInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol stream item entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol stream item entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol stream item entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol stream item entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol stream item entities');
  }


}
