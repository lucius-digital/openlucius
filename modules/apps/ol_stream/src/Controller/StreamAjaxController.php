<?php

namespace Drupal\ol_stream\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Symfony\Component\HttpFoundation\Response;
use Drupal\ol_stream\Services\OlStream;

/**
 * An example controller.
 */
class StreamAjaxController extends ControllerBase {

  /**
   * @var $renderer
   */
  protected $stream;

  /**
   * {@inheritdoc}
   */
  public function __construct(OlStream $stream) {
    $this->stream = $stream;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olstream.stream')
    );
  }

  /**
   * @param $uuid
   * @return Response
   * @throws \Exception
   */
  public function getStreamItems($uuid) {
    // Get stream items html via stream service.
    $stream_data = $this->stream->getStreamList($uuid);
    if(empty($stream_data)){
      return new Response(t('Be the first one to add a chat-message here!'));
    }
    $stream_html = $this->stream->renderStreamList($stream_data);
    return new Response($stream_html);
  }

  public function getLastMessageTimestamp($uuid){
    $timestamp = $this->stream->getLastMessageTimestamp($uuid);
    return new Response($timestamp);
  }
}
