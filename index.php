<?php
require('vendor/autoload.php');
require('config/env.php');
require('config/mysql.php');
#require('config/redis.php');

use Longitude\Models\User;
use Longitude\Models\Location;

// Create Slim app
$app = new \Slim\App(
    [
        'settings' => [
            'debug'         => true,
        ]
    ]
);

// Add whoops to slim because its helps debuggin' and is pretty.
$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware);

// Fetch DI Container
$container = $app->getContainer();

// Instantiate and add Slim specific extension
$view = new \Slim\Views\Twig(
    __DIR__ . '/views',
    [
        'cache' => $container->get('settings')['debug'] ? false : __DIR__ . '/cache'
    ]
);

$view->addExtension(new Slim\Views\TwigExtension(
    $container->get('router'),
    $container->get('request')->getUri()
));

// Register Twig View helper
$container->register($view);

$secret = 'fUKcwDBTmYoY71z';

$app->get('/', function (\Slim\Http\Request $request, \Slim\Http\Response $response, $args) {
    die("Nope");
});

$app->post("/login", function(\Slim\Http\Request $request, \Slim\Http\Response $response, $args) use ($secret) {
    $email = $request->getParsedBodyParam('email', '');
    $phonenumber = $request->getParsedBodyParam('phonenumber', '');
    $password = $request->getParsedBodyParam('password');
    if(!($email || $phonenumber)){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'Need to have email OR phone number'
            ]);
    }
    if(!$password){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'Need to have password'
            ]);
    }
    $userSearch = User::search();
    $searchValid = false;
    if(!empty($email)){
        $userSearch->where('email', $email);
        $searchValid = true;
    }
    if(!empty($phonenumber)){
        $userSearch->where('phonenumber', $phonenumber);
        $searchValid = true;
    }
    $user = $userSearch->execOne();
    if(!$searchValid){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'User Lookup Invalid'
            ]);
    }
    if(!$user instanceof User){
        $user = new User();
        $user->email = $email;
        $user->phonenumber = $phonenumber;
        $user->setPassword($password);
        $user->save();
    }
    if($user->banned == "Yes" || $user->deleted == "Yes"){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'User Banned/Deleted'
            ]);
    }
    if($user->checkPassword($password)){
        return $response
            ->withStatus(200)
            ->withJson([
                'Status' => 'Okay',
                'User' => $user->__toPublicArray(),
                'AuthCode' => $user->getNextAuthCode(),
            ]);
    }else{
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'Bad Credentials'
            ]);
    }

});

$app->post('/auth', function(\Slim\Http\Request $request, \Slim\Http\Response $response, $args) use ($secret) {
    $authCode = $request->getParsedBodyParam('authcode');

    if($authCode == $secret){
        return $response
            ->withStatus(200)
            ->withJson([
                'Status' => 'Okay',
                'authKey' => str_rot13($authCode),
            ]);
    }else{
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
            ]);
    }
});

$app->put("/profile",  function (\Slim\Http\Request $request, \Slim\Http\Response $response, $args) use ($secret) {
    $authKey = $request->getParsedBodyParam('authKey');
});

$app->put('/location', function (\Slim\Http\Request $request, \Slim\Http\Response $response, $args) use ($secret) {
    $authKey = $request->getParsedBodyParam('authKey');
    $coordinates = $request->getParsedBodyParam('location');
    $deviceId = $request->getParsedBodyParam('deviceId', '');

    if(!$authKey){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'No authKey'
            ]);
    }

    $user = User::getByAuthCode($authKey);

    if(!$user instanceof User){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'Invalid authKey. No user match.'
            ]);
    }
    if(!$coordinates){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'No location'
            ]);
    }
    $coordinates = explode(",", $coordinates);
    if(!count($coordinates) == 2){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'location value invalid'
            ]);
    }

    $location = new \Longitude\Models\Location();
    $location->lat = $coordinates[0];
    $location->lng = $coordinates[1];
    $location->user_id = $user->user_id;
    $location->device = $deviceId;
    $location->save();
    return $response
        ->withStatus(200)
        ->withJson([
            'Status' => 'Okay',
            'Location' => $location->__toPublicArray(),
            'SentLocation' => implode(",", $coordinates)
        ]);
});

$app->post("/location", function (\Slim\Http\Request $request, \Slim\Http\Response $response, $args) use ($secret) {
    $authKey = $request->getParsedBodyParam('authKey');
    if(!$authKey){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'No authKey'
            ]);
    }
    if($authKey != str_rot13($secret)){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'Invalid authKey'
            ]);
    }
    $location = \Longitude\Models\Location::search()
        ->where('user_id', 0)
        ->order('created', 'DESC')
        ->execOne();
    if($location) {
        return $response
            ->withStatus(200)
            ->withJson([
                'Status' => 'Okay',
                'Location' => $location->__toPublicArray(),
            ]);
    }else{
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => "No locations sent yet!"
            ]);

    }
});

$app->post('/friends', function (\Slim\Http\Request $request, \Slim\Http\Response $response, $args) use ($secret) {

    $faker = Faker\Factory::create();

    $authKey = $request->getParsedBodyParam('authKey');

    $friends = [];
    $friendCount = rand(5,10);

    $locationBoxXMin = 53.569503;
    $locationBoxXMax = 53.392574;
    $locationBoxYMin = -2.418099;
    $locationBoxYMax = -2.064232;

    for($i = 0; $i < $friendCount; $i++){
        $friend = [
            'Name' => ['Firstname' => $faker->firstName, 'Lastname' => $faker->lastName],
            'Location' => [
                'Lat' => rand($locationBoxXMin*1000000, $locationBoxXMax*1000000)/1000000,
                'Long' => rand($locationBoxYMin*1000000, $locationBoxYMax*1000000)/1000000
            ]
        ];
        $friends[] = $friend;
    }

    if($authKey == str_rot13($secret)){
        return $response
            ->withStatus(200)
            ->withJson([
                'Status' => 'Okay',
                'Friends' => $friends
            ]);
    }else{
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
            ]);
    }
})->setName('redis');

// Run app
$app->run();
