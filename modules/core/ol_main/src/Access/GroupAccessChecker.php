<?php

namespace Drupal\ol_main\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Checks access for displaying configuration translation page.
 */
class GroupAccessChecker implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @param $gid
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, $gid) {
    // Check permissions and combine that with any custom access checking needed. Pass forward
    // parameters from the route and/or request as needed.
    return ($account->hasPermission('access ol content') && $this->checkGroupAccess($account, $gid)) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Check if current user can access group, based on group uuid, so group id can not be guessed.
   *
   * @param $account
   * @param $gid
   * @return bool
   */
  private function checkGroupAccess($account, $gid){
    // Query.
    $query = \Drupal::database()->select('ol_group_user', 'ogu');
    $query->addField('ogu', 'id');
    $query->condition('ogu.member_uid', $account->id());
    $query->condition('olg.id', $gid);
    $query->join('ol_group', 'olg', 'olg.id = ogu.group_id');
    $group_id = $query->execute()->fetchField();
    // Return true if current user is in group.
    return (is_numeric($group_id) && !empty($group_id)) ? TRUE : FALSE ;
  }

}
