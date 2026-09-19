<?php

namespace Drupal\ol_weblink\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Weblink entities.
 *
 * @ingroup ol_weblink
 */
interface OlWeblinkInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Weblink name.
   *
   * @return string
   *   Name of the OL Weblink.
   */
  public function getName();

  /**
   * Sets the OL Weblink name.
   *
   * @param string $name
   *   The OL Weblink name.
   *
   * @return \Drupal\ol_weblink\Entity\OlWeblinkInterface
   *   The called OL Weblink entity.
   */
  public function setName($name);

  /**
   * Gets the OL Weblink creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Weblink.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Weblink creation timestamp.
   *
   * @param int $timestamp
   *   The OL Weblink creation timestamp.
   *
   * @return \Drupal\ol_weblink\Entity\OlWeblinkInterface
   *   The called OL Weblink entity.
   */
  public function setCreatedTime($timestamp);

}
