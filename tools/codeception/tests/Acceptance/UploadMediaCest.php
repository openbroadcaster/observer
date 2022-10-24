<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Codeception\Example;

class UploadMediaCest extends BaseCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/welcome');
        $I->fillField('#login_username', $this->username);
        $I->fillField('#login_password', $this->password);
        $I->click('#login_submit');
        $I->waitForText('Logged in as: ' . $this->username, 10);
    }

    /**
     * @example { "file": "eaglelanded.mp3", "id3": true }
     */
    public function uploadMusicSuccess(AcceptanceTester $I, Example $example)
    {
        $I->click('#sidebar_media_upload_button');
        $I->attachFile('input[type="file"]', $example['file']);
        if ($example['id3']) {
            $I->waitForText('Use ID3 Data', 10);
        } else {
            $I->waitForText('ID3/EXIF Data Unavailable', 10);
        }
        $I->fillField('.artist_field', 'Acceptance Test Artist');
        $I->fillField('.title_field', 'AT Mozart Music');
        $I->fillField('.album_field', 'AT Album');
        $I->fillField('.year_field', '2022');
        $I->selectOption('.category_field', 'Music');
        $I->click('Save');
        $I->waitForText('Media has been saved.', 5);
    }

    /**
     * @example { "file": "electric_scooter.ogv", "id3": false }
     */
    public function uploadVideoSuccess(AcceptanceTester $I, Example $example)
    {
        $I->click('#sidebar_media_upload_button');
        $I->attachFile('input[type="file"]', $example['file']);
        if ($example['id3']) {
            $I->waitForText('Use ID3 Data', 10);
        } else {
            $I->waitForText('ID3/EXIF Data Unavailable', 10);
        }
        $I->fillField('.artist_field', 'Acceptance Test Artist');
        $I->fillField('.title_field', 'AT Electric Scooter Video');
        $I->fillField('.album_field', 'AT Album');
        $I->fillField('.year_field', '2022');
        $I->selectOption('.category_field', 'Entertainment (non-music)');
        $I->click('Save');
        $I->waitForText('Media has been saved.', 5);
    }

    /**
     * @example { "file": "apollo.jpg", "id3": false }
     */
    public function uploadImageSuccess(AcceptanceTester $I, Example $example)
    {
        $I->click('#sidebar_media_upload_button');
        $I->attachFile('input[type="file"]', $example['file']);
        if ($example['id3']) {
            $I->waitForText('Use ID3 Data', 10);
        } else {
            $I->waitForText('ID3/EXIF Data Unavailable', 10);
        }
        $I->fillField('.artist_field', 'Acceptance Test Artist');
        $I->fillField('.title_field', 'AT Apollo 11 Image');
        $I->fillField('.album_field', 'AT Album');
        $I->fillField('.year_field', '2022');
        $I->selectOption('.category_field', 'Other');
        $I->click('Save');
        $I->waitForText('Media has been saved.', 5);
    }
}
