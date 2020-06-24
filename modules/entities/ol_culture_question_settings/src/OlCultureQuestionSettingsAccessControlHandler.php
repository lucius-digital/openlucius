<?php

namespace Drupal\ol_culture_question_settings;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ol culture question settings entity.
 *
 * @see \Drupal\ol_culture_question_settings\Entity\OlCultureQuestionSettings.
 */
class OlCultureQuestionSettingsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_culture_question_settings\Entity\OlCultureQuestionSettingsInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol culture question settings entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol culture question settings entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol culture question settings entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol culture question settings entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol culture question settings entities');
  }


}
