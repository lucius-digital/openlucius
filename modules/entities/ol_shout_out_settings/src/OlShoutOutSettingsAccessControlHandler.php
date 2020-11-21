<?php

namespace Drupal\ol_shout_out_settings;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ol shout out settings entity.
 *
 * @see \Drupal\ol_shout_out_settings\Entity\OlShoutOutSettings.
 */
class OlShoutOutSettingsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_shout_out_settings\Entity\OlShoutOutSettingsInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol shout out settings entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol shout out settings entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol shout out settings entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol shout out settings entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol shout out settings entities');
  }


}
