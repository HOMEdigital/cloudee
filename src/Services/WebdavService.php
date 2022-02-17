<?php

namespace Home\Cloudee\Services;


use Illuminate\Support\Arr;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

class WebdavService
{
    private Filesystem $filesystem;
    private string $basePath;

    public function __construct()
    {
        $client = new Client([
            'baseUri' => config('cloudee.webdav.url'),
            'userName' => config('cloudee.webdav.user'),
            'password' => config('cloudee.webdav.password'),
        ]);
        $adapter = new WebDAVAdapter($client);
        $this->filesystem = new Filesystem($adapter);

        $this->basePath = config('cloudee.webdav.basePath');
    }

    /**
     * @param string $path
     * @param bool $addBasePath
     * @return void
     */
    public function createFolder(string $path, bool $addBasePath = true): void
    {
        $path = $addBasePath ? $this->basePath . $path : $path;

        try{
            $this->filesystem->createDirectory($path);
        } catch (FilesystemException | UnableToCreateDirectory $exception) {
            dd($exception);
        }
    }

    /**
     * @param string $path
     * @param bool $addBasePath
     * @return resource
     * @throws FilesystemException
     */
    public function downloadFile(string $path, bool $addBasePath = true)
    {
        $path = $addBasePath ? $this->basePath . $path : $path;
        return $this->filesystem->readStream($path);
    }

    /**
     * @param string $path
     * @param bool $recursive
     * @return array
     * @throws FilesystemException
     */
    public function getFolderContent(string $path, bool $recursive = true): array
    {
        $content = [];
        $client = new Client([
            'baseUri' => config('cloudee.nextcloud.url'),
            'userName' => config('cloudee.webdav.user'),
            'password' => config('cloudee.webdav.password'),
        ]);
        $adapter = new WebDAVAdapter($client);
        $this->filesystem = new Filesystem($adapter);

        $listing = $this->filesystem->listContents($path, $recursive)->sortByPath();
        $basePathLength = 0;
        foreach ($listing as $item) {
            $splitPath = explode('/', $item['path']);
            $id = Arr::last($splitPath);
            $content[$id] = $item->jsonSerialize();
            $content[$id]['id'] = $id;

            if ($item['type'] === 'dir') {
                $content[$id]['children'] = [];
                if($basePathLength === 0 || $basePathLength === count($splitPath)){
                    $basePathLength = count($splitPath);
                    $content[$id]['parent_id'] = '0';
                }else{
                    $tmp = $content[$id];
                    $content[$id]['parent_id'] = Arr::last($splitPath, function($value, $key) use ($tmp) {
                        return $value !== $tmp['id'];
                    });
                }
            } else {
                $content[$id]['parent_id'] = Arr::last($splitPath, function($value, $key) {
                    return !str($value)->contains('.');
                });
            }

        }

        return $this->buildTree($content);
    }

    /**
     * @param string $path
     * @param bool $addBasePath
     * @return void
     * @throws FilesystemException
     */
    public function deleteFolder(string $path, bool $addBasePath = true)
    {
        $path = $addBasePath ? $this->basePath . $path : $path;
        $this->filesystem->deleteDirectory($path);
    }

    /**
     * @param string $path
     * @param bool $addBasePath
     * @return bool
     * @throws FilesystemException
     */
    public function folderExists(string $path, bool $addBasePath = true): bool
    {
        $path = $addBasePath ? $this->basePath . $path : $path;
        return $this->filesystem->directoryExists($path);
    }

    /**
     * @param array $elements
     * @param string $parentId
     * @return array
     */
    public function buildTree(array $elements, string $parentId = '0'): array
    {
        $branch = array();
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
}
