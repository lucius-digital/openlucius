<?php

namespace Drupal\ol_main\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OLMainSubscriber implements EventSubscriberInterface {

  /**
   * OLMainSubscriber constructor.
   */
  public function __construct() {
    $this->account = \Drupal::currentUser();
  }

  /**
   * Makes sure no anonymous users can not enter.
   * @param RequestEvent $event
   */
  public function checkAnonymous(RequestEvent $event) {

    // Get current request.
    $request = $event->getRequest();
    // Get current path.
    $current_path = $request->getPathInfo();
    // Check if this is /user/rest path.
    $user_reset = fnmatch('/user/reset/*', $current_path);

    if($this->account->isAnonymous()
      && $current_path != '/user/login'
      && $current_path != '/user/password'
      && $current_path != '/register'
      && $user_reset != true
    ){
      $event->setResponse(new RedirectResponse('/user/login', 301));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkAnonymous', 100];
    return $events;
  }

}
