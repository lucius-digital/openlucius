<?php

namespace Drupal\ol_weblinks\Services;

use Drupal\ol_weblink\Entity\OlWeblink;

/**
 * Service for managing weblinks.
 */
class OlWeblinks {

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route;

  /**
   * OlWeblinks constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   *   The current route match service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   */
  public function __construct($route, $connection) {
    $this->route = $route;
    $this->database = $connection;
  }

  /**
   * Save a new weblink entity.
   *
   * @param array $params
   *   The weblink parameters.
   *
   * @return int
   *   The result of the entity save operation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveWeblink(array $params) {
    if ($params['weblink'] && $params['link_title']) {
      $entity = OlWeblink::create([
        'weblink' => $params['weblink'],
        'name' => $params['link_title'],
        'group_id' => $params['gid'],
        'entity_id' => $params['entity_id'],
        'entity_type' => $params['entity_type'],
      ]);
    }
    return $entity->save();
  }

  /**
   * Get weblinks based on filter parameters.
   *
   * @param array $params
   *   The filter parameters including gid, entity_id, and entity_type.
   *
   * @return array
   *   Array of weblink data objects.
   */
  public function getWeblinks(array $params) {
    $query = \Drupal::database()->select('ol_weblink', 'olw');
    $query->addField('olw', 'created');
    $query->addField('olw', 'id');
    $query->addField('olw', 'name');
    $query->addField('olw', 'weblink');
    $query->addField('olw', 'user_id');
    $query->addField('user', 'name', 'username');
    $query->condition('olw.group_id', $params['gid']);
    if ($params['entity_id']) {
      $query->condition('olw.entity_id', $params['entity_id']);
      $query->condition('olw.entity_type', $params['entity_type']);
    }
    $query->condition('olw.status', 1);
    $query->leftJoin('users_field_data', 'user', 'user.uid = olw.user_id');
    $query->orderBy('olw.name');
    return $query->execute()->fetchAll();
  }

  /**
   * Render weblinks to HTML.
   *
   * @param array $weblinks
   *   Array of weblink data objects.
   *
   * @return string
   *   Rendered HTML string for all weblinks.
   */
  public function renderWeblinks($weblinks) {
    // Initiate html.
    $weblinks_items_html = '';
    // Loop through array and render HTML rows via twig file.
    foreach ($weblinks as $weblink) {
      // Get board settings of group this weblink is in.
      $vars['id'] = $weblink->id;
      $vars['created'] = date('d-M-Y, H:i', $weblink->created);
      $username = ($weblink->username) ? $weblink->username : t('removed_user');
      $vars['username'] = $username;
      $vars['is_owner'] = $weblink->user_id == \Drupal::currentUser()->id();
      $vars['name'] = $weblink->name;
      $vars['weblink'] = $weblink->weblink;
      // Render.
      $render = ['#theme' => 'weblink_item', '#vars' => $vars];
      $weblinks_items_html .= \Drupal::service('renderer')->render($render);
    }
    // Add wrapper html.
    $vars['weblinks'] = $weblinks_items_html;
    // Render.
    $render_all = ['#theme' => 'weblinks_wrapper', '#vars' => $vars];
    // Render them to html.
    return \Drupal::service('renderer')->render($render_all);
  }

  /**
   * Delete a weblink by ID.
   *
   * @param int $ol_weblink_id
   *   The weblink entity ID.
   *
   * @return bool
   *   True if deleted successfully, false otherwise.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteWeblink($ol_weblink_id) {
    $weblink = OlWeblink::load($ol_weblink_id);
    $owner_uid = $weblink->getOwnerId();
    if ($owner_uid == \Drupal::currentUser()->id()) {
      $weblink->delete();
      return TRUE;
    }
    return FALSE;
  }

}
