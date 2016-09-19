<?php

namespace App\Http\Controllers;

use \MongoDB\Client as Client;
//use MongoDB\MongoWrapper as MongoWrapper;
use App\Http\Requests;
use Faker;


class UserController extends Controller
{
    //users
    public function usersGet()
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $userCollection = $db->selectCollection('User');
        $allUsers = iterator_to_array($userCollection->find());
        return $allUsers;
    }

    public function userGet($username)
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $userCollection = $db->selectCollection('User');
        $user = $userCollection->findOne(array('_id' =>$username ));
        return $user;
    }


    public function userGetFriends($username)
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $users = $db->selectCollection('User');
        $userFriends = $users->findOne(array('_id' =>$username ))->friends;
        return $userFriends;
    }

    public function userAdd()
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $users = $db->selectCollection('User');
        $newUser = request()->all();
        $users->insertOne($newUser);
        return array(true);
    }

    public function userAddCity($username)
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $users = $db->selectCollection('User');
        $body = request()->all();
        $lat = $body['lat']; $long = $body['long'];
        $cityID=$lat . "_" . $long;
        $city = $db->selectCollection('City')->findOne(array('_id' =>$cityID ));
        $users->updateOne(array('_id' => $username),array('$push' => array('cities' => $city)));
        return array(true);
    }

    public function userAddUpvote($username)
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $users = $db->selectCollection('User');
        $users->updateOne(array('_id' => $username),array('$inc' => array('upvotes' => 1)));
        return array(true);
    }

    public function userAddDownvote($username)
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $users = $db->selectCollection('User');
        $users->updateOne(array('_id' => $username),array('$inc' => array('downvotes' => 1)));
        return array(true);
    }

    public function userUpdate($username)
    {
        $client = new Client();
        $userData = request()->all();
        $db = $client->selectDatabase('TourApp');
        $users = $db->selectCollection('User');
        $users->updateOne(array('_id'=>$username),array('$set'=>$userData));
        return array(true);
    }

    public function userDelete($username)
    {
        $client = new Client();
        $userData = request()->all();
        $db = $client->selectDatabase('TourApp');
        $users = $db->selectCollection('User');
        $users->deleteOne(array('_id'=>$username));
        return array(true);
    }


    public function userComments($username)
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $userCollection = $db->selectCollection('User');
        $userComments = $userCollection->findOne(array('_id' =>$username ))->comments;
        return $userComments;
    }

    public function userAddFriend()
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $users = $db->selectCollection('User');
        $body=request()->all();
        $userFromID=$body['userFromID'];
        $userToID=$body['userToID'];
        $userFrom = $users->findOne(array('_id' =>$userFromID ));
        $userTo = $users->findOne(array('_id' =>$userToID ));
        //do ovde je dobro
        $users->updateOne(array('_id' => $userFrom), array('$push' => array('friends' => array('_id' => $userToID))));
        $users->updateOne(array('_id' => $userTo), array('$push' => array('friends' => array('_id' => $userFromID))));
        return array(true);
    }

    //cities

    public function citiesGet()
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $cityCollection = $db->selectCollection('City');
        $allCities = iterator_to_array($cityCollection->find());
        return $allCities;
    }


    //comments

    public function commentAdd()
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $users = $db->selectCollection('User');
        $comments = $db->selectCollection('Comment');
        $body = request()->all();
        $comment=array(
            '_id' => time() . '_' . $body['from'],
            'content' => $body['content'],
            'toUser' => $body['to']
        );
        $comments->insertOne($comment);
        $users->updateOne(array('_id' => $body['from']), array('$push' => array('comments' => $comment)));
        return array(true);
    }
    public function commentsGet()
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $commentCollection = $db->selectCollection('Comment');
        $allCities = iterator_to_array($commentCollection->find());
        return $allCities;
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
            $users->updateOne(array('_id' => $username), array('$push' => array('comments' => $comment)));
            $komentari[] = $comment;
        }
        return array(true);
    }


}
