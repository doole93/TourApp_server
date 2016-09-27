<?php
/**
 * Created by PhpStorm.
 * User: duce
 * Date: 19-Sep-16
 * Time: 19:50
 */

namespace App;

use \MongoDB\Client as Client;
use \MongoDB\Driver\Exception\Exception as MongoEx;

//TODO sredi error handling kod komunikacije sa bazom

class MongoWrapper
{
    private static $db;
    private static $onlineUsers;

    private function __construct() { }

    private static function getInstance()
    {
        if(self::$db==null)
        {
            $client = new Client();
            self::$onlineUsers = array();
            self::$db = $client->selectDatabase('TourApp');
        }
            return self::$db;
    }


    public static function userGet($username)
    {
        $db=self::getInstance();
        $users=$db->selectCollection('User');
        $result= self::Bison2JSON($users->findOne(array('_id' => $username)));
        return response($result)->header('Content-Type','application/json');
    }

    public static function userGetFriends($username)
    {
        $db=self::getInstance();
        $users=$db->selectCollection('User');
        $result= self::Bison2JSON($users->findOne(array('_id' => $username))['friends']);
        return response($result)->header('Content-Type','application/json');
    }

    public static function usersGet()
    {
        $db=self::getInstance();
        $users = $db->selectCollection('User');
        $result =  self::Bison2JSON((iterator_to_array($users->find())));
        return response($result)->header('Content-Type','application/json');
    }

    public static function userUpdateStatus($body,$online)
    {
        $id = $body['_id'];
        $db = self::getInstance();
        $users = $db->selectCollection('User');
        $user= self::Bison2JSON($users->findOne(array('_id' => $id)));

        $status = $body['status'];
        $lat = $body['lat'];
        $long = $body['long'];

        if(self::$onlineUsers[$id])
        {
            $onlineUsers[$id]['online'] = $online;
            $onlineUsers[$id]['status'] = $status;
            if($user['latitude'])
                $onlineUsers[$id]['latitude'] = $lat;
            if( $user['longitude'])
                $onlineUsers[$id]['longitude'] = $long;
        }
        else //nema ga u trenutnom nizu
        {
            $onlineUsers[] = array( '_id' => $id, 'latitude' => $lat, 'longitude' => $long, 'status' => $status );
        }
        return response(true)->header('Content-Type', 'application/json');
    }

    //return online users
    public static function userGetOnlineUsers()
    {
        $online=array();
        foreach (self::$onlineUsers as $user)
        {
            if($user['online'])
                $online[] = $user;
        }
        return $online;
    }


    public static function userAdd($newUser)
    {
        $db=self::getInstance();
        $users = $db->selectCollection('User');
        $users->insertOne($newUser);
        return array(true);
    }

    public static function userAddCity($username,$lat,$long)
    {
        $db=self::getInstance();
        $users = $db->selectCollection('User');
        $cityID=$lat . "_" . $long;
        $city = $db->selectCollection('City')->findOne(array('_id' =>$cityID ));
        $users->updateOne(array('_id' => $username),array('$push' => array('cities' => $city)));
        return array(true);
    }

    public static function userAddUpDownvote($username,$upvote)
    {
        $db=self::getInstance();
        $users = $db->selectCollection('User');
        $vote= $upvote ? 'upvotes' : 'downvotes';
        $users->updateOne(array('_id' => $username),array('$inc' => array("$vote" => 1)));
        return array(true);
    }

    public static function userUpdate($username,$userData)
    {
        $db=self::getInstance();
        $users = $db->selectCollection('User');
        $users->updateOne(array('_id'=>$username),array('$set'=>$userData));
        return array(true);
    }

    public static function userDelete($username)
    {
        $db=self::getInstance();
        $users = $db->selectCollection('User');
        $users->deleteOne(array('_id'=>$username));
        return array(true);
    }

    public static function userComments($username)
    {
        $db=self::getInstance();
        $users=$db->selectCollection('User');
        $result= self::Bison2JSON($users->findOne(array('_id' => $username))->comments);
        return response($result)->header('Content-Type','application/json');
    }

    public static function userAddFriend($f1,$f2)
    {
        $db=self::getInstance();
        $users = $db->selectCollection('User');
        $u1=$users->findOne(array('_id' => $f1));
        $u2=$users->findOne(array('_id' => $f2));
        $users->updateOne(array('_id' => $f2), array('$push' => array('friends' => $u1)));
        $users->updateOne(array('_id' => $f1), array('$push' => array('friends' => $u2)));
        return array(true);
    }


    //cities
    public static function citiesGet()
    {
        $db=self::getInstance();
        $cities = $db->selectCollection('City');
        return iterator_to_array($cities->find());
    }

    //comments
    public static function commentAdd($data)
    {
        $db=self::getInstance();
        $users = $db->selectCollection('User');
        $comments = $db->selectCollection('Comment');
        $comment=array(
            '_id' => time() . '_' . $data['from'],
            'content' => $data['content'],
            'toUser' => $data['to']
        );
        $comments->insertOne($comment);
        $users->updateOne(array('_id' => $data['from']), array('$push' => array('comments' => $comment)));
        return array(true);
    }

    public static function commentsGet()
    {
        $db=self::getInstance();
        $users = $db->selectCollection('Comment');
        $result =  self::Bison2JSON((iterator_to_array($users->find())));
        return response($result)->header('Content-Type','application/json');
    }


    //helper
    private static function Bison2JSON($data)
    {
        return \MongoDB\BSON\toJSON(\MongoDB\BSON\fromPHP($data));
    }
}