# Usage

* Run `composer update --working-dir=./tools/codeception/` inside the top observer directory to download and update the testing framework.
* Inside the tests directory, copy Acceptance.example.yml to Acceptance.suite.yml (don't forget the 'suite' part!) and edit the url field to point tests at.
* Inside the tests/Acceptance directory, copy BaseCest.example.php to BaseCest.php and edit the fields with the appropriate values for testing.
* Make sure Selenium Server is appropriately configured and running for your platform:
    * On Windows: install the Java JRE, download Selenium Server, ensure that a Chrome WebDriver is available from the PATH, then run Selenium using `java -jar .\selenium-server-{VERSION}.jar standalone`
* Run `php vendor/bin/codecept run` inside the codeception directory to run the tests.
