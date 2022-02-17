<?php

namespace Home\Cloudee\Http\Controllers;

use Home\Cloudee\Models\NextcloudUser;
use Home\Cloudee\Services\NextcloudService;
use Home\Cloudee\Services\WebdavService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
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
    private NextcloudService $nextcloudService;
    private WebdavService $webdavService;

    public function __construct()
    {
        $this->nextcloudService = new NextcloudService();
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
     * @throws FilesystemException
     */
    public function createShareWithGroup(Request $request): Application|ResponseFactory|Response
    {
        $groupid = $request->get('groupId');
        $path = $request->get('path');

        $this->nextcloudService->createGroup($groupid);
        $this->webdavService->createFolder("{$path}/{$groupid}");
        $this->nextcloudService->createShare("{$path}/{$groupid}", 1, $groupid);

        return \response();
    }

    /**
     * @param Request $request
     * @return Application|Response|ResponseFactory
     */
    public function getUser(Request $request): Response|Application|ResponseFactory
    {
        $response = $this->nextcloudService->getUser($request->userid);

        return \response($response->json());
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function deleteGroup(Request $request): Application|ResponseFactory|Response
    {
        $response = $this->nextcloudService->deleteGroup($request->groupid);

        return \response($response->json());
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function deleteUser(Request $request): Response|Application|ResponseFactory
    {
        $response = $this->nextcloudService->deleteUser($request->userid);

        return \response($response->json());
    }

    public function demoteUserFromSubAdmin(Request $request)
    {
        $response = $this->nextcloudService->demoteUserFromSubAdmin($request->userid, $request->groupid);

        return \response($response->json());
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     * @throws ValidationException
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

        $responses = $this->nextcloudService->updateUser($user);
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
     */
    public function getFolderContent(Request $request): Application|ResponseFactory|Response
    {
        return \response($this->webdavService->getFolderContent($request->dir, true));
    }

    public function promoteUserToSubAdmin(Request $request)
    {
        $response = $this->nextcloudService->promoteUserToSubAdmin($request->userid, $request->groupid);

        return \response($response->json());
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function removeUserFromGroup(Request $request): Application|ResponseFactory|Response
    {
        $response = $this->nextcloudService->removeUserFromGroup($request->userid, $request->groupid);

        return \response($response->json());
    }
}
