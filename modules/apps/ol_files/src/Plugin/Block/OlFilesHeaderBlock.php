<?php

namespace Drupal\ol_files\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'OlFilesHeaderBlock' block.
 *
 * @Block(
 *  id = "ol_files_header_block",
 *  admin_label = @Translation("Ol files header block"),
 * )
 */
class OlFilesHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Initialize.
    $build = array();
    $id_group = getActiveGroup();
    $navigation_form = \Drupal::formBuilder()->getForm(\Drupal\ol_files\Form\FilesNavigationForm::class, $id_group);
    $count_groups = countGroups();
    // Facilitate external users.
    $user = \Drupal::currentUser();
    $org_member = $user->hasPermission('access organization content');
    if(!$org_member && $id_group == 0){
      return $build;
    }

    // Build.
    $theme_vars = [
      'navigation_form' => $navigation_form,
      'group_id' => $id_group,
      'count_groups' => $count_groups,
      'org_member' => $org_member,
    ];
    $build = [
      '#theme' => 'ol_files_header_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }

}
