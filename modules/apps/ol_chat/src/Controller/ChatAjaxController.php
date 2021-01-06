<?php

namespace Drupal\ol_chat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Symfony\Component\HttpFoundation\Response;
use Drupal\ol_chat\Services\OlChat;

/**
 * An example controller.
 */
class ChatAjaxController extends ControllerBase {

  /**
   * @var $renderer
   */
  protected $chat;

  /**
   * {@inheritdoc}
   */
  public function __construct(OlChat $chat) {
    $this->chat = $chat;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olchat.chat')
    );
  }

  /**
   * @param $uuid
   * @return Response
   * @throws \Exception
   */
  public function getChatItems($uuid) {
    // Get chat items html via chat service.
    $chat_data = $this->chat->getChatList($uuid);
    if(empty($chat_data)){
      return new Response(t('Be the first one to chat here!'));
    }
    $chat_html = $this->chat->renderChatList($chat_data);
    return new Response($chat_html);
  }

  /**
   * @param $uuid
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getLastMessageTimestamp($uuid){
    $timestamp = $this->chat->getLastMessageTimestamp($uuid);
    return new Response($timestamp);
  }
}
