<?php

namespace Drupal\braze_submission\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\braze_submission\BrazeSubmissionInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Serialization\Json;

/**
 * Webform example handler.
 *
 * @WebformHandler(
 *   id = "webform_braze_submission",
 *   label = @Translation("Braze Submission"),
 *   category = @Translation("Form Handler"),
 *   description = @Translation("Submit webform data to Braze."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class BrazeSubmissionHandler extends WebformHandlerBase {

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;


  /**
   * Braze submission service.
   *
   * @var \Drupal\braze_submission\BrazeSubmissionInterface
   */
  protected $brazeService;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, WebformTokenManagerInterface $token_manager, BrazeSubmissionInterface $braze_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->tokenManager = $token_manager;
    $this->brazeService = $braze_service;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('webform.token_manager'),
      $container->get('braze_submission.braze_submission_service'),
      $container->get('logger.factory')
    );
  }

  /**
   * Default configuration.
   */
  public function defaultConfiguration() {
    return [
      'api_url' => '',
      'app_group_key' => '',
      'custom_data' => '',
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Additional.
    $form['additional'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additional settings'),
    ];

    $form['additional']['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Braze track endpoint'),
      '#description' => $this->t('Enter Braze track endpoint url.'),
      '#default_value' => $this->configuration['api_url'],
    ];

    $form['additional']['app_group_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Braze Group REST App Key'),
      '#description' => $this->t('Enter Group REST App Key'),
      '#default_value' => $this->configuration['app_group_key'],
    ];

    $form['additional']['custom_data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom data'),
      '#description' => $this->t('Enter custom data that will be included in all remote post requests.<br/>'),
      '#default_value' => $this->configuration['custom_data'],
      '#suffix' => $this->t('<div role="contentinfo" class="messages messages--info">
        field_braze_attribute: field_webform_key
      </div>'),
    ];

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, posted submissions will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    $this->elementTokenValidate($form);

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);
    // Cast debug.
    $this->configuration['debug'] = (bool) $this->configuration['debug'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $configuration = $this->getConfiguration();
    $settings = $configuration['settings'];
    return [
      '#settings' => $settings,
    ] + parent::getSummary();

  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $custom_data = (!empty($this->configuration['custom_data'])) ? $this->configuration['custom_data'] : '';
    if (!empty($custom_data)) {
      $data_replace = $this->replaceTokens($custom_data, $webform_submission);
      $data_replace_decode = Yaml::decode($data_replace);
      $data = $webform_submission->getData();
      if ($this->configuration['debug']) {
        $this->loggerFactory->get('braze_submission')->info('Config data: @config_data, Data: @data', [
          '@config_data' => Json::encode($data_replace_decode),
          '@data' => Json::encode($data),
        ]);
      }

      // Send data to Braze.
      if (!empty($data_replace_decode)) {
        $braze_submitted = $this->brazeService->brazeSubmissionWebformSendBraze($data, $this->configuration);
        if ($braze_submitted) {
          $this->loggerFactory->get('braze_submission')->info('Braze submission success for data: @data', [
            '@data' => Json::encode($data_replace_decode),
          ]);
        }
      }
    }
    else {
      $this->loggerFactory->get('braze_submission')->error('Custom data is not configured.');
    }

  }

}
