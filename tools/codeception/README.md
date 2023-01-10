# Usage

* Run `composer update --working-dir=./tools/codeception/` inside the top observer directory to download and update the testing framework.
* Inside the tests directory, copy Acceptance.example.yml to Acceptance.suite.yml (don't forget the 'suite' part!) and edit the url field to point tests at.
* Inside the tests/Acceptance directory, copy BaseCest.example.php to BaseCest.php and edit the fields with the appropriate values for testing.
* Make sure Selenium Server is appropriately configured and running for your platform:
    * On Windows: install the Java JRE, download Selenium Server, ensure that a Chrome WebDriver is available from the PATH, then run Selenium using `java -jar .\selenium-server-{VERSION}.jar standalone`
* Run `php vendor/bin/codecept run -v` inside the codeception directory to run the tests.

# Writing Tests

## element not interactable

`[Facebook\WebDriver\Exception\ElementNotInteractableException] element not interactable` is a common error that comes up when writing new tests. This can be something as simple as an element not being visible, but quite commonly it's because the selector allows for *multiple* elements, and Codeception does not allow `:first` to be used in CSS selectors. The way around this is to write the selector in the following way, using a click as an example:

```php
use Codeception\Util\Locator;

[...]

// Select the first possible button in the provided context and click it.
$I->click(Locator::elementAt('#context button.could_be_multiple', 1));
```

## see or waitForText

Because OpenBroadcaster loads so many values asynchronously and then inserts them in the DOM using JavaScript, relying on `$I->see` generally backfires. In places where this was used, it resulted in inconsistent test results: sometimes they would succeed, other times the async calls wouldn't happen quickly enough and the test would fail. This makes it incredibly tricky to figure out where anything is going wrong.

As a result, opt for using `$I->waitForText` or `$I->waitForElement` instead (or any of the other waitX methods described in the Codeception documentation). Adding a reasonable amount of waiting time to each call such as five seconds is fine, since it will only wait as long as it needs to, until the element appears.
