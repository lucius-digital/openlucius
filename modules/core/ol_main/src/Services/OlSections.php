<?php

namespace Drupal\ol_main\Services;

use Drupal\Core\Site\Settings;

/**
 * Service for managing section operations.
 */
class OlSections {

  /**
   * Get all sections data from modules.
   *
   * @return array
   *   Array of section data.
   */
  public function getSectionsData() {
    $sections = [];
    \Drupal::moduleHandler()->invokeAll('add_ol_section', [&$sections]);
    usort($sections, 'sortByWeight');
    return $sections;
  }

  /**
   * Get global menu items from modules.
   *
   * @return array
   *   Array of global menu items.
   */
  public function getGlobalMenuData() {
    $sections = [];
    \Drupal::moduleHandler()->invokeAll('add_global_menu_item', [&$sections]);
    usort($sections, 'sortByWeight');
    return $sections;
  }

  /**
   * Get enabled sections for a group.
   *
   * @param int $gid
   *   The group ID.
   *
   * @return array|false
   *   Array of enabled section keys.
   */
  public function getEnabledSections($gid) {
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
   * Get section overrides data for a group.
   *
   * @param int $gid
   *   The group ID.
   *
   * @return string
   *   JSON encoded section overrides.
   */
  public function getSectionOverridesData($gid) {
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'section_overrides');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

  /**
   * Get the override title for a section, or the default.
   *
   * @param string $section_key
   *   The section key.
   * @param string $default_title
   *   The default title if no override exists.
   *
   * @return string
   *   The section title.
   */
  public function getSectionOverrideTitle($section_key, $default_title) {
    $gid = \Drupal::service('current_route_match')->getParameter('gid');
    $section_overrides_json = $this->getSectionOverridesData($gid);
    if ($section_overrides_json) {
      $section_overrides = json_decode($section_overrides_json, TRUE);
    }
    if (!empty($section_overrides[$section_key])) {
      return $section_overrides[$section_key];
    }
    else {
      return $default_title;
    }
  }

  /**
   * Build an options array from sections data.
   *
   * @param array $sections
   *   Array of section data.
   *
   * @return array
   *   Associative array of section keys to labels.
   */
  public function buildOptionsFromSections($sections) {
    // Get global domain settings, if any.
    $enabled_sections = Settings::get('enabled_sections');
    // Build sections.
    $options = [];
    // Loop through all by-module-available sections.
    foreach ($sections as $section) {
      // Set label and key.
      // Casting to string is needed here.
      $label = (string) $section['label'];
      $key = (string) $section['path'];
      // Only allow section, if domain settings are set -and allow it.
      if (!empty($enabled_sections)) {
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
