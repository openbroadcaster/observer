<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Codeception\Example;

class MediaCest extends BaseCest
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
    public function uploadMusic(AcceptanceTester $I, Example $example)
    {
        $I->click('#sidebar_media_upload_button');
        $I->attachFile('input[type="file"]', $example['file']);
        if ($example['id3']) {
            $I->waitForText('Use ID3 Data', 10);
        } else {
            $I->waitForText('ID3/EXIF Data Unavailable', 10);
        }
        $I->fillField('.artist_field', 'Acceptance Test Artist');
        $I->fillField('.title_field', 'Mozart Music');
        $I->fillField('.album_field', 'Album');
        $I->fillField('.year_field', '2022');
        $I->selectOption('.category_field', 'Music');
        $I->click('Save');
        $I->waitForText('Media has been saved.', 5);
    }

    /**
     * @depends uploadMusic
     */
    public function editMusic(AcceptanceTester $I)
    {
        $I->waitForElement('tr[data-title="Mozart Music"]', 10);
        $I->doubleClick('tr[data-title="Mozart Music"]');
        $I->waitForText('Media Details', 5);
        $I->click('#media_details_edit');

        $I->waitForElement('.title_field', 5);
        $I->fillField('.title_field', 'Mozart Music UPDATE');
        $I->fillField('.year_field', '2023');
        $I->click('Save');

        $I->waitForElement('tr[data-title="Mozart Music UPDATE"]', 5);
        $I->doubleClick('tr[data-title="Mozart Music UPDATE"]');
        $I->waitForText('Mozart Music UPDATE', 5, '#media_details_title');
        $I->waitForText('2023', 5, '#media_details_year');
    }

    /**
     * @depends editMusic
     */
    public function archiveMusic(AcceptanceTester $I)
    {
        $I->waitForElement('tr[data-title="Mozart Music UPDATE"]', 10);
        $I->doubleClick('tr[data-title="Mozart Music UPDATE"]');
        $I->waitForText('Media Details', 5);
        $I->click('#media_details_delete');

        $I->waitForText('Delete Media', 5, '#media_heading');
        $I->click('Yes, Delete');
        $I->waitForText('Media has been deleted.', 5);
    }

    /**
     * @depends archiveMusic
     */
    public function deleteMusic(AcceptanceTester $I)
    {
        $I->waitForElement('#sidebar_search_media_archived', 10);
        $I->click('#sidebar_search_media_archived');

        $I->waitForElement('tr[data-title="Mozart Music UPDATE"]', 10);
        $I->doubleClick('tr[data-title="Mozart Music UPDATE"]');
        $I->waitForText('Media Details', 5);
        $I->click('#media_details_delete');

        $I->waitForText('Delete Media', 5, '#media_heading');
        $I->click('Yes, Delete');
        $I->waitForText('Media has been deleted.', 5);
    }

    /**
     * @example { "file": "electric_scooter.ogv", "id3": false }
     */
    public function uploadVideo(AcceptanceTester $I, Example $example)
    {
        $I->click('#sidebar_media_upload_button');
        $I->attachFile('input[type="file"]', $example['file']);
        if ($example['id3']) {
            $I->waitForText('Use ID3 Data', 10);
        } else {
            $I->waitForText('ID3/EXIF Data Unavailable', 10);
        }
        $I->fillField('.artist_field', 'Acceptance Test Artist');
        $I->fillField('.title_field', 'Electric Scooter Video');
        $I->fillField('.album_field', 'Album');
        $I->fillField('.year_field', '2022');
        $I->selectOption('.category_field', 'Entertainment (non-music)');
        $I->click('Save');
        $I->waitForText('Media has been saved.', 5);
    }

    /**
     * @example { "file": "apollo.jpg", "id3": false }
     */
    public function uploadImage(AcceptanceTester $I, Example $example)
    {
        $I->click('#sidebar_media_upload_button');
        $I->attachFile('input[type="file"]', $example['file']);
        if ($example['id3']) {
            $I->waitForText('Use ID3 Data', 10);
        } else {
            $I->waitForText('ID3/EXIF Data Unavailable', 10);
        }
        $I->fillField('.artist_field', 'Acceptance Test Artist');
        $I->fillField('.title_field', 'Apollo 11 Image');
        $I->fillField('.album_field', 'Album');
        $I->fillField('.year_field', '2022');
        $I->selectOption('.category_field', 'Other');
        $I->click('Save');
        $I->waitForText('Media has been saved.', 5);
    }
}
