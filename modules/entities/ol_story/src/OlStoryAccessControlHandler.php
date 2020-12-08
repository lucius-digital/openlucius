<?php

namespace Drupal\ol_story;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Story entity.
 *
 * @see \Drupal\ol_story\Entity\OlStory.
 */
class OlStoryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_story\Entity\OlStoryInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol story entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol story entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol story entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol story entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol story entities');
  }


}
