<?php

namespace Drupal\ol_stream\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Checks access for displaying configuration translation page.
 */
class UserProfileAccessChecker implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @param $uid
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, $uid) {
    // Check permissions and combine that with any custom access checking needed. Pass forward
    // parameters from the route and/or request as needed.
    return ($account->hasPermission('access ol content')
      && $this->checkUserProfileAccess($account, $uid)) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Check if current user can access group, based on group uuid, so group id can not be guessed.
   *
   * @param $account
   * @param $uid
   * @return bool
   */
  private function checkUserProfileAccess($account, $uid){

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
