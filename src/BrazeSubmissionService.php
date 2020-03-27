<?php

namespace Drupal\braze_submission;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * Class BrazeSubmissionService.
 */
class BrazeSubmissionService implements BrazeSubmissionInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new BrazeService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Get Braze global config.
   */
  public function brazeSubmissionGetConfigs() {
    $global_configs = $this->configFactory->get('braze_submission.brazesubmissionconfig');
    $global_api_endpoint = $global_configs->get('api_endpoint');
    $global_group_rest_api_key = $global_configs->get('app_group_rest_api_key');

    return [
      'api_url' => $global_api_endpoint,
      'app_group_key' => $global_group_rest_api_key,
    ];
  }

  /**
   * Submit data to Braze using global config.
   *
   * @param array $data
   *   Array submission data.
   *
   * @return bool
   *   Boolean Braze send status.
   */
  public function brazeSubmissionSendBraze(array $data) {
    $braze_configs = $this->brazeSubmissionGetConfigs();
    $valid_braze_config = (isset($braze_configs['api_url']) && !empty($braze_configs['api_url']) && isset($braze_configs['app_group_key']) && !empty($cbraze_configsonfigs['app_group_key'])) ? TRUE : FALSE;

    // Send using global config. to global config.
    if ($valid_braze_config) {
      return $this->brazeSubmissionSend($data, $braze_configs);
    }
    else {
      $this->loggerFactory->get('braze_submission')->error('Global Braze config is not configured.');
    }
  }

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
  public function brazeSubmissionWebformSendBraze(array $data, array $configs) {
    $braze_configs = $this->brazeSubmissionGetConfigs();
    $valid_webform_config = (isset($configs['api_url']) && !empty($configs['api_url']) && isset($configs['app_group_key']) && !empty($configs['app_group_key'])) ? TRUE : FALSE;
    $valid_braze_config = (isset($braze_configs['api_url']) && !empty($braze_configs['api_url']) && isset($braze_configs['app_group_key']) && !empty($cbraze_configsonfigs['app_group_key'])) ? TRUE : FALSE;

    // Send submission using webform config if set.
    if ($valid_webform_config) {
      return $this->brazeSubmissionSend($data, $configs);
    }
    else {
      $this->loggerFactory->get('braze_submission')->error('Global Brazee config is not configured.');
    }

    // Fallback to global config.
    if ($valid_braze_config) {
      return $this->brazeSubmissionSend($data, $braze_configs);
    }
    else {
      $this->loggerFactory->get('braze_submission')->error('Webform Braze config is not configured.');
    }

    return FALSE;
  }

  /**
   * Send request to Braze using http client.,.
   *
   * @param array $data
   *   Array of data.
   * @param array $configs
   *   Array if configs.
   *
   * @return boolan
   *   Boolean Braze send status.
   */
  public function brazeSubmissionSend(array $data, array $configs) {
    // Setup headers.
    $headers = [
      'Content-Type' => 'application/json',
    ];

    // Setup Braze attributes.
    $braze_attributes = [
      $data,
    ];

    // Setup Braze payload.
    $braze_payload = [
      'app_group_id' => $configs['app_group_key'],
      'attributes' => $braze_attributes,
    ];

    try {
      $url = Url::fromUri($configs['api_url'], [])->toString();
      $request = $this->httpClient->post($url, [
        'verify' => FALSE,
        'headers' => $headers,
        'body' => Json::encode($braze_payload),
      ]);

      $status_code = $request->getStatusCode();
      $response = $request->getBody()->getContents();
      if ($status_code >= 200 || $status_code < 300) {
        $this->loggerFactory->get('braze_submission')->notice('Response: @response', ['@response' => $response]);
        return TRUE;
      }
      else {
        $this->loggerFactory->get('braze_submission')->error('Error code: @code', ['@code' => $status_code]);
      }
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('braze_submission')->error('Error: @error', ['@error' => $e->getMessage()]);
    }

    return FALSE;
  }

}
