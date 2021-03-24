@consent
Feature: Consenting to all services
  In order to have a fully functioning experience
  As a Visitor
  I want to be able to consent to all services

  Background:
    Given the store operates on a single channel in "United States"

  @ui @javascript
  Scenario: Consenting to all services
    Given the store uses a service named "Sylius Analytics"
    When I visit this channel's homepage
    Then I should see a consent dialog
    And I should be able to click an accept button
    And the "Sylius Analytics" should run
