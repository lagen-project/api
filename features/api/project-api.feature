Feature: Project API
  To be able to manage projects,
  As an API consumer, like an user interface,
  I should have a functioning projects API

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
    When I send a "GET" request to "projects/test-lagen-api"
    Then I should have the following response:
    """
    {
        "name": "TEST LAGEN API",
        "gitRepository": "",
        "slug": "test-lagen-api",
        "features": []
    }
    """

  Scenario: Retrieve the collection of projects
    When I send a "GET" request to "projects"
    Then I should have the following response:
    """
    [
        {
            "slug": "test-lagen-api",
            "name": "TEST LAGEN API"
        }
    ]
    """

  Scenario: Update the project
    When I send a "PUT" request to "projects/test-lagen-api" with the following body:
    """
    {
        "name": "TEST LAGEN API - EDITED",
        "gitRepository": "git@github.com/test/lagen-api.git"
    }
    """
    Then the configuration file of the "test-lagen-api" project should have the following values:
    """
    {
        "name": "TEST LAGEN API - EDITED",
        "gitRepository": "git@github.com/test/lagen-api.git"
    }
    """
