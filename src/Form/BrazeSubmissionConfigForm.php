<?php

namespace Drupal\braze_submission\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BrazeSubmissionConfigForm.
 */
class BrazeSubmissionConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'braze_submission.brazesubmissionconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'braze_submission_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('braze_submission.brazesubmissionconfig');
    $form['braze_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Braze Configs'),
      '#open' => TRUE,
    ];

    $form['braze_config']['app_group_rest_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Group REST API Key'),
      '#description' => $this->t('Enter App Group REST APi Key'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('app_group_rest_api_key'),
    ];
    $form['braze_config']['api_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Endpoint'),
      '#description' => $this->t('Enter API Endpoint'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('api_endpoint'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('braze_submission.brazesubmissionconfig')
      ->set('app_group_rest_api_key', $form_state->getValue('app_group_rest_api_key'))
      ->set('api_endpoint', $form_state->getValue('api_endpoint'))
      ->save();
  }

}
