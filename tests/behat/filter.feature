@local @local_gojuon @javascript
Feature: Gojūon index bar on the participants page
  In order to navigate a roster of Japanese names
  As a teacher
  I need to filter participants by the kana row of their phonetic name

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | lastnamephonetic | firstnamephonetic |
      | kato     | Taro      | Kato     | かとう           | たろう            |
      | sato     | Jiro      | Sato     | さとう           | じろう            |
      | tanaka   | Hanako    | Tanaka   | たなか           | はなこ            |
      | teacher1 | Terry     | Teacher  | せんせい         | せんせい          |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | kato     | C1     | student        |
      | sato     | C1     | student        |
      | tanaka   | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | fullnamedisplay           | lastname firstname lastnamephonetic firstnamephonetic |              |
      | enabled                   | 1                                                     | local_gojuon |
    And I log in as "teacher1"
    And I am on the "Course 1" "enrolled users" page

  Scenario: The kana bar filters the participant list by the last-name reading
    Then I should see "Kato"
    And I should see "Sato"
    And I should see "Tanaka"
    When I click on "か" "button" in the ".local-gojuon-barrow[data-filter='kanalast']" "css_element"
    Then I should see "Kato"
    And I should not see "Sato"
    And I should not see "Tanaka"

  Scenario: Clearing the filter restores the full list
    When I click on "か" "button" in the ".local-gojuon-barrow[data-filter='kanalast']" "css_element"
    And I click on "すべて" "button" in the ".local-gojuon-barrow[data-filter='kanalast']" "css_element"
    Then I should see "Kato"
    And I should see "Sato"
    And I should see "Tanaka"

  Scenario: The two axes compose
    When I click on "た" "button" in the ".local-gojuon-barrow[data-filter='kanalast']" "css_element"
    And I click on "は" "button" in the ".local-gojuon-barrow[data-filter='kanafirst']" "css_element"
    Then I should see "Tanaka"
    And I should not see "Kato"

  Scenario: The bar renders above the participant list, not buried in the footer
    Then ".local-gojuon-bar" "css_element" should be visible
    And ".local-gojuon-bar" "css_element" should appear before "[data-table-component][data-table-handler='participants']" "css_element"

  @accessibility
  Scenario: The kana bar meets accessibility standards
    Then the ".local-gojuon-bar" "css_element" should meet "wcag2aa" accessibility standards
