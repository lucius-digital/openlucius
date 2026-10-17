<?php

namespace Drupal\ol_stream\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for displaying configuration translation page.
 */
class UserProfileAccessChecker implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param int $uid
   *   The user ID to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, $uid) {
    // Check permissions and combine with custom access checking.
    // Pass forward parameters from the route as needed.
    return ($account->hasPermission('access ol content')
      && $this->checkUserProfileAccess($account, $uid)) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Check if current user can access user profile based on group membership.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check.
   * @param int $uid
   *   The user ID to check.
   *
   * @return bool
   *   TRUE if access is granted.
   */
  private function checkUserProfileAccess($account, $uid) {

    $stream = \Drupal::service('olstream.stream');
    $current_user_gids = $stream->getUserGroups();
    // Query.
    $query = \Drupal::database()->select('ol_group_user', 'ogu');
    $query->addField('ogu', 'id');
    $query->condition('ogu.member_uid', $uid);
    $query->condition('ogu.group_id', $current_user_gids, 'IN');
    $group_id = $query->execute()->fetchField();
    // Return true if current user is in group.
    return is_numeric($group_id) && !empty($group_id);
  }

}
