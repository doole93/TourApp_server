<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/users','UserController@allUsers');
Route::get('/users/{id}','UserController@user');
Route::get('/cities','UserController@allCities');
Route::get('/comments','UserController@allComments');
Route::get('/testPodaci','UserController@testData');


