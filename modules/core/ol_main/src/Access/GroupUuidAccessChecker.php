<?php

namespace Drupal\ol_main\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for displaying configuration translation page.
 */
class GroupUuidAccessChecker implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $uuid) {
    // Check permissions and combine with custom access checking.
    return ($account->hasPermission('access ol content')
      && $this->checkGroupAccess($account, $uuid))
      ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Check if user can access group by UUID to prevent guessing.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   * @param string $uuid
   *   The group UUID.
   *
   * @return bool
   *   TRUE if the user has access, FALSE otherwise.
   */
  private function checkGroupAccess($account, $uuid) {

    // Query.
    $query = \Drupal::database()->select('ol_group_user', 'ogu');
    $query->addField('ogu', 'id');
    $query->condition('ogu.member_uid', $account->id());
    $query->condition('olg.uuid', $uuid);
    $query->join('ol_group', 'olg', 'olg.id = ogu.group_id');
    $group_id = $query->execute()->fetchField();

    // Return true if current user is in group.
    return (is_numeric($group_id) && !empty($group_id)) ? TRUE : FALSE;
  }

}
