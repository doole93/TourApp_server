<?php
/**
 * Created by PhpStorm.
 * User: duce
 * Date: 19-Sep-16
 * Time: 19:50
 */

namespace App;

use Davibennun\LaravelPushNotification\Facades\PushNotification;
use League\Flysystem\Exception;
use \MongoDB\Client as Client;
use Faker;

class MongoWrapper
{
    //TODO funf baza i slozeni upiti
    private static $db;
    private static $usersCollection = "Users";
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


    //proveri da vec nije online - zbog logovanja sa vise mesta
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
    public static function usersNear($user, $radius)
    {
//        dump($user);dump($radius);die();
        $db=self::getInstance();
        $onlineUsers = self::bsonIterator2Array($db->selectCollection(self::$usersCollection)
                        ->find(array('online' => true)));
//        dump($onlineUsers);die();
        foreach ($onlineUsers as $onlineUser) {
//            dump($onlineUser);die();
            if($onlineUser['_id'] != $user['_id']) {
                $distance = self::haversineGreatCircleDistance($user['latitude'],$user['longitude'],
                    $onlineUser['latitude'],$onlineUser['longitude']);
//            dump($distance);die();
                if ($distance<$radius) {
                    $near[]=$onlineUser;
                }
            }
        }
        dump($near);die();
        return response($near)->header('Content-Type','application/json');
    }

    public static function friendsNear($user, $radius)
    {
        $db=self::getInstance();
        $onlineUsers = self::bsonIterator2Array($db->selectCollection(self::$usersCollection)
            ->find(array('online' => true)));
        $usersFriendsIDs = array_map(
            function($friend) {return $friend['_id'];},
            $user['friends']);
        $onlineUserIDs = array_map(
            function($friend) {return $friend['_id'];},
            $onlineUsers );
        $onlineUsers = array_combine($onlineUserIDs,$onlineUsers);
        $friendsToCheck = array_intersect($usersFriendsIDs,$onlineUserIDs);
        $near = array();
        foreach ($friendsToCheck as $friendID) {
            $distance = self::haversineGreatCircleDistance($user['latitude'],$user['longitude'],
                $onlineUsers[$friendID]['latitude'],$onlineUsers[$friendID]['longitude']);
            if ($distance<$radius) {
                $near[]=$onlineUsers[$friendID];
            }
        }
        return response($near)->header('Content-Type','application/json');
    }

    public static function userAddFriend($user,$friendId)
    {
        $user['friends'][]=$friendId;
        $db = self::getInstance();
        $db->selectCollection(self::$usersCollection)
            ->updateOne(array('_id'=>$user['_id']),array('$set'=>$user));
        return response('true')->header('Content-Type', 'application/json');
    }

    //return online users
    public static function userGetOnlineUsers()
    {
//        $db=self::getInstance();
//        $users = $db->selectCollection(self::$usersOnlineCollection);
//        $result = self::bsonIterator2Array($users->find());
//        return response($result)->header('Content-Type','application/json');
        $db = self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        $users = $users->find(array('online' => true));
        $result = self::bsonIterator2Array($users);
        return response($result)->header('Content-Type','application/json');
    }


