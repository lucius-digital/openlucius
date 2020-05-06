<?php

namespace Drupal\ol_main\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Checks access for group admin routes
 * There is also a method isGroupAdmin() that's used in code, this is for route protection.
 */
class GroupAdminAccessChecker implements AccessInterface {

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
    return ($account->hasPermission('access ol content') && $this->checkIfGroupAdmin($account, $gid)) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Check if current user can access group, based on group uuid, so group id can not be guessed.
   *
   * @param $account
   * @param $gid
   * @return bool
   */
  private function checkIfGroupAdmin($account, $gid){
    // Query
    $query = \Drupal::database()->select('ol_group', 'og');
    $query->addField('og', 'id');
    $query->condition('og.user_id', $account->id());
    $query->condition('og.id', $gid);
    $group_id = $query->execute()->fetchField();
    // Return true if current user is in group.
    return (is_numeric($group_id) && !empty($group_id)) ? TRUE : FALSE ;
  }

}
