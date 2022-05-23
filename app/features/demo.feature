# This file contains a user story for demonstration only.
# Learn how to get started with Behat and BDD on Behat's website:
# http://behat.org/en/latest/quick_start.html

Feature:
    In order to prove that the Behat Symfony extension is correctly installed
    As a user
    I want to have a demo scenario

    Background:
        When I add "Content-Type" header equal to "application/json"
        And I add "Accept" header equal to "application/json"

    Scenario: It receives a response from Symfony's kernel
        When I send a GET request to "/api"
        Then the response should be in JSON
