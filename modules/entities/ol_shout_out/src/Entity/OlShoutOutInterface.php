<?php

namespace Drupal\ol_shout_out\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Shout Out entities.
 *
 * @ingroup ol_shout_out
 */
interface OlShoutOutInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Shout Out name.
   *
   * @return string
   *   Name of the OL Shout Out.
   */
  public function getName();

  /**
   * Sets the OL Shout Out name.
   *
   * @param string $name
   *   The OL Shout Out name.
   *
   * @return \Drupal\ol_shout_out\Entity\OlShoutOutInterface
   *   The called OL Shout Out entity.
   */
  public function setName($name);

  /**
   * Gets the OL Shout Out creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Shout Out.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Shout Out creation timestamp.
   *
   * @param int $timestamp
   *   The OL Shout Out creation timestamp.
   *
   * @return \Drupal\ol_shout_out\Entity\OlShoutOutInterface
   *   The called OL Shout Out entity.
   */
  public function setCreatedTime($timestamp);

}
