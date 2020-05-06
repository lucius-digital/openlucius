<?php

namespace Drupal\ol_message;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Message entity.
 *
 * @see \Drupal\ol_message\Entity\OlMessage.
 */
class OlMessageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_message\Entity\OlMessageInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol message entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol message entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol message entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol message entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol message entities');
  }


}
