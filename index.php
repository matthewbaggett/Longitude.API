<?php
require('vendor/autoload.php');
require('config/env.php');
require('config/mysql.php');
#require('config/redis.php');

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

$app->post('/auth', function(\Slim\Http\Request $request, \Slim\Http\Response $response, $args) use ($secret) {
    $authCode = $request->getParsedBodyParam('authcode');

    if($authCode == $secret){
        return $response
            ->withStatus(200)
            ->withJson([
                'Status' => 'Okay',
                'SessionKey' => str_rot13($authCode),
            ]);
    }else{
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
            ]);
    }
});

$app->put('/location', function (\Slim\Http\Request $request, \Slim\Http\Response $response, $args) use ($secret) {
    $sessionKey = $request->getParsedBodyParam('sessionKey');
    $coordinates = $request->getParsedBodyParam('location');

    if(!$sessionKey){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'No sessionKey'
            ]);
    }
    if($sessionKey != str_rot13($secret)){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'Invalid sessionKey'
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
    $location->user_id = 0;
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
    $sessionKey = $request->getParsedBodyParam('sessionKey');
    if(!$sessionKey){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'No sessionKey'
            ]);
    }
    if($sessionKey != str_rot13($secret)){
        return $response
            ->withStatus(400)
            ->withJson([
                'Status' => 'Failure',
                'Reason' => 'Invalid sessionKey'
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

    $sessionKey = $request->getParsedBodyParam('sessionKey');

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

    if($sessionKey == str_rot13($secret)){
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
