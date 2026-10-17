<?php

namespace Drupal\ol_main\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for group admin routes.
 *
 * There is also a method isGroupAdmin() that is used in code;
 * this is for route protection.
 */
class GroupAdminAccessChecker implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $gid) {
    // Check permissions and combine with custom access checking.
    return ($account->hasPermission('access ol content')
      && $this->checkIfGroupAdmin($account, $gid))
      ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Check if user is an admin of the group by ID to prevent guessing.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   * @param int $gid
   *   The group ID.
   *
   * @return bool
   *   TRUE if the user is a group admin, FALSE otherwise.
   */
  private function checkIfGroupAdmin($account, $gid) {
    // Query.
    $query = \Drupal::database()->select('ol_group', 'og');
    $query->addField('og', 'id');
    $query->condition('og.user_id', $account->id());
    $query->condition('og.id', $gid);
    $group_id = $query->execute()->fetchField();
    // Return true if current user is in group.
    return (is_numeric($group_id) && !empty($group_id)) ? TRUE : FALSE;
  }

}
