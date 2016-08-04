@api
Feature: Email teachings archive
  In order to make email teachings accessible later
    we need an archive of them 
  
  Background:
    Given I am logged in as a user with the "administrator" role
    
  Scenario: Email teachings content type exists
    When I visit "node/add/email_teaching"
    Then I should see the response status code should be 200 

  Scenario: Email teachings have URL archive/emails/year/month/day/titleword1-titleword2-etc
    Given "email teaching" content:
      | title           | created      |
      | email1          | 2005-12-30 13:12:01 |
      | email2          | 2005-10-01 11:05:00 |
      | email3          | 2005-10-01 00:00:00 |
      | email 4         | 2005-10-01 13:00:00 |
      | email, and 5    | 2005-10-01 13:00:00 |
    When I visit "archive/email/2005/12/30/email1"
    When I visit "archive/email/2005/10/01/email2"
    When I visit "archive/email/2005/10/01/email3"
    When I visit "archive/email/2005/10/01/email-4"
    When I visit "archive/email/2005/10/01/email-and-5"
    Then the response status code should be 200 
