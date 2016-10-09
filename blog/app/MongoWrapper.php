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
use Psy\Util\Json;

class MongoWrapper
{
    //TODO sredi error handling kod komunikacije sa bazom

    private static $db;
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
        $user = $users->findOne(array('_id' => $username));
        if($user == null)
            return response(json_encode(null))->header('Content-Typge','application/json');
        $result= self::bson2JSON($user);
        return response($result)->header('Content-Type','application/json');
    }


    public static function usersGet()
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $result = self::bsonIterator2Array($users->find());
        return response($result)->header('Content-Type','application/json');
    }

    //TODO: testirati ovu fju dal radi
    public static function usersNear($user,$radius)
    {
        $db=self::getInstance();
        $onlineUsers = self::bsonIterator2Array( $db->selectCollection(self::$usersOnlineCollection));
//        $usersFriends = json_decode(self::bson2JSON(($currentUser['friends'])),true);
        $usersFriends = $user['friends'];
        $usersFriends = array_map(
                      function($friend) {return $friend['_id'];},
                      $usersFriends);
        $onlineUserIDs = array_map(
            function($friend) {return $friend['_id'];},
            $onlineUsers );
        $friendsToCheck = array_intersect($usersFriends,$onlineUserIDs);
        $near = array();
        foreach ($friendsToCheck as $friendID) {
            if (self::getDistance($user['latitude'],$user['longitude'],
                $onlineUsers[$friendID]['latitude'],$onlineUsers[$friendID]['longitude'])<$radius) {
                $near[]=$onlineUsers[$friendID];
            }
        }
        return response($near)->header('Content-Type','application/json');
    }

    //return online users
    public static function userGetOnlineUsers()
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersOnlineCollection);
        $result = self::bsonIterator2Array($users->find());
        return response($result)->header('Content-Type','application/json');
    }


    public static function usersAdd($newUser)
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $onlineUsers = $db->selectCollection(self::$usersOnlineCollection);
        //TODO: na kraju sifra
//        $newUser['password'] = password_hash($newUser['password'],PASSWORD_DEFAULT); //bcrypt
        $users->insertOne($newUser);
        $onlineUsers->insertOne($newUser);
        return response('true')->header('Content-Type', 'application/json');
    }

    public static function userUpdate($body)
    {
        $username=$body['_id'];
        $db = self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $body['percentage'] = number_format($body['percentage'],2);
        $users->updateOne(array('_id'=>$username),array('$set'=>$body));
        return response('true')->header('Content-Type', 'application/json');
    }

    public static function userUpdateOnline($body)
    {
        $db = self::getInstance();
        $user = $db->selectCollection(self::$usersOnlineCollection)->updateOne(array('_id' => $body['_id']),$body);
        return response('true')->header('Content-Type', 'application/json');
    }

    public static function userDelete($username)
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $users->deleteOne(array('_id'=>$username));
        return response('true')->header('Content-Type', 'application/json');
    }

    public static function userValidate($body)
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $user = $users->findOne(array('_id' => $body['_id']));
        if($user)
        {
            //TODO : vrati sifru
//            $pass = password_hash($body['password'],PASSWORD_DEFAULT);
            $pass = $body['password'];
            if($pass == $user['password'] )
            {
                $result = self::bson2JSON($user);
                return response($result)->header('Content-Type', 'application/json');
            }
            else
                return response(json_encode(false))->header('Content-Type', 'application/json');
        }
        return response(json_encode(false))->header('Content-Type', 'application/json');
    }

    public static function userAddFriend($f1,$f2)
    {
        $db = self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $u1 = $users->findOne(array('_id' => $f1));
        $u2 = $users->findOne(array('_id' => $f2));
        $users->updateOne(array('_id' => $f1), array('$push' => array('friends' => $u2)));
        $users->updateOne(array('_id' => $f2), array('$push' => array('friends' => $u1)));
        return response('true')->header('Content-Type', 'application/json');
    }

    public static function userComments($username)
    {
        $db = self::getInstance();
        $users = $db->selectCollection(self::$commentsCollection);
        $result = self::bsonIterator2Array($users->find(array('toUser' => $username)));
        return response($result)->header('Content-Type', 'application/json');
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
        $comments = $db->selectCollection(self::$commentsCollection);
        $result = self::bsonIterator2Array($comments->find());
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
                'percentage' => number_format($upvotes *100/($upvotes+$downvotes),2)
            );

            //for static array
            $online=$faker->boolean();
            $users->insertOne($user);
            if($online)
            {
                $onlineUsers->insertOne(array(
                    "_id" => $user['_id'],
                    "latitude" => 43.32,
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
        return response('true')->header('Content-Type', 'application/json');
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
    private static function bson2JSON($data)
    {
        return \MongoDB\BSON\toJSON(\MongoDB\BSON\fromPHP($data));
    }

    private static function bsonIterator2Array($array)
    {
        $result = self::bson2JSON(iterator_to_array($array));
        return json_decode($result, true);
    }

    //Haversine formula for distance between two points
    private static function getDistance($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $earth_radius = 6371;
        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2))
            * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;
        return $d;
    }

    private static function testProbes()
    {
        $db = self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
    }

    public static function generateProbesCollections()
    {
        $client = new Client();
        $db = $client->selectDatabase('FUNF');
        $probes = file_get_contents('../resources/funf_data/probes.csv');
        $probes = explode(PHP_EOL, $probes);
//
//        $probeArray = array();
//        foreach ($lines as $line) {
//            $probeArray[] = str_getcsv($line);
//        }
//        $probeArray = array_map(function($data))
//        dump($probes);die();
        foreach ($probes as $probe) {
            $db->createCollection($probe);
        }
        return response('true')->header('Content-Type', 'application/json');
    }

}