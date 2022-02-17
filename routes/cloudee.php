<?php

use Illuminate\Support\Facades\Route;
use Home\Cloudee\Http\Controllers\CloudeeController;

Route::group(['middleware' => ['json.response', 'auth:api']], function () {
    Route::delete('/api/cloudee/user/{userid}', [CloudeeController::class, 'deleteUser'])->name('delete.user.cloudee');
    Route::get('/api/cloudee/user/{userid}', [CloudeeController::class, 'getUser'])->name('get.user.cloudee');
    Route::post('/api/cloudee/user/create', [CloudeeController::class, 'createUser'])->name('create.user.cloudee');
    Route::put('/api/cloudee/user', [CloudeeController::class, 'updateUser'])->name('update.user.cloudee');
    Route::post('/api/cloudee/group/demote', [CloudeeController::class, 'demoteUserFromSubAdmin'])->name('demote.group.cloudee');
    Route::post('/api/cloudee/group/promote', [CloudeeController::class, 'promoteUserToSubAdmin'])->name('promote.group.cloudee');
    Route::delete('/api/cloudee/group', [CloudeeController::class, 'deleteGroup'])->name('delete.group.cloudee');
    Route::delete('/api/cloudee/group/user', [CloudeeController::class, 'removeUserFromGroup'])->name('removeUser.group.cloudee');
    Route::get('/api/cloudee/storage/folder', [CloudeeController::class, 'getFolderContent'])->name('get.storage.cloudee');
    Route::get('/api/cloudee/storage/download', [CloudeeController::class, 'downloadFile'])->name('download.storage.cloudee');
    Route::post('/api/cloudee/share/create-with-group', [CloudeeController::class, 'createShareWithGroup'])->name('create.user.cloudee');
});
