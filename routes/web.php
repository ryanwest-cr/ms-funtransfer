<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// Home page
$app->get('/', function () use ($app) {
    return $app->version();
});

// Posts
$app->get('/posts','PostController@index');
$app->post('/posts','PostController@store');
$app->get('/posts/{post_id}','PostController@show');
$app->put('/posts/{post_id}', 'PostController@update');
$app->patch('/posts/{post_id}', 'PostController@update');
$app->delete('/posts/{post_id}', 'PostController@destroy');

// Users
$app->get('/users/', 'UserController@index');
$app->post('/users/', 'UserController@store');
$app->get('/users/{user_id}', 'UserController@show');
$app->put('/users/{user_id}', 'UserController@update');
$app->patch('/users/{user_id}', 'UserController@update');
$app->delete('/users/{user_id}', 'UserController@destroy');

// Comments
$app->get('/comments', 'CommentController@index');
$app->get('/comments/{comment_id}', 'CommentController@show');

// Comment(s) of a post
$app->get('/posts/{post_id}/comments', 'PostCommentController@index');
$app->post('/posts/{post_id}/comments', 'PostCommentController@store');
$app->put('/posts/{post_id}/comments/{comment_id}', 'PostCommentController@update');
$app->patch('/posts/{post_id}/comments/{comment_id}', 'PostCommentController@update');
$app->delete('/posts/{post_id}/comments/{comment_id}', 'PostCommentController@destroy');

// Player Details Request
$app->post('/api/playerdetailsrequest/', 'PlayerDetailsController@show');

// Fund Transfer Request
$app->post('/api/fundtransferrequest/', 'FundTransferController@process');

// Solid Gaming Endpoints

$app->post('/api/v1/solidgaming/authenticate', 'SolidGamingController@authPlayer');
$app->post('/api/v1/solidgaming/playerdetails', 'SolidGamingController@getPlayerDetails');
$app->post('/api/v1/solidgaming/balance', 'SolidGamingController@getBalance');
$app->post('/api/v1/solidgaming/debit', 'SolidGamingController@debitProcess');
$app->post('/api/v1/solidgaming/credit', 'SolidGamingController@creditProcess');
$app->post('/api/v1/solidgaming/debitandcredit', 'SolidGamingController@debitAndCreditProcess');
$app->post('/api/v1/solidgaming/rollback', 'SolidGamingController@rollbackProcess');
$app->post('/api/v1/solidgaming/endround', 'SolidGamingController@endPlayerRound');
$app->post('/api/v1/solidgaming/endsession', 'SolidGamingController@endPlayerSession');


// Request an access token
$app->post('/oauth/access_token', function() use ($app){
    return response()->json($app->make('oauth2-server.authorizer')->issueAccessToken());
});
