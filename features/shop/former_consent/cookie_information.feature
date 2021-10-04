@former_consent
Feature: Handling former consents from Cookie Information
  In order to save my visitors from unnecessary distraction
  As an Administrator
  I want to be able to detect and use former consents

  Background:
    Given the store operates on a single channel in "United States"
    And the visitor has consented to all services through Cookie Information before

  @ui @javascript
  Scenario: Running all services
    When I visit this channel's homepage
    Then the consent dialog should not be visible
    And all services should run
