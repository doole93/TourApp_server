<?php
/**
 * Created by PhpStorm.
 * User: duce
 * Date: 19-Sep-16
 * Time: 19:50
 */

namespace App;

use \MongoDB\Client as Client;
use Faker;

class MongoWrapper
{
    //TODO cuvati staticku listu online korisnika(username,lat,long,online) ???
    //TODO sredi slike
    //TODO sredi error handling kod komunikacije sa bazom

    private static $db;
    private static $onlineUsers = array();
    private static $usersCollection = "Users";
    private static $usersOnlineCollection = "UsersOnline";
    private static $commentsCollection = "Comments";
    private static $citiesCollection = "Cities";


    private function __construct() { }

    private static function getInstance()
    {
        if(self::$db==null)
        {
            $client = new Client();
            self::$db = $client->selectDatabase('TourApp');
        }
            return self::$db;
    }


    public static function userGet($username)
    {
        $db=self::getInstance();
        $users=$db->selectCollection(self::$usersCollection);
        $result= self::bison2JSON($users->findOne(array('_id' => $username)));
        return response($result)->header('Content-Type','application/json');
    }

    public static function userGetFriends($username)
    {
        $db=self::getInstance();
        $users=$db->selectCollection(self::$usersCollection);
        $result= self::bison2JSON($users->findOne(array('_id' => $username))['friends']);
        return response($result)->header('Content-Type','application/json');
    }

    public static function usersGet()
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $result =  self::bison2JSON((iterator_to_array($users->find())));
        return response($result)->header('Content-Type','application/json');
    }

    public static function userUpdateStatus($body)
    {
        $id = $body['_id'];
        $db = self::getInstance();
        $user = $db->selectCollection(self::$usersCollection)->findOne(array('_id' => $id));

        $lat = $body['lat'];
        $long = $body['long'];
        $online = $body['online'];

        if(self::$onlineUsers[$id])
        {
            $onlineUsers[$id]['online'] = $online;
            $onlineUsers[$id]['online'] = $online;
            if($user['latitude'])
                $onlineUsers[$id]['latitude'] = $lat;
            if( $user['longitude'])
                $onlineUsers[$id]['longitude'] = $long;
        }
        else //nema ga u trenutnom nizu
        {
            $onlineUsers[] = array( '_id' => $id, 'latitude' => $lat, 'longitude' => $long, 'online' => $online );
        }
        return response(true)->header('Content-Type', 'application/json');
    }

    //return online users
    public static function userGetOnlineUsers()
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersOnlineCollection);
        $result =  self::bison2JSON((iterator_to_array($users->find())));
        return response($result)->header('Content-Type','application/json');
    }


    public static function userAdd($newUser)
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $users->insertOne($newUser);
        return response(true)->header('Content-Type', 'application/json');
    }

    public static function userAddCity($username,$lat,$long)
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $cityID=$lat . "_" . $long;
        $city = $db->selectCollection(self::$citiesCollection)->findOne(array('_id' =>$cityID ));
        $users->updateOne(array('_id' => $username),array('$push' => array('cities' => $city)));
        return response(true)->header('Content-Type', 'application/json');
    }

    public static function userAddUpDownvote($username,$upvote)
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $vote= $upvote ? 'upvotes' : 'downvotes';
        $users->updateOne(array('_id' => $username),array('$inc' => array("$vote" => 1)));
        return response(true)->header('Content-Type', 'application/json');
    }

    public static function userUpdate($username,$userData)
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $users->updateOne(array('_id'=>$username),array('$set'=>$userData));
        return response(true)->header('Content-Type', 'application/json');
    }

    public static function userDelete($username)
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $users->deleteOne(array('_id'=>$username));
        return response(true)->header('Content-Type', 'application/json');
    }

    public static function userComments($username)
    {
        $db=self::getInstance();
        $users=$db->selectCollection(self::$usersCollection);
        $result= self::bison2JSON($users->findOne(array('_id' => $username))->comments);
        return response($result)->header('Content-Type','application/json');
    }

    public static function userAddFriend($f1,$f2)
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $u1=$users->findOne(array('_id' => $f1));
        $u2=$users->findOne(array('_id' => $f2));
        $users->updateOne(array('_id' => $f2), array('$push' => array('friends' => $u1)));
        $users->updateOne(array('_id' => $f1), array('$push' => array('friends' => $u2)));
        return response(true)->header('Content-Type', 'application/json');
    }


    //cities
    public static function citiesGet()
    {
        $db=self::getInstance();
        $cities = $db->selectCollection(self::$citiesCollection);
        return iterator_to_array($cities->find());
    }

    //comments
    public static function commentAdd($data)
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $comments = $db->selectCollection(self::$commentsCollection);
        $comment=array(
            '_id' => time() . '_' . $data['from'],
            'content' => $data['content'],
            'toUser' => $data['to']
        );
        $comments->insertOne($comment);
        $users->updateOne(array('_id' => $data['from']), array('$push' => array('comments' => $comment)));
        return response("true")->header('Content-Type', 'application/json');
    }

    public static function commentsGet()
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$commentsCollection);
        $result =  self::bison2JSON((iterator_to_array($users->find())));
        return response($result)->header('Content-Type','application/json');
    }

    public static function testData()
    {
        $faker = Faker\Factory::create();
        $client = new Client();
        $db=$client->selectDatabase('TourApp');

        //users
        $users = $db->selectCollection(self::$usersCollection);
        $onlineUsers=$db->selectCollection(self::$usersOnlineCollection);
        $addedUsers = array();
        for ($i = 1;$i <= 10;$i++)
        {
            $userObject = new User();
            $username=$faker->userName;
            $upvotes=$faker->numberBetween(1,10);
            $downvotes=$faker->numberBetween(1,10);
            $image=file_get_contents('../resources/images/avatars/'.$i.'.jpg');
            $imagePrepared=base64_encode($image);
            $user = array(
                "_id" => $username,
                "password" => $faker->password(),
                "mail" => $faker->email,
                "name" => $faker->firstName,
                "surname" => $faker->lastName,
                "description" => $faker->text(30),
                "image" => $imagePrepared,
                'comments' => array(),
                'friends' => array(),
                'cities' => array(),
                'upvotes' => $upvotes,
                'downvotes' => $downvotes,
                'percentage' => number_format($upvotes*100/($upvotes+$downvotes),2)
            );

            //for static array
            $online=$faker->boolean();
            $users->insertOne($user);
            if($online)
            {
                $onlineUsers->insertOne(array(
                    "_id" => $user['_id'],
                    "_latitude" => 43.32,
                    "longitude" => 21.89
                ));
            }
            $addedUsers[] = $username;
        }

        //cities
        $cities=$db->selectCollection(self::$citiesCollection);
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
        $comments=$db->selectCollection(self::$commentsCollection);
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

    /**
     * clean data
     * @return mixed
     */
    public static function cleanData()
    {
        $db = self::getInstance();
        $db->selectCollection(self::$usersCollection)->deleteMany(array());
        $db->selectCollection(self::$usersOnlineCollection)->deleteMany(array());
        $db->selectCollection(self::$citiesCollection)->deleteMany(array());
        $db->selectCollection(self::$commentsCollection)->deleteMany(array());
        return response('true')->header('Content-Type', 'application/json');
    }

    //helper
    private static function bison2JSON($data)
    {
        return \MongoDB\BSON\toJSON(\MongoDB\BSON\fromPHP($data));
    }
}
