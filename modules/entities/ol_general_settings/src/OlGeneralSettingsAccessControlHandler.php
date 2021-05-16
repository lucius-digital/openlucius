<?php

namespace Drupal\ol_general_settings;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ol general settings entity.
 *
 * @see \Drupal\ol_general_settings\Entity\OlGeneralSettings.
 */
class OlGeneralSettingsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_general_settings\Entity\OlGeneralSettingsInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol general settings entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol general settings entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol general settings entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol general settings entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol general settings entities');
  }


}