    public static function usersAdd($newUser)
    {
        $db=self::getInstance();
        $users = $db->selectCollection(self::$usersCollection);
        //TODO: na kraju sifra
//        $newUser['password'] = password_hash($newUser['password'],PASSWORD_DEFAULT); //bcrypt
        $users->insertOne($newUser);
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

//    public static function userUpdateOnline($body)
//    {
//        $db = self::getInstance();
//        $user = $db->selectCollection(self::$usersOnlineCollection)->updateOne(array('_id' => $body['_id']),$body);
//        return response('true')->header('Content-Type', 'application/json');
//    }

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
                $user['online'] = true;
                $result = self::bson2JSON($user);
                return response($result)->header('Content-Type', 'application/json');
            }
            else
                return response(json_encode(false))->header('Content-Type', 'application/json');
        }
        return response(json_encode(false))->header('Content-Type', 'application/json');
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
        $db = self::getInstance();
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
        $db = self::getInstance();

        //users
        $users = $db->selectCollection(self::$usersCollection);
        $addedUsers = array();
        $addedUsersObjects = array();
        for ($i = 1;$i <= 20;$i++)
        {
            $username=$faker->userName;
            $upvotes=$faker->numberBetween(1,10);
            $downvotes=$faker->numberBetween(1,10);
            $image=file_get_contents('../resources/images/avatars/'.$i.'.jpg');
            $imagePrepared=base64_encode($image);
            $latRandom = $faker->numberBetween(242202,406045);
            $longRandom = $faker->numberBetween(830177,994972);
            $user = array(
                "_id" => $username,
                "password" => $faker->password(),
                "guide" => $faker->boolean(),
                "online" => $faker->boolean(),
                "latitude" => 42+$latRandom/1000000,
                "longitude" => 21+$longRandom/1000000,
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
                'percentage' => number_format($upvotes *100/($upvotes+$downvotes),2),
                'tokenFB' => ''
            );
            $addedUsers[] = $username;
            $addedUsersObjects[$username] = $user;
        }

        //fill friends
        foreach ($addedUsersObjects as $userObject) {
            $numFriends = $faker->numberBetween(1,19);
            for($i=0; $i<$numFriends;$i++) {
                do
                    $friend = array_rand($addedUsers);
                while ($friend == $userObject['_id'] || in_array($addedUsers[$friend],$userObject['friends']));
                $userObject['friends'][] = $addedUsers[$friend];
            }
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
        for ($i = 1;$i<=30;$i++)
        {
            $fromUser=$addedUsers[array_rand($addedUsers)];
            do
                $usernameTo=$addedUsers[array_rand($addedUsers)];
            while($fromUser==$usernameTo);

            $comment= array(
                '_id' => $faker->dateTimeThisYear->getTimestamp()."_$fromUser",
                'content' => $faker->text($faker->numberBetween(20,50)),
                'toUser' => $usernameTo
            );
            $comments->insertOne($comment);
//            $users->updateOne(array('_id' => $fromUser), array('$push' => array('comments' => $comment)));
//            $komentari[] = $comment;
            $addedUsersObjects[$usernameTo]['comments'][] = $comment;
        }

        foreach ($addedUsersObjects as $userObject){
            $users->insertOne($userObject);
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
        $db->selectCollection(self::$citiesCollection)->deleteMany(array());
        $db->selectCollection(self::$commentsCollection)->deleteMany(array());
        return response('true')->header('Content-Type', 'application/json');
    }

    //helper
    private static function bson2JSON($data)
    {
        return \MongoDB\BSON\toJSON(\MongoDB\BSON\fromPHP($data));
    }

    private static function bsonIterator2Array($iterator)
    {
        $result = self::bson2JSON(iterator_to_array($iterator));
        return json_decode($result, true);
    }

    //Haversine formula for distance between two points - km???
    private static function haversineGreatCircleDistance($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $earth_radius = 6371;
        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2))
            * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;
//        $d =  $d * 1000; //u metrima
//        $d = (int) $d;
        return $d;
    }

//    public static function haversineGreatCircleDistance(
//        $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
//    {
//        // convert from degrees to radians
//        $latFrom = deg2rad($latitudeFrom);
//        $lonFrom = deg2rad($longitudeFrom);
//        $latTo = deg2rad($latitudeTo);
//        $lonTo = deg2rad($longitudeTo);
//
//        $latDelta = $latTo - $latFrom;
//        $lonDelta = $lonTo - $lonFrom;
//
//        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
//                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
//        return ($angle * $earthRadius)/1000000;
//    }

    public static function sendNotification($fromUserUsername, $toUserUsername, $bluetoothAdresa)
    {
        try{
            $db=self::getInstance();
            $toUser = $db->selectCollection(self::$usersCollection)->findOne(array('_id' => $toUserUsername));
            $message = PushNotification::Message('poruka', array(
                'poruka' => $fromUserUsername . " wants to be your friend :)",
                'fromUserUsername' => $fromUserUsername,
                'bluetoothAdresa' => $bluetoothAdresa
            ));
            PushNotification::app('TourApp')
                ->to($toUser['tokenFB'])
                ->send($message);
        } catch (Exception $e) {
            return response($e->getMessage())->header('Content-Type', 'application/json');
        }
        return response('true')->header('Content-Type', 'application/json');
    }
}