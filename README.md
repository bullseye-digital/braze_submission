# braze_submission

Braze submission handler for webform and custom module

# Global configuration

Module setting can be found at: /admin/config/braze_submission/brazesubmissionconfig

# Webform submit handler configuration

* **Braze track endpoint:** Braze user track endpoint, [detail](https://www.braze.com/docs/api/basics/)
* **Braze Group REST App Key:** Braze Group REST API Key, [detail](https://www.braze.com/docs/api/api_key/)
* **Custom data:** Braze attributes and data field mapping, [detail](https://www.braze.com/docs/api/objects_filters/user_attributes_object/#user-attributes-object-specification)

## Webform Custom data mapping
Custom data mapping is in YML format. Example below:

# Use module service for other module
* Call statically:
$braze_submission = \Drupal::service('braze_submission.braze_submission_service');
* Use dependency injection (recommended): See **BrazeSubmissionHandler.php** for example how to call it from plugin or other service.


