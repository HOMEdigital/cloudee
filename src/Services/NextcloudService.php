<?php

namespace Home\Cloudee\Services;

use Home\Cloudee\Models\NextcloudUser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NextcloudService
{
    /**
     * @param string $userid
     * @param string $groupid
     * @return Response
     * @throws ConnectionException
     */
    public static function addUserToGroup(string $userid, string $groupid): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->post("ocs/v2.php/cloud/users/{$userid}/groups{$params}", [
                'groupid' => $groupid
            ]);
    }

    /**
     * @param string $oldUserid
     * @param string $newUserid
     * @param string $password
     * @return void
     * @throws ConnectionException
     * @throws HttpClientException
     */
    public static function changeUserid(string $oldUserid, string $newUserid, string $password)
    {
        #-- get old user
        $oldUser = (new NextcloudUser())->find($oldUserid);

        #-- create new user
        $newUserResponse = (new NextcloudUser())
            ->create($newUserid, $password, $oldUser->displayName, $oldUser->email, $oldUser->language);

        if (Arr::get($newUserResponse->json(), 'ocs.meta.status') !== 'ok') {
            throw new HttpClientException($newUserResponse);
        }

        #-- get old user groups
        $groupsResponse = self::getUserGroups($oldUserid)->json();
        $groups = Arr::get($groupsResponse, 'ocs.data.groups');

        #-- assign groups to new user
        if (!empty($groups)) {
            foreach ($groups as $group) {
                self::addUserToGroup($newUserid, $group);
            }
        }

        #-- delete old user
        $deleteResponse = self::deleteUser($oldUserid);
        if (Arr::get($deleteResponse->json(), 'ocs.meta.status') !== 'ok') {
            throw new HttpClientException($deleteResponse);
        }
    }

    /**
     * @param string $groupid
     * @return Response
     * @throws ConnectionException
     */
    public static function createGroup(string $groupid): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->post("ocs/v2.php/cloud/groups{$params}", [
                'groupid' => $groupid
            ]);
    }

    /**
     * create new user in nextcloud
     *
     * @param NextcloudUser $user
     * @return Response
     * @throws ConnectionException
     */
    public static function createUser(NextcloudUser $user): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->post("ocs/v2.php/cloud/users{$params}", $user->toArray());
    }

    /**
     * @param string $path
     * @param int $shareType
     * @param string $shareWith
     * @return Response
     * @throws ConnectionException
     */
    public static function createShare(string $path, int $shareType, string $shareWith): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->post("ocs/v2.php/apps/files_sharing/api/v1/shares{$params}", [
                'path' => $path,
                'shareType' => $shareType,
                'shareWith' => $shareWith,
            ]);
    }

    /**
     * @param string $groupid
     * @return Response
     * @throws ConnectionException
     */
    public static function deleteGroup(string $groupid): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->delete("ocs/v2.php/cloud/groups/{$groupid}{$params}");
    }

    /**
     * @param string $userid
     * @return Response
     * @throws ConnectionException
     */
    public static function deleteUser(string $userid): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->delete("ocs/v2.php/cloud/users/{$userid}{$params}");
    }

    /**
     * @param string $userid
     * @return Response
     * @throws ConnectionException
     */
    public static function disableUser(string $userid): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->put("ocs/v2.php/cloud/users/{$userid}/disable{$params}", []);
    }

    /**
     * @param string $userid
     * @param string $groupid
     * @return Response
     * @throws ConnectionException
     */
    public static function demoteUserFromSubAdmin(string $userid, string $groupid): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->delete("ocs/v2.php/cloud/users/{$userid}/subadmins{$params}", [
                'groupid' => $groupid
            ]);
    }

    /**
     * @param string $userid
     * @return Response
     * @throws ConnectionException
     */
    public static function enableUser(string $userid): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->put("ocs/v2.php/cloud/users/{$userid}/enable{$params}", []);
    }

    /**
     * @param string $userid
     * @return Response
     * @throws ConnectionException
     */
    public static function getUser(string $userid): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->get("ocs/v2.php/cloud/users/{$userid}{$params}");
    }

    /**
     * @param string $userid
     * @return Response
     * @throws ConnectionException
     */
    public static function getUserGroups(string $userid): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->get("ocs/v2.php/cloud/users/{$userid}/groups{$params}");
    }

    /**
     * @return Response
     * @throws ConnectionException
     */
    public static function getUserList(): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->get("ocs/v2.php/cloud/users{$params}");
    }

    /**
     * following values can be updated in nextcloud: email, displayName and password
     * - for each value a separate request has to be made
     *
     * @param NextcloudUser $user
     * @return array
     * @throws ConnectionException
     */
    public static function updateUser(NextcloudUser $user): array
    {
        $params = config('cloudee.nextcloud.params');
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
                ->put("ocs/v2.php/cloud/users/{$user->userid}{$params}", [
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
     * @throws ConnectionException
     */
    public static function promoteUserToSubAdmin(string $userid, string $groupid): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->post("ocs/v2.php/cloud/users/{$userid}/subadmins{$params}", [
                'groupid' => $groupid
            ]);
    }

    /**
     * @param string $userid
     * @param string $groupid
     * @return Response
     * @throws ConnectionException
     */
    public static function removeUserFromGroup(string $userid, string $groupid): Response
    {
        $params = config('cloudee.nextcloud.params');
        return Http::nextcloud()
            ->delete("ocs/v2.php/cloud/users/{$userid}/groups{$params}", [
                'groupid' => $groupid
            ]);
    }
}
