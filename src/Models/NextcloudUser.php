<?php

namespace Home\Cloudee\Models;


use Home\Cloudee\Services\NextcloudService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;

class NextcloudUser
{
    public string $userid;
    public string $password;
    public string $displayName;
    public string $email;
    public string $language;

    protected NextcloudService $nextcloudService;

    public function __construct()
    {
        $this->nextcloudService = new NextcloudService();
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $displayName
     * @param string $email
     * @param string $language
     * @return Response
     */
    public function create(string $username, string $password, string $displayName, string $email, string $language = 'de'): Response
    {
        $this->userid = $username;
        $this->password = $password;
        $this->displayName = $displayName;
        $this->email = $email;
        $this->language = $language;

        return $this->nextcloudService->createUser($this);
    }

    /**
     * find user by id and return model
     *
     * @param string $userid
     * @return $this
     */
    public function find(string $userid): NextcloudUser
    {
        $response = $this->nextcloudService->getUser($userid);
        $this->userid = Arr::get($response, 'ocs.data.id');
        $this->displayName = Arr::get($response, 'ocs.data.displayName');
        $this->email = Arr::get($response, 'ocs.data.email');
        $this->language = Arr::get($response, 'ocs.data.language');

        return $this;
    }

    /**
     * set properties from array and return $this
     *
     * @param array $arr
     * @return $this
     */
    public function fromArray(array $arr): NextcloudUser
    {
        $this->userid = Arr::get($arr, 'userid');
        $this->password = Arr::get($arr, 'password', '');
        $this->displayName = Arr::get($arr, 'displayName');
        $this->email = Arr::get($arr, 'email');
        $this->language = Arr::get($arr, 'language', 'de');

        return $this;
    }

    /**
     * get array from $this
     *
     * @return array
     */
    public function toArray(): array
    {
        return array(
            'userid' => $this->userid,
            'password' => $this->password,
            'displayName' => $this->displayName,
            'email' => $this->email,
            'language' => $this->language,
        );
    }
}
