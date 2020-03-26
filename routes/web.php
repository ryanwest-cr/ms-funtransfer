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
$app->post('/api/solid/authenticate', 'SolidGamingController@authPlayer');
$app->post('/api/solid/playerdetails', 'SolidGamingController@getPlayerDetails');
$app->post('/api/solid/balance', 'SolidGamingController@getBalance');
$app->post('/api/solid/debit', 'SolidGamingController@debitProcess');
$app->post('/api/solid/credit', 'SolidGamingController@creditProcess');
$app->post('/api/solid/debitandcredit', 'SolidGamingController@debitAndCreditProcess');
$app->post('/api/solid/rollback', 'SolidGamingController@rollbackProcess');
$app->post('/api/solid/endround', 'SolidGamingController@endPlayerRound');
$app->post('/api/solid/endsession', 'SolidGamingController@endPlayerSession');



// Lottery Gaming Endpoints
$app->post('/api/lottery/authenticate', 'LotteryController@authPlayer');
$app->post('/api/lottery/balance', 'LotteryController@getBalance'); #/
$app->post('/api/lottery/debit', 'LotteryController@debitProcess'); #/
// $app->post('/api/lottery/credit', 'LotteryController@creditProcess');
// $app->post('/api/lottery/debitandcredit', 'LotteryController@debitAndCreditProcess');
// $app->post('/api/lottery/endsession', 'LotteryController@endPlayerSession');


// Mariott Gaming Endpoints
$app->post('/api/marriott/authenticate', 'MarriottController@authPlayer');
$app->post('/api/marriott/balance', 'MarriottController@getBalance'); #/
$app->post('/api/marriott/debit', 'MarriottController@debitProcess'); #/






// EPOINT CONTROLLER
// $app->post('/api/epoint', 'EpointController@epointAuth'); #/
// $app->post('/api/epoint/bitgo', 'EpointController@bitgo'); #/


// EBANCO
// $app->post('/api/ebancobanks', 'EbancoController@getAvailableBank'); #/
// $app->post('/api/ebancodeposit', 'EbancoController@deposit'); #/
// $app->post('/api/ebancodeposithistory', 'EbancoController@deposithistory'); #/
// $app->post('/api/ebancodepositinfobyid', 'EbancoController@depositinfo'); #/
// $app->post('/api/ebancodepositinfobyselectedid', 'EbancoController@depositinfobyselectedid'); #/
// $app->post('/api/ebancodepositreceipt', 'EbancoController@depositReceipt'); #/

$app->post('/api/ebancoauth', 'EbancoController@connectTo'); #/
$app->post('/api/ebancogetbanklist', 'EbancoController@getBankList'); #/
$app->post('/api/ebancodeposit', 'EbancoController@makeDeposit'); #/
$app->post('/api/ebancosenddepositreceipt', 'EbancoController@sendReceipt'); #/
$app->post('/api/ebancodeposittransaction', 'EbancoController@depositInfo'); #/
$app->post('/api/ebancodeposittransactions', 'EbancoController@depositHistory'); #/
$app->post('/api/ebancoupdatedeposit', 'EbancoController@updateDeposit'); #/


// Request an access token
$app->post('/oauth/access_token', function() use ($app){
    return response()->json($app->make('oauth2-server.authorizer')->issueAccessToken());
});


//paymentgateway routes

$app->get('/paymentgateways','Payments\PaymentGatewayController@index');
$app->post('/payment','Payments\PaymentGatewayController@paymentPortal');
$app->get('/coinpaymentscurrencies','Payments\PaymentGatewayController@getCoinspaymentRate');
$app->get('/currencyconversion','CurrencyController@currency');
$app->post('/updatetransaction','Payments\PaymentGatewayController@updatetransaction');
$app->post('qaicash/depositmethods','Payments\PaymentGatewayController@getQAICASHDepositMethod');
$app->post('qaicash/deposit','Payments\PaymentGatewayController@makeDepositQAICASH');
///CoinsPayment Controller