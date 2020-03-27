<?php

namespace Drupal\braze_submission;

/**
 * Interface BrazeSubmissionInterface.
 */
interface BrazeSubmissionInterface {

  /**
   * Submit data to Braze using global config.
   *
   * @param array $data
   *   Array submission data.
   *
   * @return bool
   *   Boolean Braze send status.
   */
  public function brazeSubmissionSendBraze(array $data);

  /**
   * Submit data to Braze using webform config.
   *
   * @param array $data
   *   Associate array between braze attributes and data.
   * @param array $configs
   *   Array webform configs.
   *
   * @return bool
   *   Boolean Braze send success.
   */
  public function brazeSubmissionWebformSendBraze(array $data, array $configs);

}
