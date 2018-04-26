<?php

namespace Drupal\user_list;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Class ListingService.
 */
class ListingService {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * List of all user excepted guest and admin.
   *
   * @var \Drupal\user\UserInterface[]|null
   */
  private $allUsers;

  /**
   * Array of all favored users.
   *
   * @var array|null
   */
  private $favoredUserIds;

  /**
   * Returns an array with user ids favored by current user.
   *
   * @return array
   *   The described user ids.
   */
  protected function getFavoredUserIds() {
    if (!$this->favoredUserIds) {
      /** @var \Drupal\user\UserInterface $cuser */
      $cuser = User::load($this->currentUser->id());
      $favs = $cuser->get('field_favorites')->getValue();
      $favoredUserIds = [];
      foreach ($favs as $fav) {
        $favoredUserIds[] = $fav['target_id'];
      }
      $this->favoredUserIds = $favoredUserIds;
    }
    return $this->favoredUserIds;
  }

  /**
   * Returns a complete unordered list of users.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|UserInterface[]
   *   The unordered user list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getAllUsers() {
    if (!$this->allUsers) {
      $profiles = $this->entityTypeManager
        ->getStorage('user')->loadMultiple();

      $favored = $this->getFavoredUserIds();

      $first = [];
      $last = [];
      foreach ($profiles as $profile) {
        if (in_array($profile->id(), $favored)) {
          $first[] = $profile;
        }
        else {
          $last[] = $profile;
        }
      }
      $this->allUsers = array_merge($first, $last);
    }
    return $this->allUsers;
  }

  /**
   * Constructs a new ListingService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Returns renderable Array with user listing.
   *
   * @return array
   *   The User listing sorted by Favorite.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getListing() {
    /** @var \Drupal\user\UserInterface[] $users */
    $users = [];
    foreach ($this->getAllUsers() as $user) {
      if ($user->id() > 1) {
        $name = $user->toLink()->toString();
        // Create Fav-Link.
        $fav_url = Url::fromRoute('user_list.ajax', ['uid' => $user->id()]);
        $fav_url->setOptions([
          'attributes' => [
            'class' => ['use-ajax', 'btn', 'btn--primary'],
            'rel' => ['.content'],
          ],
        ]);
        $fav = Link::fromTextAndUrl(t('Favorite'), $fav_url)->toString();
        $users[] = [
          '#markup' => $name . $fav,
          '#prefix' => '<div class="user-list--item">',
          '#suffix' => '</div>',
        ];
      }
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['user-list'],
      ],
      '#attached' => [
        'library' => ['user_list/listing'],
      ],
      'content' => $users,
    ];
  }

  /**
   * Toggles user between favorite and not-favorite.
   *
   * @param int $uid
   *   User id to toggle.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function toggleUser($uid) {
    /** @var \Drupal\user\UserInterface $cuser */
    $cuser = User::load($this->currentUser->id());
    $favs = $cuser->get('field_favorites')->getValue();

    $removed = FALSE;
    foreach ($favs as $key => $value) {
      if ($value['target_id'] == $uid) {
        unset($favs[$key]);
        $removed = TRUE;
      }
    }
    if (!$removed) {
      $favs[] = ['target_id' => $uid];
    }
    $cuser->set('field_favorites', $favs, FALSE);
    $cuser->save();
  }

}
