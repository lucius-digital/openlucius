<?php

namespace Drupal\ol_chat_item;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Chat Item entity.
 *
 * @see \Drupal\ol_chat_item\Entity\OlChatItem.
 */
class OlChatItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_chat_item\Entity\OlChatItemInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol chat item entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol chat item entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol chat item entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol chat item entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol chat item entities');
  }


}
