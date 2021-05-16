<?php

namespace Drupal\ol_text_docs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ol_text_docs\Services\OlTextDocs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

/**
 * The MainAjaxController .
 */
class TextDocsAjaxController extends ControllerBase {


  /**
   * @var $textdocs
   */
  protected $textdocs;


  /**
   * @var $config
   */
  protected $config;

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * {@inheritdoc}
   */
  public function __construct(OlTextdocs $textdocs) {
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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @param $uuid
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function saveOrder(Request $request, $uuid) {
    $ordered_items = $request->get('orderedItems');
    $success = $this->textdocs->updatePagePositions($ordered_items, $uuid);
    $response = ($success) ? 1: 0;
    return new Response($response);
  }

}
