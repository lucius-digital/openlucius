<?php

namespace Drupal\ol_icebreaker;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Icebreaker entity.
 *
 * @see \Drupal\ol_icebreaker\Entity\OlIcebreaker.
 */
class OlIcebreakerAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_icebreaker\Entity\OlIcebreakerInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol icebreaker entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol icebreaker entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol icebreaker entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol icebreaker entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol icebreaker entities');
  }


}
