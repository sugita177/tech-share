<?php

use Illuminate\Support\Facades\Route;

// API以外のすべてのリクエストを、先ほど作った app.blade.php に集約する
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');