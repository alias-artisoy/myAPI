<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('home');
});

Route::group(['prefix' => 'api/v1'], function(){

    Route::resource('meeting', 'MeetingController',[
        'except' => ['edit','create']
    ]);

    Route::resource('meeting/registration', 'RegistrationController',[
        'only' => ['store','destroy']
    ]);

    Route::post('user', [
        'uses' => 'AuthController@store'
    ]);

    Route::post('user/signin', [
        'uses' => 'AuthController@signin'
    ]);

});
