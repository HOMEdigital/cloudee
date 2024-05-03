<?php

namespace Home\Cloudee\Http\Controllers;

use Home\Cloudee\Models\NextcloudUser;
use Home\Cloudee\Services\NextcloudService;
use Home\Cloudee\Services\WebdavService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToReadFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CloudeeController extends Controller
{
    private WebdavService $webdavService;

    public function __construct()
    {
        $this->webdavService = new WebdavService();
    }

    /**
     * @param Request $request
     * @return Application|Response|ResponseFactory
     */
    public function createUser(Request $request): Response|Application|ResponseFactory
    {
        $user = new NextcloudUser();
        $response = $user->create($request->username, $request->password, $request->displayName, $request->email);

        return \response($response->json());
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     * @throws ConnectionException
     */
    public function createShareWithGroup(Request $request): Application|ResponseFactory|Response
    {
        $groupid = $request->get('groupId');
        $path = $request->get('path');

        NextcloudService::createGroup($groupid);
        $this->webdavService->createFolder("{$path}/{$groupid}");
        NextcloudService::createShare("{$path}/{$groupid}", 1, $groupid);

        return \response();
    }

    /**
     * @param Request $request
     * @return Application|Response|ResponseFactory
     * @throws ConnectionException
     */
    public function getUser(Request $request): Response|Application|ResponseFactory
    {
        $response = NextcloudService::getUser($request->userid);

        return \response($response->json());
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     * @throws ConnectionException
     */
    public function deleteGroup(Request $request): Application|ResponseFactory|Response
    {
        $response = NextcloudService::deleteGroup($request->groupid);

        return \response($response->json());
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     * @throws ConnectionException
     */
    public function deleteUser(Request $request): Response|Application|ResponseFactory
    {
        $response = NextcloudService::deleteUser($request->userid);

        return \response($response->json());
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|\Illuminate\Foundation\Application|Response
     * @throws ConnectionException
     */
    public function demoteUserFromSubAdmin(Request $request)
    {
        $response = NextcloudService::demoteUserFromSubAdmin($request->userid, $request->groupid);

        return \response($response->json());
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     * @throws ValidationException
     * @throws ConnectionException
     */
    public function updateUser(Request $request): Response|Application|ResponseFactory
    {
        $data = $request->only(['userid', 'password', 'email', 'displayName']);
        $validator = Validator::make($data, [
            'userid' => 'required|string',
            'password' => 'sometimes|string',
            'email' => 'sometimes|email',
            'displayName' => 'sometimes|string',
        ]);

        if (!$validator->validate()) {
            return \response($validator->errors());
        }

        $user = new NextcloudUser();
        $user->fromArray($data);

        $responses = NextcloudService::updateUser($user);
        $json = [];
        if(!empty($responses)){
            foreach ($responses as $response) {
                $json[] = $response->json();
            }
        }

        return \response($json);
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response|StreamedResponse
     */
    public function downloadFile(Request $request): Application|ResponseFactory|Response|StreamedResponse
    {
        $path = $request->filename;
        $filename = Arr::last(explode('/', $path));

        try {
            $stream = $this->webdavService->downloadFile($path);
            return \response()->streamDownload(function () use($stream){
                fpassthru($stream);
            }, $filename);
        } catch (FilesystemException | UnableToReadFile $exception) {
            // handle the error
        }
        return \response([], 404);
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     * @throws FilesystemException
     */
    public function getFolderContent(Request $request): Application|ResponseFactory|Response
    {
        return \response($this->webdavService->getFolderContent($request->dir));
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|\Illuminate\Foundation\Application|Response
     * @throws ConnectionException
     */
    public function promoteUserToSubAdmin(Request $request)
    {
        $response = NextcloudService::promoteUserToSubAdmin($request->userid, $request->groupid);

        return \response($response->json());
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     * @throws ConnectionException
     */
    public function removeUserFromGroup(Request $request): Application|ResponseFactory|Response
    {
        $response = NextcloudService::removeUserFromGroup($request->userid, $request->groupid);

        return \response($response->json());
    }
}
