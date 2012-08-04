<?php
class UserController extends ApplicationController
{
  public function showLoginPage()
  {
    $this->render('user/login');
  }
  
  /**
   * @see http://httpd.apache.org/docs/2.2/misc/password_encryptions.html
   */
  private static function hashPassword($password)
  {
    return '{SHA}' . base64_encode(sha1($password, TRUE));
  }
  
  public function login()
  {
    $username = trim(fRequest::get('username', 'string'));
    $password = fRequest::get('password', 'string');
    $password_hash = static::hashPassword($password);
    try {
      if (fRequest::get('action') == 'Sign In') {
        $user = new User($username);
        if ($user->getPassword() == $password_hash) {
          fAuthorization::setUserToken($user->getUsername());
          fMessaging::create('success', 'Logged in successfully.');
          fURL::redirect(fAuthorization::getRequestedURL(TRUE, Util::getReferer()));
        } else {
          throw new fValidationException('Password mismatch.');
        }
      } else if (fRequest::get('action') == 'Register') {
        if (strlen($username) < 4) {
          throw new fValidationException('Username is too short.');
        }
        try {
          $user = new User($username);
          throw new fValidationException('User already exists.');
        } catch (fNotFoundException $e) {
          $user = new User();
          $user->setUsername($username);
          $user->setPassword($password_hash);
          $user->store();
          fAuthorization::setUserToken($user->getUsername());
          fMessaging::create('success', 'Registered successfully.');
          fURL::redirect(fAuthorization::getRequestedURL(TRUE, Util::getReferer()));
        }
      }
    } catch (fExpectedException $e) {
      fMessaging::create('error', $e->getMessage());
      fURL::redirect(fAuthorization::getRequestedURL(TRUE, Util::getReferer()));
    }
  }
  
  public function logout()
  {
    fAuthorization::destroyUserInfo();
    fMessaging::create('success', 'Logged out successfully.');
    fURL::redirect(Util::getReferer());
  }
  
  public function changePassword()
  {
    $this->render('user/change_password');
  }
  
  public function updatePassword()
  {
    try {
      $old_password = fRequest::get('old_password');
      $new_password = fRequest::get('new_password');
      $repeat_password = fRequest::get('repeat_password');
      if (strlen($old_password) < 6) {
        throw new fValidationException('Old password is too short.');
      }
      if (strlen($new_password) < 6) {
        throw new fValidationException('New password is too short.');
      }
      if ($new_password != $repeat_password) {
        throw new fValidationException('Repeat password mismatch.');
      }
      $user = new User(fAuthorization::getUserToken());
      $old_password_hash = static::hashPassword($old_password);
      if ($user->getPassword() != $old_password_hash) {
        throw new fValidationException('Old password mismatch.');
      }
      $new_password_hash = static::hashPassword($new_password);
      $user->setPassword($new_password_hash);
      $user->store();
      fMessaging::create('success', 'Password updated successfully.');
      fURL::redirect(Util::getReferer());
    } catch (fExpectedException $e) {
      fMessaging::create('error', $e->getMessage());
      Util::redirect('/change/password');
    }
  }
}