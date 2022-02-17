<?php

namespace Home\Cloudee\Tests;

use Home\Cloudee\Models\NextcloudUser;
use Home\Cloudee\Services\NextcloudService;
use Home\Cloudee\Services\WebdavService;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesApplication;

class CloudeeNextcloudApiTest extends TestCase
{
    use CreatesApplication;

    private NextcloudService $nextcloudService;
    private WebdavService $webdavService;
    private array $userArr = [];
    private NextcloudUser $user;
    private string $groupid;

    function setUp(): void
    {
        parent::setUp();

        $this->nextcloudService = new NextcloudService();
        $this->webdavService = new WebdavService();
        $this->userArr = [
            'userid' => 'unittest',
            'password' => 'superSecretPassword',
            'displayName' => 'Unit Test',
            'email' => 'dev@home.digital',
            'language' => 'de',
        ];
        $this->user = new NextcloudUser();
        $this->groupid = 'testgroup';
    }

    /** @test */
    function it_creates_a_nextcloud_user()
    {
        $response = $this->user->create($this->userArr['userid'], $this->userArr['password'], $this->userArr['displayName'], $this->userArr['email'])->json();

        $this->assertEquals($this->userArr['userid'], Arr::get($response, 'ocs.data.id'));
    }

    /** @test */
    function it_gets_a_nextcloud_user()
    {
        $response = $this->nextcloudService->getUser($this->userArr['userid'])->json();

        $this->assertEquals($this->userArr['userid'], Arr::get($response, 'ocs.data.id'));
    }

    /** @test */
    function it_updates_a_nextcloud_user()
    {
        $newEmail = 'test@home.digital';
        $this->user->fromArray($this->userArr);
        $this->user->email = $newEmail;
        $this->nextcloudService->updateUser($this->user);

        $response = $this->nextcloudService->getUser($this->user->userid);

        $this->assertEquals($newEmail, Arr::get($response, 'ocs.data.email'));
    }

    /** @test */
    function it_creates_a_nextcloud_group()
    {
        $response = $this->nextcloudService->createGroup($this->groupid)->json();

        $this->assertEquals('ok', Arr::get($response, 'ocs.meta.status'));
    }

    /** @test */
    function it_adds_a_user_to_a_group()
    {
        $response = $this->nextcloudService->addUserToGroup($this->userArr['userid'], $this->groupid)->json();

        $this->assertEquals('ok', Arr::get($response, 'ocs.meta.status'));
    }

    /** @test */
    function it_promotes_a_user_to_sub_admin()
    {
        $response = $this->nextcloudService->promoteUserToSubAdmin($this->userArr['userid'], $this->groupid)->json();

        $this->assertEquals('ok', Arr::get($response, 'ocs.meta.status'));
    }

    /** @test */
    function it_demotes_a_user_from_sub_admin()
    {
        $response = $this->nextcloudService->demoteUserFromSubAdmin($this->userArr['userid'], $this->groupid)->json();

        $this->assertEquals('ok', Arr::get($response, 'ocs.meta.status'));
    }

    /** @test */
    function it_removes_a_user_from_a_group()
    {
        $response = $this->nextcloudService->removeUserFromGroup($this->userArr['userid'], $this->groupid)->json();

        $this->assertEquals('ok', Arr::get($response, 'ocs.meta.status'));
    }

    /** @test */
    function it_deletes_a_nextcloud_group()
    {
        $response = $this->nextcloudService->deleteGroup($this->groupid)->json();

        $this->assertEquals('ok', Arr::get($response, 'ocs.meta.status'));
    }

    /** @test */
    function it_creates_a_nextcloud_share()
    {
        $groupid = $this->groupid . 'share';
        $path = 'unit';

        #-- create group, folder and share
        $this->nextcloudService->createGroup($groupid);
        $this->webdavService->createFolder("{$path}/{$groupid}");
        $response = $this->nextcloudService->createShare("{$path}/{$groupid}", 1, $groupid);

        $this->assertEquals('ok', Arr::get($response, 'ocs.meta.status'));

        #-- cleanup created group and folder
        $this->webdavService->deleteFolder($path);
        $this->assertFalse($this->webdavService->folderExists($path));

        $response = $this->nextcloudService->deleteGroup($groupid)->json();
        $this->assertEquals('ok', Arr::get($response, 'ocs.meta.status'));
    }

    /** @test */
    function it_deletes_a_nextcloud_user()
    {
        $response = $this->nextcloudService->deleteUser($this->userArr['userid'])->json();

        $this->assertEquals('ok', Arr::get($response, 'ocs.meta.status'));
    }

    /** @test */
    function it_gets_the_content_of_a_directory_by_path()
    {
        $path = 'remote.php/dav/files/igkg_admin/IGKG Züri/Dokumente/';

        $content = $this->webdavService->getFolderContent($path, true);

        $this->assertNotEmpty($content);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('type', $content[0]);
    }

    /** @test */
    function it_can_download_a_file()
    {
        $path = 'Readme.md';
        $stream = $this->webdavService->downloadFile($path);

        Storage::fake('unit');
        Storage::disk('unit')->assertMissing($path);
        Storage::disk('unit')->writeStream($path, $stream);
        Storage::disk('unit')->assertExists($path);
    }
}
