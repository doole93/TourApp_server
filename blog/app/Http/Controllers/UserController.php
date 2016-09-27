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


    public function userGetFriends($username)
    {
        return MongoWrapper::userGetFriends($username);
    }

    public function userAdd()
    {
        $newUser = request()->all();
        return MongoWrapper::userAdd($newUser);
    }

    public function userAddCity($username)
    {
        $body = request()->all();
        $lat = $body['lat']; $long = $body['long'];
        return MongoWrapper::userAddCity($username,$lat,$long);
    }

    public function userAddUpvote($username)
    {
        return MongoWrapper::userAddUpDownvote($username,true);
    }

    public function userAddDownvote($username)
    {
       return MongoWrapper::userAddUpDownvote($username,false);
    }

    public function userUpdate($username)
    {
        $body = request()->all();
        return MongoWrapper::userUpdate($username,$body);
    }

    public function userOnlineUpdate()
    {
        $body = request()->all();
        return MongoWrapper::userUpdateOnline($body);
    }

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
        $body=request()->all();
        $f1=$body['f1'];
        $f2=$body['f2'];
        return MongoWrapper::userAddFriend($f1,$f2);

    }

    //cities

    public function citiesGet()
    {
        return MongoWrapper::citiesGet();
    }



    /**
     * comments
     * @return array
     */
    public function commentAdd()
    {
        return MongoWrapper::commentAdd(request()->all());
    }
    public function commentsGet()
    {
        return MongoWrapper::commentsGet();
    }


    /**
     * test data
     * @return array
     */
    public function testData()
    {
        return MongoWrapper::testData();
    }

    /**
     * clean data
     * @return mixed
     */
    public function cleanData()
    {
        return MongoWrapper::cleanData();
    }


}
