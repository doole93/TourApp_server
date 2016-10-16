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

//REST API routes

//users
//Route::get('/users/{username}/friends','UserController@userGetFriends');

Route::get('/users','UserController@usersGet');
Route::post('/users','UserController@usersAdd');
Route::put('/users','UserController@userUpdate');
Route::get('/users/online','UserController@userGetOnlineUsers');
Route::get('/users/{username}','UserController@userGet');
Route::post('/usersNear','UserController@usersNear');
Route::post('/users/validate','UserController@userValidate');
Route::post('/users/addFriend','UserController@userAddFriend');
Route::delete('/users/{username}','UserController@userDelete');


//comments
Route::get('/comments','UserController@commentsGet');
Route::get('/comments/{username}','UserController@userComments');
Route::post('/comments','UserController@commentAdd');

//cities
Route::get('/cities','UserController@citiesGet');

//test data
Route::get('/testData','UserController@testData');

//clean data
Route::get('/cleanData','UserController@cleanData');

//online users

//funf data
Route::get('/generateProbes','UserController@generateProbesCollections');



