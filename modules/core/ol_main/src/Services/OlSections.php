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
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'enabled_sections');
    $query->condition('gr.id', $gid);
    $enabled_sections = $query->execute()->fetchField();
    return explode(',',$enabled_sections);
  }

  /**
   * @param $gid
   *
   * @return mixed
   */
  public function getSectionOverridesData($gid){
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'section_overrides');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

  public function getSectionOverrideTitle($section_key, $default_title){
    $gid = \Drupal::service('current_route_match')->getParameter('gid');
    $section_overrides_json = $this->getSectionOverridesData($gid);
    $section_overrides = json_decode($section_overrides_json, true);
    if(!empty($section_overrides[$section_key])){
      return $section_overrides[$section_key];
    } else {
      return $default_title;
    }
  }

}
