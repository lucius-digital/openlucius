<?php

namespace Drupal\ol_icebreaker_settings;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ol icebreaker settings entity.
 *
 * @see \Drupal\ol_icebreaker_settings\Entity\OlIcebreakerSettings.
 */
class OlIcebreakerSettingsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_icebreaker_settings\Entity\OlIcebreakerSettingsInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol icebreaker settings entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol icebreaker settings entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol icebreaker settings entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol icebreaker settings entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol icebreaker settings entities');
  }


}
