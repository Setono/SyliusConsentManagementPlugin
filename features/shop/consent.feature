@consent
Feature: Consenting to all services
  In order to have a fully functioning experience
  As a Visitor
  I want to be able to consent to all services

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
