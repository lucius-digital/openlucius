<?php

namespace Drupal\ol_social_question_settings;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ol social question settings entity.
 *
 * @see \Drupal\ol_social_question_settings\Entity\OlSocialQuestionSettings.
 */
class OlSocialQuestionSettingsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_social_question_settings\Entity\OlSocialQuestionSettingsInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol social question settings entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol social question settings entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol social question settings entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol social question settings entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol social question settings entities');
  }


}
