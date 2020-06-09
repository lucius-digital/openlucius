<?php

namespace Drupal\ol_text_doc\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Text Doc entities.
 *
 * @ingroup ol_text_doc
 */
interface OlTextDocInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Text Doc name.
   *
   * @return string
   *   Name of the OL Text Doc.
   */
  public function getName();

  /**
   * Sets the OL Text Doc name.
   *
   * @param string $name
   *   The OL Text Doc name.
   *
   * @return \Drupal\ol_text_doc\Entity\OlTextDocInterface
   *   The called OL Text Doc entity.
   */
  public function setName($name);

  /**
   * Gets the OL Text Doc creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Text Doc.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Text Doc creation timestamp.
   *
   * @param int $timestamp
   *   The OL Text Doc creation timestamp.
   *
   * @return \Drupal\ol_text_doc\Entity\OlTextDocInterface
   *   The called OL Text Doc entity.
   */
  public function setCreatedTime($timestamp);

}
