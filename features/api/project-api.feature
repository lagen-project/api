Feature: Project API

  Scenario: Create a new project
    Given I have no projects installed
    When I send a "POST" request to "projects" with the following body:
    """
    {
        "name": "TEST LAGEN API"
    }
    """
    Then I should have a directory "test-lagen-api" inside the "projects" directory
    And the configuration file of the "test-lagen-api" project should have the following values:
    """
    {
        "name": "TEST LAGEN API"
    }
    """

  Scenario: Retrieve the single project
    When  I send a "GET" request to "projects/test-lagen-api"
    Then I should have the following response:
    """
    {
        "name": "TEST LAGEN API",
        "gitRepository": "",
        "slug": "test-lagen-api",
        "features": []
    }
    """
