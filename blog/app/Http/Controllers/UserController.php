<?php

namespace App\Http\Controllers;

use App\MongoWrapper;
use \MongoDB\Client as Client;
//use MongoDB\MongoWrapper as MongoWrapper;
use App\Http\Requests;
use Faker;


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
        $user =  MongoWrapper::userGet($username);
        return $user;
    }

    public function userGetOnlineUsers()
    {
        return MongoWrapper::userGetOnlineUsers();
    }

    public function userUpdateStatus()
    {
        $body = request()->all();
        $username = $body['_id'];
        $status = $body['status'];
        return MongoWrapper::userUpdateStatus($username,$status);
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


    //comments

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
        $faker = Faker\Factory::create();
        $client = new Client();
        $db=$client->selectDatabase('TourApp');

        //users
        $users = $db->selectCollection('User');
        $addedUsers = array();
        for ($i = 1;$i <= 10;$i++)
        {
            $username=$faker->userName;
            $user = array(
                "_id" => $username,
                "password" => $faker->password(),
                "mail" => $faker->email,
                "name" => $faker->firstName,
                "surname" => $faker->lastName,
                "description" => $faker->text(30),
                "image" => '',
                'comments' => array(),
                'friends' => array(),
                'cities' => array(),
                'upvotes' => 0,
                'downvotes' => 0,
                'percentage' => 0
            );
            $users->insertOne($user);
            $addedUsers[]=$username;
        }

        //cities
        $cities=$db->selectCollection('City');
        $addedCities=array(
            array(
                '_id' => '43.3194_21.8963',
                'name' => 'Niš',
                'country' => 'Serbia',
                'latitude' => '43.3194',
                'longitude' => '21.8963'
            ),
            array(
                '_id' => '44.8206_20.4622',
                'name' => 'Belgrade',
                'country' => 'Serbia',
                'latitude' => '44.8206',
                'longitude' => '20.4622'
            ),
            array(
                '_id' => '51.5081_-0.128',
                'name' => 'London',
                'country' => 'UK',
                'latitude' => '51.5081',
                'longitude' => '-0.128'
            ),
            array(
                '_id' => '48.8566_2.3522',
                'name' => 'Paris',
                'country' => 'France',
                'latitude' => '48.8566',
                'longitude' => '2.3522'
            ),
            array(
                '_id' => '-33.8675_151.207',
                'name' => 'Sydney',
                'country' => 'Australia',
                'latitude' => '-33.8675',
                'longitude' => '151.207'
            )
        );
        foreach ($addedCities as $city)
            $cities->insertOne($city);

        //comments
        $comments=$db->selectCollection('Comment');
        $komentari=array();
        for ($i = 1;$i<=30;$i++)
        {
            $usernameFrom=$addedUsers[array_rand($addedUsers)];
            $usernameTo=$addedUsers[array_rand($addedUsers)];
            $fromUser =  $users->findOne(array('_id' => $usernameFrom))->_id;
            $comment= array(
                '_id' => $faker->dateTimeThisYear->getTimestamp()."_$fromUser",
                'content' => $faker->text($faker->numberBetween(20,50)),
                'toUser' => $usernameTo
            );
            $comments->insertOne($comment);
            $users->updateOne(array('_id' => $fromUser), array('$push' => array('comments' => $comment)));
            $komentari[] = $comment;
        }
        return array(true);
    }


}
