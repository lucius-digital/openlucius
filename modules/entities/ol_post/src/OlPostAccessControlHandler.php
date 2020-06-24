<?php

namespace Drupal\ol_post;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Post entity.
 *
 * @see \Drupal\ol_post\Entity\OlPost.
 */
class OlPostAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_post\Entity\OlPostInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol post entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol post entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol post entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol post entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol post entities');
  }


}
