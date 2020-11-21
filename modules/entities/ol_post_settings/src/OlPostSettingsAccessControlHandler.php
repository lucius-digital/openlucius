<?php

namespace Drupal\ol_post_settings;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ol post settings entity.
 *
 * @see \Drupal\ol_post_settings\Entity\OlPostSettings.
 */
class OlPostSettingsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_post_settings\Entity\OlPostSettingsInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol post settings entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol post settings entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol post settings entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol post settings entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol post settings entities');
  }


}
