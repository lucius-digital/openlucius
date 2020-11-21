<?php

namespace Drupal\ol_shout_out_settings\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ol shout out settings entities.
 *
 * @ingroup ol_shout_out_settings
 */
interface OlShoutOutSettingsInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ol shout out settings name.
   *
   * @return string
   *   Name of the Ol shout out settings.
   */
  public function getName();

  /**
   * Sets the Ol shout out settings name.
   *
   * @param string $name
   *   The Ol shout out settings name.
   *
   * @return \Drupal\ol_shout_out_settings\Entity\OlShoutOutSettingsInterface
   *   The called Ol shout out settings entity.
   */
  public function setName($name);

  /**
   * Gets the Ol shout out settings creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ol shout out settings.
   */
  public function getCreatedTime();

  /**
   * Sets the Ol shout out settings creation timestamp.
   *
   * @param int $timestamp
   *   The Ol shout out settings creation timestamp.
   *
   * @return \Drupal\ol_shout_out_settings\Entity\OlShoutOutSettingsInterface
   *   The called Ol shout out settings entity.
   */
  public function setCreatedTime($timestamp);

}
