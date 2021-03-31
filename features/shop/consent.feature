@consent
Feature: Consenting to services
  In order to have control of my privacy settings
  As a Visitor
  I want to be able to consent to the services that I want to run

  Background:
    Given the store operates on a single channel in "United States"
    And the store uses multiple services, at least one in each category

  @ui @javascript
  Scenario: Consenting to all services
    When I visit this channel's homepage
    And I see the consent dialog
    And I click the accept button
    Then the consent dialog should disappear
    And all services should run

  @ui @javascript
  Scenario: Consenting to marketing services
    When I visit this channel's homepage
    And I see the consent dialog
    And I click the more information button
    And I only check marketing services
    And I click the accept button
    Then the consent dialog should disappear
    And only marketing services should run

  @ui @javascript
  Scenario: Consent is stored
    When I visit this channel's homepage
    And I click the accept button
    And I reload the page
    Then all services should run
    And the consent dialog should not be visible
