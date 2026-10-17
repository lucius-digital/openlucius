<?php

namespace Drupal\ol_text_docs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ol_text_docs\Services\OlTextDocs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ajax controller for text document operations.
 */
class TextDocsAjaxController extends ControllerBase {

  /**
   * Textdocs service.
   *
   * @var \Drupal\ol_text_docs\Services\OlTextDocs
   */
  protected $textdocs;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * TextDocsAjaxController constructor.
   *
   * @param \Drupal\ol_text_docs\Services\OlTextDocs $textdocs
   *   The textdocs service.
   */
  public function __construct(OlTextDocs $textdocs) {
    $this->textdocs = $textdocs;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oltextdocs.textdocs')
    );
  }

  /**
   * Save the text document order via Ajax.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $uuid
   *   The group UUID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP response.
   */
  public function saveOrder(Request $request, $uuid) {
    $ordered_items = $request->get('orderedItems');
    $success = $this->textdocs->updatePagePositions($ordered_items, $uuid);
    $response = ($success) ? 1 : 0;
    return new Response($response);
  }

}
