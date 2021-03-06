<?php

namespace App\Http\Controllers;

use App\MongoWrapper;
use App\Http\Requests;

class UserController extends Controller
{
    //users
    public function usersGet()
    {
        response('Content-Type:application/json');
        $allUsers = MongoWrapper::usersGet();
        return $allUsers;
    }

    public function usersNear()
    {
        return MongoWrapper::usersNear(request()->all(),request('radius'));
    }

    public function userGet($username)
    {
        return MongoWrapper::userGet($username);
    }

    public function userGetOnlineUsers()
    {
        return MongoWrapper::userGetOnlineUsers();
    }

    public function userUpdateStatus()
    {
        return MongoWrapper::userUpdateStatus(request()->all());
    }

    public function usersAdd()
    {
        return MongoWrapper::usersAdd(request()->all());
    }
//
//    public function userAddCity($username)
//    {
//        $body = request()->all();
//        $lat = $body['lat']; $long = $body['long'];
//        return MongoWrapper::userAddCity($username,$lat,$long);
//    }

//    public function userAddUpvote($username)
//    {
//        return MongoWrapper::userAddUpDownvote($username,true);
//    }
//
//    public function userAddDownvote($username)
//    {
//       return MongoWrapper::userAddUpDownvote($username,false);
//    }

    public function userUpdate()
    {
        return MongoWrapper::userUpdate(request()->all());
    }

    public function userValidate()
    {
        return MongoWrapper::userValidate(request()->all());
    }

//    public function userOnlineUpdate()
//    {
//        $body = request()->all();
//        return MongoWrapper::userUpdateOnline($body);
//    }

    public function userDelete($username)
    {
        return MongoWrapper::userDelete($username);
    }

    public function userComments($username)
    {
        return MongoWrapper::userComments($username);
    }

    public function userAddFriend()
    {
        return MongoWrapper::userAddFriend(request()->all(),request('friendId'));;
    }

    //cities
    public function citiesGet()
    {
        return MongoWrapper::citiesGet();
    }

    public function commentAdd()
    {
        return MongoWrapper::commentAdd(request()->all());
    }

    public function commentsGet()
    {
        return MongoWrapper::commentsGet();
    }

    public function testData()
    {
        return MongoWrapper::testData();
    }

    public function cleanData()
    {
        return MongoWrapper::cleanData();
    }

    public function generateProbesCollections()
    {
        return MongoWrapper::generateProbesCollections();
    }

    public function sendNotification()
    {
//        dump(request()->all());die();
        return MongoWrapper::sendNotification(request()->get('fromUserUsername'),request()->get('toUserUsername'),
            request()->request->get('bluetoothAdresa'));
    }
}