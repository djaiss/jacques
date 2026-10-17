<?php

declare(strict_types=1);

use App\Actions\ResizeImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('writes a webp at the size it was asked for', function () {
    Storage::fake('local');

    $path = new ResizeImage(
        file: UploadedFile::fake()->image('dwight.jpg', 400, 400),
        width: 96,
        height: 96,
        path: 'avatars/1',
        name: 'beets.webp',
        disk: 'local',
    )->execute();

    expect($path)->toEqual('avatars/1/beets.webp');

    Storage::disk('local')->assertExists($path);

    $size = getimagesizefromstring((string) Storage::disk('local')->get($path));

    expect($size[0])->toEqual(96);
    expect($size[1])->toEqual(96);
    expect($size['mime'])->toEqual('image/webp');
});

it('crops a rectangle to the box rather than squashing it', function () {
    Storage::fake('local');

    $path = new ResizeImage(
        file: UploadedFile::fake()->image('michael.jpg', 800, 200),
        width: 96,
        height: 96,
        path: 'avatars/1',
        name: 'boss.webp',
        disk: 'local',
    )->execute();

    $size = getimagesizefromstring((string) Storage::disk('local')->get($path));

    expect($size[0])->toEqual(96);
    expect($size[1])->toEqual(96);
});

it('refuses a width that is not positive', function () {
    new ResizeImage(
        file: UploadedFile::fake()->image('pam.jpg'),
        width: 0,
        height: 96,
        path: 'avatars/1',
        name: 'art.webp',
        disk: 'local',
    )->execute();
})->throws(InvalidArgumentException::class);

it('refuses a height that is not positive', function () {
    new ResizeImage(
        file: UploadedFile::fake()->image('jim.jpg'),
        width: 96,
        height: -10,
        path: 'avatars/1',
        name: 'prank.webp',
        disk: 'local',
    )->execute();
})->throws(InvalidArgumentException::class);
