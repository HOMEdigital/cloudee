<?php

namespace Home\Cloudee\Services;

use Home\Cloudee\Models\NextcloudUser;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NextcloudService
{
    private string $params;

    public function __construct()
    {
        $this->params = config('cloudee.nextcloud.params');
    }

    /**
     * @param string $userid
     * @param string $groupid
     * @return Response
     */
    public function addUserToGroup(string $userid, string $groupid): Response
    {
        return Http::nextcloud()
            ->post("ocs/v2.php/cloud/users/{$userid}/groups{$this->params}", [
                'groupid' => $groupid
            ]);
    }

    /**
     * @param string $oldUserid
     * @param string $newUserid
     * @param string $password
     * @return void
     */
    public function changeUserid(string $oldUserid, string $newUserid, string $password)
    {
        #-- get old user
        $oldUser = (new NextcloudUser())->find($oldUserid);

        #-- create new user
        $newUserResponse = (new NextcloudUser())
            ->create($newUserid, $password, $oldUser->displayName, $oldUser->email, $oldUser->language);

        if (Arr::get($newUserResponse->json(), 'ocs.meta.status') !== 'ok') {
            throwException(new HttpClientException($newUserResponse));
        }

        #-- get old user groups
        $groupsResponse = $this->getUserGroups($oldUserid)->json();
        $groups = Arr::get($groupsResponse, 'ocs.data.groups');

        #-- assign groups to new user
        if (!empty($groups)) {
            foreach ($groups as $group) {
                $this->addUserToGroup($newUserid, $group);
            }
        }

        #-- delete old user
        $deleteResponse = $this->deleteUser($oldUserid);
        if (Arr::get($deleteResponse->json(), 'ocs.meta.status') !== 'ok') {
            throwException(new HttpClientException($deleteResponse));
        }
    }

    /**
     * @param string $groupid
     * @return Response
     */
    public function createGroup(string $groupid): Response
    {
        return Http::nextcloud()
            ->post("ocs/v2.php/cloud/groups{$this->params}", [
                'groupid' => $groupid
            ]);
    }

    /**
     * create new user in nextcloud
     *
     * @param NextcloudUser $user
     * @return Response
     */
    public function createUser(NextcloudUser $user): Response
    {
        return Http::nextcloud()
            ->post("ocs/v2.php/cloud/users{$this->params}", $user->toArray());
    }

    /**
     * @param string $path
     * @param int $shareType
     * @param string $shareWith
     * @return Response
     */
    public function createShare(string $path, int $shareType, string $shareWith): Response
    {
        return Http::nextcloud()
            ->post("ocs/v2.php/apps/files_sharing/api/v1/shares{$this->params}", [
                'path' => $path,
                'shareType' => $shareType,
                'shareWith' => $shareWith,
            ]);
    }

    /**
     * @param string $groupid
     * @return Response
     */
    public function deleteGroup(string $groupid): Response
    {
        return Http::nextcloud()
            ->delete("ocs/v2.php/cloud/groups/{$groupid}{$this->params}");
    }

    /**
     * @param string $userid
     * @return Response
     */
    public function deleteUser(string $userid): Response
    {
        return Http::nextcloud()
            ->delete("ocs/v2.php/cloud/users/{$userid}{$this->params}");
    }

    /**
     * @param string $userid
     * @return Response
     */
    public function disableUser(string $userid): Response
    {
        return Http::nextcloud()
            ->put("ocs/v2.php/cloud/users/{$userid}/disable{$this->params}", []);
    }

    /**
     * @param string $userid
     * @param string $groupid
     * @return Response
     */
    public function demoteUserFromSubAdmin(string $userid, string $groupid): Response
    {
        return Http::nextcloud()
            ->delete("ocs/v2.php/cloud/users/{$userid}/subadmins{$this->params}", [
                'groupid' => $groupid
            ]);
    }

    /**
     * @param string $userid
     * @return Response
     */
    public function enableUser(string $userid): Response
    {
        return Http::nextcloud()
            ->put("ocs/v2.php/cloud/users/{$userid}/enable{$this->params}", []);
    }

    /**
     * @param string $userid
     * @return Response
     */
    public function getUser(string $userid): Response
    {
        return Http::nextcloud()
            ->get("ocs/v2.php/cloud/users/{$userid}{$this->params}");
    }

    /**
     * @param string $userid
     * @return Response
     */
    public function getUserGroups(string $userid): Response
    {
        return Http::nextcloud()
            ->get("ocs/v2.php/cloud/users/{$userid}/groups{$this->params}");
    }

    /**
     * @return Response
     */
    public function getUserList(): Response
    {
        return Http::nextcloud()
            ->get("ocs/v2.php/cloud/users{$this->params}");
    }

    /**
     * following values can be updated in nextcloud: email, displayName and password
     * - for each value a separate request has to be made
     *
     * @param NextcloudUser $user
     * @return array
     */
    public function updateUser(NextcloudUser $user): array
    {
        $data = $user->toArray();
        $responses = [];
        #-- remove userid anf language from array
        unset($data['userid']);
        unset($data['language']);
        #-- remove password from array if it is empty
        if (empty($data['password'])) {
            unset($data['password']);
        }
        #-- make a request for each value to change
        foreach ($data as $key=>$value){
            $responses[] = Http::nextcloud()
                ->put("ocs/v2.php/cloud/users/{$user->userid}{$this->params}", [
                    'key' => Str::lower($key),
                    'value' => $value,
                ]);
        }

        return $responses;
    }

    /**
     * @param string $userid
     * @param string $groupid
     * @return Response
     */
    public function promoteUserToSubAdmin(string $userid, string $groupid): Response
    {
        return Http::nextcloud()
            ->post("ocs/v2.php/cloud/users/{$userid}/subadmins{$this->params}", [
                'groupid' => $groupid
            ]);
    }

    /**
     * @param string $userid
     * @param string $groupid
     * @return Response
     */
    public function removeUserFromGroup(string $userid, string $groupid): Response
    {
        return Http::nextcloud()
            ->delete("ocs/v2.php/cloud/users/{$userid}/groups{$this->params}", [
                'groupid' => $groupid
            ]);
    }
}
