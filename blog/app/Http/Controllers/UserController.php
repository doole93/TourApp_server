<?php

namespace App\Http\Controllers;

use \MongoDB\Client as Client;
use App\Http\Requests;
use Faker;
use MongoDB\Model\BSONDocument;


class UserController extends Controller
{
    //
    public function allUsers()
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $userCollection = $db->selectCollection('User');
        $allUsers = iterator_to_array($userCollection->find());
        return $allUsers;
    }

    public function user($username)
    {

        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $userCollection = $db->selectCollection('User');
        $user = $userCollection->findOne(array('_id' =>$username ));
        return $user;
    }

    public function allCities()
    {
        $client = new Client();
        $db = $client->selectDatabase('TourApp');
        $cityCollection = $db->selectCollection('City');
        $allCities = iterator_to_array($cityCollection->find());
        return $allCities;
    }

    public function allComments()
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
                "image" => ''
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
            $username=$addedUsers[array_rand($addedUsers)];
            $fromUser =  $users->findOne(array('_id' => $username))->_id;
            $comment= array(
                '_id' => $faker->dateTimeThisYear,
                'content' => $faker->text($faker->numberBetween(20,50)),
                'user' => $fromUser
            );
            $comments->insertOne($comment);
            $users->updateOne(array('_id' => $username), array('$push' => array('comment' => $comment)));
            $komentari[] = $comment;
        }
        return $komentari;
    }


}
