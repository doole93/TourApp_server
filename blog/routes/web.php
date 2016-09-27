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
// TODO: vracanje prijatelja (mozda i nije potrebno, posto se ucitavanjem usera ucitavaju i svi njegovi prijatelji)

Route::get('/users','UserController@usersGet');
Route::get('/users/{username}','UserController@userGet');
Route::post('/users','UserController@userAdd');
Route::put('/users/{username}','UserController@userUpdate');
Route::delete('/users/{username}','UserController@userDelete');
Route::put('/users/{username}/addCity','UserController@userAddCity');

Route::put('/users','UserController@userAddFriend');
Route::put('/users/{username}/upvote','UserController@userAddUpvote');
Route::put('/users/{username}/downvote','UserController@userAddDownvote');

// TODO: mozda treba i da se izbaci vracanje komentara korisnika (posto se ucitavanjem usera ucitavaju i svi njegovi komentari

//comments
//Route::get('/comments/{username}','UserController@userComments');
Route::get('/comments','UserController@commentsGet');
Route::post('/comments/add','UserController@commentAdd');

//cities
Route::get('/cities','UserController@citiesGet');

//test data
Route::get('/testData','UserController@testData');

//clean data
Route::get('/cleanData','UserController@cleanData');

//online users
Route::get('/usersOnline','UserController@userGetOnlineUsers');


