<?php

namespace Drupal\user_list\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user_list\ListingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UserListController.
 */
class UserListController extends ControllerBase {

  /**
   * Drupal\user_list\ListingService definition.
   *
   * @var \Drupal\user_list\ListingService
   */
  protected $listingService;

  /**
   * Constructs a new UserListController object.
   */
  public function __construct(ListingService $listing_service) {
    $this->listingService = $listing_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user_list.listing')
    );
  }

  /**
   * Original listing.
   *
   * @return array
   *   Return render array with list of users.
   */
  public function listing() {
    return $this->listingService->getListing();
  }

  /**
   * The ajax call.
   *
   * @param int $uid
   *   The user id to favor or not.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Return render array with list of users.
   */
  public function ajax($uid) {
    $this->listingService->toggleUser($uid);
    $response = new AjaxResponse();

    $response->addCommand(new ReplaceCommand(
      '.user-list',
      $this->listingService->getListing()
    ));

    return $response;
  }

}
