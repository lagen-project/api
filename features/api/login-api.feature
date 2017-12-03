Feature: Login API
  To be able to use the API,
  As an API consumer, like an user interface,
  I should be able to login

  Background: 
    Given I have this user in database
      | username | password | roles |
      | pierre   | rolland  | user  |

  Scenario: Successful login attempt
    When I send a "POST" request to "login" with the following params:
      | _username | _password |
      | pierre    | rolland   |
    Then I should have a 200 response containing the "token" key

  Scenario: Failed login attempt
    When I send a "POST" request to "login" with the following params:
      | _username | _password |
      | fake      | pierrot   |
    Then I should have a 401 response
