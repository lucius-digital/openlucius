<?php

namespace Drupal\ol_main\Services;

/**
 * Class OlSections.
 */
class OlSections{

  /**
   * @return array
   */
  public function getSectionsData() {
    $sections = array();
    \Drupal::moduleHandler()->invokeAll('add_ol_section', [&$sections]);
    usort($sections, 'sortByWeight');
    return $sections;
  }

  /**
   * @return array
   */
  public function getGlobalMenuData() {
    $sections = array();
    \Drupal::moduleHandler()->invokeAll('add_global_menu_item', [&$sections]);
    usort($sections, 'sortByWeight');
    return $sections;
  }

  /**
   * @param $gid
   *
   * @return false|array
   */
  public function getEnabledSections($gid){
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'enabled_sections');
    $query->condition('gr.id', $gid);
    $enabled_sections = $query->execute()->fetchField();
    return explode(',',$enabled_sections);
  }

}
