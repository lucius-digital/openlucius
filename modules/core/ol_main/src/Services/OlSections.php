<?php

namespace Drupal\ol_main\Services;

use Drupal\Core\Site\Settings;

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
    if (!empty($enabled_sections)) {
      return explode(',', $enabled_sections);
    }
    return [];
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

  /**
   * @param $section_key
   * @param $default_title
   *
   * @return mixed
   */
  public function getSectionOverrideTitle($section_key, $default_title){
    $gid = \Drupal::service('current_route_match')->getParameter('gid');
    $section_overrides_json = $this->getSectionOverridesData($gid);
    if ($section_overrides_json) {
      $section_overrides = json_decode($section_overrides_json, TRUE);
    }
    if (!empty($section_overrides[$section_key])){
      return $section_overrides[$section_key];
    } else {
      return $default_title;
    }
  }

  /**
   * @param $sections
   * @return array
   */
  public function buildOptionsFromSections($sections){
    // Get global domain settings, if any.
    $enabled_sections = Settings::get('enabled_sections');
    // Build sections.
    $options = array();
    // Loop through all by-module-available sections.
    foreach ($sections as $section){
      // Set label and key.
      $label = (string) $section['label']; // Casting to string is needed here.
      $key = (string) $section['path'];
      // Only allow section, if domain settings are set -and allow it.
      if(!empty($enabled_sections)) {
        if (in_array($key, $enabled_sections)) {
          $options[$key] = $label;
        }
      }
      // Fallback settings: show all by-module-enabled sections.
      else {
        $options[$key] = $label;
      }
    }
    return $options;
  }

}
