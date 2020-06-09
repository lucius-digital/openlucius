<?php

namespace Drupal\ol_text_doc;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OL Text Doc entity.
 *
 * @see \Drupal\ol_text_doc\Entity\OlTextDoc.
 */
class OlTextDocAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ol_text_doc\Entity\OlTextDocInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ol text doc entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ol text doc entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ol text doc entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ol text doc entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ol text doc entities');
  }


}
