<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EpointController extends Controller
{
    //

	/**
	 *  EPOINT OAUTH TEST TEST NO FUNCTIONS
	 */
    public function epointAuth(Request $request){

    	// dd(2);	

       /* CLIENT TO PROVIDE */	
       $client_secret = $reqeust->client_secret;
       $client_id = $request->client_id;


       /* SNED AUTH REQUEST TO EPOINT */
       // $http = new Client();
	   // $response = $http->post('http://www.epointexchange.com/oauth/token', [
	   //     'form_params' => [
	   //         'grant_type' => 'password',
	   //         'client_id' => '3',
	   //         'client_secret' => 'uAthPzJR6lk9hrgPljMUjzGHjnPvtT2Ps6eLHRv7',
	   //         'username' => 'apiadmin@epointexchange.com',
	   //         'password' => 'apiadmin@)!(**',
	   //         'scope' => 'betrnk',
	   //     ],
	   // ]);


	   $http = new Client();
	   $response = $http->post('https://www.epointexchange.com/oauth/token', [
	       'form_params' => [
	           'grant_type' => 'password',
	           'client_id' => $client_id, 
	           'client_secret' => $client_secret, /* client secret coming from client */
	           'username' => 'apiadmin@epointexchange.com',
	           'password' => 'apiadmin@)!(**',
	           'scope' => 'betrnk',
	       ],
	   ]);


	   /* REPLY TO THE CLIENT */
	  echo $epoint_response = json_decode($guzzle_response->getBody()->getContents());


    }

    public function epointAuth(Request $request){ 


    }







}
