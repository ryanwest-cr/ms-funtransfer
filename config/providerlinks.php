<?php

$middleware_url_api = 'https://api-test.betrnk.games/public';
$gamelobby_site = 'https://daddy.betrnk.games';
$play_betrnk = 'https://play.betrnk.games:446';

return [
    'play_betrnk' => $play_betrnk,
    'tigergames' => $gamelobby_site,
    'oauth_mw_api' => [
        'access_url' => $middleware_url_api.'/oauth/access_token',
        'mwurl' => $middleware_url_api,
        'client_id' => 1,
        'client_secret' => 'QPmdvSg3HGFXbsfhi8U2g5FzAOnjpRoF',
        'username' => 'randybaby@gmail.com',
        'password' => '_^+T3chSu4rt+^_',
        'grant_type' => 'password',
    ],
    'icgaminglogin' => 'https://admin-stage.iconic-gaming.com/service/login',
    'icgaminggames' => 'https://admin-stage.iconic-gaming.com/service/api/v1/games?type=all&lang=en',
    'icgagents'=>[
        'jpyagents'=>[
            'username' => 'betrnkjpy',
            'password' => ']WKtkT``mJCe8N3J',
            'secure_code' => '60e7a70e-806a-479c-af0b-d3c83a6616c1',
        ],
        'euragents'=>[
            'username' => 'betrnkeuro',
            'password' => ']WKtkT``mJCe8N3J',
            'secure_code' => '4c7aa6fe-5559-4006-b995-b2414a472d0b',
        ],
        'cnyagents'=>[
            'username' => 'betrnkcny',
            'password' => ']WKtkT``mJCe8N3J',
            'secure_code' => '7a640de8-82ce-4fe7-b0a3-0bea404bceb8',
        ],
        'krwagents'=>[
            'username' => 'betrnkkrw',
            'password' => ']WKtkT``mJCe8N3J',
            'secure_code' => '0d18064c-cd77-4a04-9f17-2dc27bdb903a',
        ],
        'phpagents'=>[
            'username' => 'betrnkphp',
            'password' => ']WKtkT``mJCe8N3J',
            'secure_code' => '0fa2144d-7529-4483-9306-6515485ce6c7',
        ],
        'thbagents'=>[
            'username' => 'betrnkthb',
            'password' => ']WKtkT``mJCe8N3J',
            'secure_code' => 'e2d411bd-ddea-41b1-a173-483d2f98f7cf',
        ],
        'tryagents'=>[
            'username' => 'betrnktry',
            'password' => ']WKtkT``mJCe8N3J',
            'secure_code' => '93928542-4014-4736-a72e-3d99786df5ea',
        ],
        'twdagents'=>[
            'username' => 'betrnkTWD',
            'password' => ']WKtkT``mJCe8N3J',
            'secure_code' => '99d78fd7-d342-4fa5-932a-029a65b8a1f1',
        ],
        'vndagents'=>[
            'username' => 'betrnkvnd',
            'password' => ']WKtkT``mJCe8N3J',
            'secure_code' => '99d78fd7-d342-4fa5-932a-029a65b8a1f1',
        ],
        'usdagents'=>[
            'username' => 'betrnk',
            'password' => 'betrnk168!^*',
            'secure_code' => '2c00c099-f32b-4fc1-a69d-661d8c51c6ae',
        ],
    ],
    'endorphina' => [
        'url' => 'https://test.endorphina.network/api/sessions/seamless/rest/v1',
        'nodeId' => 1002,
        'secretkey' => '67498C0AD6BD4D2DB8FDFE59BD9039EB',
    ],
    'bolegaming' => [
    	"CNY" => [
            'AccessKeyId' => '9048dbaa-b489-4b32-9a29-149240a5cefe',
            'access_key_secret' => '4A55C539E93B189EAA5A76A8BD92B99B87B76B80',
            'app_key' => 'R14NDR4FT',
            'login_url' => 'https://api.cdmolo.com:16800/v1/player/login',
            'logout_url' => 'https://api.cdmolo.com:16800/v1/player/logout',
        ],
        "USD" => [
            'AccessKeyId' => '912784c6-6f1a-4a0c-a64c-f01f815f8c31',
            'access_key_secret' => '8D0EAD434478F1D487165C9E27F7A93FC9451FFF',
            'app_key' => 'RiANDRAFT',
            'login_url' => 'https://api.bole-game.com:16800/v1/player/login',
            'logout_url' => 'https://api.bole-game.com:16800/v1/player/logout',
        ],
    ],
    'aws' => [
        'api_url' => 'https://sapi.awsxpartner.com/b2b',
        '1USD'=> [ // 
            'merchant_id' => 'TG',
            'merchant_key' => '5819e7a6d0683606e60cd6294edfc4c557a2dd8c9128dd6fbe1d58e77cd8067fead68c48cdb3ea85dcb2e05518bac60412a0914d156a36b4a2ecab359c7adfad',
        ], 
        '2THB' => [ // ASK THB
            'merchant_id' => 'ASKME',
            'merchant_key' => 'a44c3ca52ef01f55b0a8b3859610f554b05aa57ca36e4a508addd9ddae539a84d43f9407c72d555bc3093bf3516663d504e98b989f3ec3e3ff8407171f43ccdc',
        ],
        '3XIGOLO' => [ // XIGOLO USD
            'merchant_id' => 'XIGOLO',
            'merchant_key' => 'b7943fc2e48c3b74a2c31514aebdce25364bd2b1a97855f290c01831052b25478c35bdebdde8aa7a963e140a8c1e6401102321a2bd237049f9e675352c35c4cc',
        ],
        '4TGC' => [  // ASK ME THB
            'merchant_id' => 'TGC',
            'merchant_key' => 'cb1bc0a2fc16bddfd549bdd8aae0954fba28c9b11c6a25e6ef886b56e846b033ae5fe29880be69fd8741ab400e6c4cb2f8c0f05e49dcc4568362370278ba044d',
        ]
    ],
    'cqgames' => [
        "prefix" => "TG",
        "pdbid"=> 30, // Database ID nothing todo with the provider!
        'api_url' => 'http://api.cqgame.games',
        // 'api_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyaWQiOiI1ZjIyODY1ZWNkNTY1ZjAwMDEzZjUyZDAiLCJhY2NvdW50IjoidGlnZXJnYW1lcyIsIm93bmVyIjoiNWYyMjg2NWVjZDU2NWYwMDAxM2Y1MmQwIiwicGFyZW50Ijoic2VsZiIsImN1cnJlbmN5IjoiVVNEIiwianRpIjoiMjQ3NDQ1MTQzIiwiaWF0IjoxNTk2MDk4MTQyLCJpc3MiOiJDeXByZXNzIiwic3ViIjoiU1NUb2tlbiJ9.fdoQCWGPkYNLoROGR9jzMs4axnZbRJCnnLZ8T2UDCwU',
        'api_tokens' => [
            'USD' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyaWQiOiI1ZjIyODY1ZWNkNTY1ZjAwMDEzZjUyZDAiLCJhY2NvdW50IjoidGlnZXJnYW1lcyIsIm93bmVyIjoiNWYyMjg2NWVjZDU2NWYwMDAxM2Y1MmQwIiwicGFyZW50Ijoic2VsZiIsImN1cnJlbmN5IjoiVVNEIiwianRpIjoiMjQ3NDQ1MTQzIiwiaWF0IjoxNTk2MDk4MTQyLCJpc3MiOiJDeXByZXNzIiwic3ViIjoiU1NUb2tlbiJ9.fdoQCWGPkYNLoROGR9jzMs4axnZbRJCnnLZ8T2UDCwU',
            'CNY' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyaWQiOiI1ZjM0ZDg1YWNkNTY1ZjAwMDE0MDBjZTYiLCJhY2NvdW50IjoidGlnZXJnYW1lc19jbnkiLCJvd25lciI6IjVmMjI4NjVlY2Q1NjVmMDAwMTNmNTJkMCIsInBhcmVudCI6IjVmMjI4NjVlY2Q1NjVmMDAwMTNmNTJkMCIsImN1cnJlbmN5IjoiQ05ZIiwianRpIjoiNjY2MjE3MzgxIiwiaWF0IjoxNTk3Mjk4Nzc4LCJpc3MiOiJDeXByZXNzIiwic3ViIjoiU1NUb2tlbiJ9.5xRfW4vHJLi7PeBmZGckSAIw9KoeL_al-dwcnV5dYL4',
        ],
        'wallet_token' => [
            'USD' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyaWQiOiI1ZjIyODY1ZWNkNTY1ZjAwMDEzZjUyZDAiLCJhY2NvdW50IjoidGlnZXJnYW1lcyIsIm93bmVyIjoiNWYyMjg2NWVjZDU2NWYwMDAxM2Y1MmQwIiwicGFyZW50Ijoic2VsZiIsImN1cnJlbmN5IjoiVVNEIiwianRpIjoiMjQ3NDQ1MTQzIiwiaWF0IjoxNTk2MDk4MTQyLCJpc3MiOiJDeXByZXNzIiwic3ViIjoiU1NUb2tlbiJ9.fdoQCWGPkYNLoROGR9jzMs4axnZbRJCnnLZ8T2UDCwU',
            'CNY' => '7CBCiX3qf5zMfYijIAmanbP2JB2HiBAi',
        ]
    ],
    'sagaming' => [
        "pdbid"=> 25, // Database ID nothing todo with the provider!
        "prefix" => "TGSA", // Nothing todo with the provider
        "lobby" => "A3107",
        "API_URL" => "http://sai-api.sa-apisvr.com/api/api.aspx",
        "MD5Key" => "GgaIMaiNNtg",
        "EncryptKey" => "g9G16nTs",
        "SAAPPEncryptKey" =>"M06!1OgI",
        "SecretKey" => "87B41ED0FB20437E85DE569B16EAA1DB",
    ],
    'kagaming' => [
        "pdbid"=> 32, // Database ID nothing todo with the provider!
        "gamelaunch" => "https://gamesstage.kaga88.com",
        "ka_api" => "https://rmpstage.kaga88.com/kaga/",
        "access_key" => "A95383137CE37E4E19EAD36DF59D589A",
        "secret_key" => "40C6AB9E806C4940E4C9D2B9E3A0AA25",
        "partner_name" => "TIGER",
    ],
    'iagaming' => [
        'auth_key' => '54bc08c471ae3d656e43735e6ffc9bb6',
        'pch' => 'BRNK', 
        'prefix' => 'TGAMES', // Nothing todo with the provider
        'iv' => '45b80556382b48e5',
        'url_lunch' => 'http://api.ilustretest.com/user/lunch',
        'url_register' => 'http://api.ilustretest.com/user/register',
        'url_withdraw' => 'http://api.ilustretest.com/user/withdraw',
        'url_deposit' => 'http://api.ilustretest.com/user/deposit',
        'url_balance' => 'http://api.ilustretest.com/user/balance',
        'url_wager' => 'http://api.ilustretest.com/user/getproject',
        'url_hotgames' => 'http://api.ilustretest.com/user/gethotgame',
        'url_orders' => 'http://api.ilustretest.com/user/searchprders',
        'url_activity_logs' => 'http://api.ilustretest.com/user/searchprders',
    ],
    'tidygaming' => [
        'url_lunch' => 'http://staging-v1-api.tidy.zone/api/game/outside/link',
        'API_URL' => 'http://staging-v1-api.tidy.zone',
        'client_id' => '8440a5b6',
        'SECRET_KEY' => 'f83c8224b07f96f41ca23b3522c56ef1',
    ],
    'evoplay' => [
        'api_url' => 'http://api.8provider.com',
        'project_id' => '1042',
        'secretkey' => 'c270d53d4d83d69358056dbca870c0ce',
    ],
    'skywind' => [
        'provider_db_id' => 28, // Database ID nothing todo with the provider!
        'prefix_user' => 'TG', // Developer Logic nothing todo with the provider!
        'api_url' => 'https://api.gcpstg.m27613.com/v1',
        'seamless_key' => '47138d18-6b46-4bd4-8ae1-482776ccb82d',
        'seamless_username' => 'TGAMESU_USER',
        'seamless_password' => 'Tgames1234',
        'merchant_data' => 'TIGERGAMESU',
        'merchant_password' => 'LmJfpioowcD8gspb',
    ],
    'digitain' => [
        'provider_db_id' => 14, // Database ID nothing todo with the provider!
        'provider_and_sub_name' => 'Digitain', // Nothing todo with the provider
        'digitain_key' => 'BetRNK3184223',
        'operator_id' => 'B9EC7C0A',
    ],
    'payment'=>[
        'catpay'=>[
            'url_order'=>'http://celpay.vip/platform/submit/order',
            'url_redirect'=>'http://celpay.vip',
            'platformId' => 'WamRAOjZxH8vYG4rJU1',
            'platformToken'=>'azETahcH',
            'platformKey'=>'3a3343c316d947f68841fd7fd7c35636',
            'sign'=> 'WamRAOjZxH8vYG4rJU1',
        ]
        ],
    'boongo'=>[
        'PLATFORM_SERVER_URL'=>'https://gate-stage.betsrv.com/op/',
        'PROJECT_NAME'=>'tigergames-stage',
        'WL'=>'prod',
        'API_TOKEN'=>'hj1yPYivJmIX4X1I1Z57494re',
    ],
    'fcgaming'=>[
        'url' => 'http://api.fcg666.net',
        'AgentCode' => 'TG',
        'AgentKey' => '8t4A17537S1d5rwz',
        
    ],
    'tgg' => [
        'api_url' => 'http://api.flexcontentprovider.com',
        'project_id' => '1421',
        'api_key' => '29abd3790d0a5acd532194c5104171c8',
    ],
    'pgsoft' => [
        'api_url' => 'http://api.pg-bo.me/external',
        'operator_token' => '642052d1627c8cae4a288fc82a8bf892',
        'secret_key' => '02f314db35a0dfe4635dff771b607f34',
    ],
    'tpp' => [
        'gamelaunch_url' => 'https://tigergames-sg0.prerelease-env.biz/gs2c/playGame.do',
        'secureLogin' => 'tg_tigergames', //or stylename
        'secret_key' => 'testKey',
    ],
    'wazdan'=>[
        'operator' => 'tigergames',
        'license' => 'curacao',
        'hmac_scret_key' => 'uTDVNr4wu6Y78SNbr36bqsSCH904Rcn1',
        'partnercode'=> 'gd1wiurg',
        'gamelaunchurl' => 'https://gl-staging.wazdanep.com/'
    ],
    'evolution'=>[
        'host' => 'https://babylontgg.uat1.evo-test.com',
        'ua2Token' => 'test123',
        'gameHistoryApiToken' => 'test123',
        'externalLobbyApiToken'=> 'test123',
        'owAuthToken' => 'TigerGames@2020',
        'ua2AuthenticationUrl' => 'https://babylontgg.uat1.evo-test.com/ua/v1/babylontgg000001/test123',
        'env'=>'production'

    ],
    'png'=>[
        'root_url'=> 'https://agastage.playngonetwork.com',
        'pid' => 8888,
        'channel'=> 'desktop',
        'practice'=>0
    ],
    'microgaming'=>[
        'grant_type'=> 'client_credentials',
        'client_id' => 'Tiger_UPG_USD_MA_Test',
        'client_secret'=> 'd4e59abcbf0b4fd88e3904f12c3dfb',
    ],
    'booming' => [
        'api_url' => 'https://api.intgr.booming-games.com',
        'api_secret' => 'NQGRafUDbe/esU8r+zVWWW7cx6xZKE2gpqWXv4Fs17j88u0djV6NBi9Tgdtc0R6w',
        'api_key' =>'xvkwXPp52AUPLBGCXmD5UA==',
        'call_back' => 'https://api-test.betrnk.games/public/api/booming/callback',
        'roll_back' => 'https://api-test.betrnk.games/public/api/booming/rollback',
        'provider_db_id' => 36,
    ],
    'manna'=>[
        'PROVIDER_ID' => 16,
        'AUTH_URL'=> 'https://api.mannagaming.com/agent/specify/betrnk/authenticate/auth_token',
        'GAME_LINK_URL' => 'https://api.mannagaming.com/agent/specify/betrnk/gameLink/link',
        'API_KEY'=> 'GkyPIN1mD*yzjxzQumq@cZZC!Vw%b!kIVy&&hk!a',
        'AUTH_API_KEY'=> 'GkyPIN1mD*yzjxzQumq@cZZC!Vw%b!kIVy&&hk!a',
        'CLIENT_API_KEY' => '4dtXHSekHaFkAqbGcsWV2es4BTRLADQP'
    ],
    'solid'=>[
        'PROVIDER_ID' => 1,
        'LAUNCH_URL'=> 'https://instage.solidgaming.net/api/launch/',
        'API_ENDPOINT' => 'https://instage.solidgaming.net/api/wallet/',
        'BRAND' => 'BETRNKMW',
        'AUTH_USER' => 'betrnkmw-stage',
        'AUTH_PASSWORD' => 'wyE4PEGHWkWyU5TjdNk2g'
    ],
    'habanero'=>[
        'api_url' => 'https://app-test.insvr.com/go.ashx?',
        'brandID' => '2416208c-f3cb-ea11-8b03-281878589203',
        'apiKey' => '3C3C5A48-4FE0-4E27-A727-07DE6610AAC8',
        'passKey' => 'Rja5ZK4kN1GA0R8C'
    ],
    'simpleplay' => [
        'PROVIDER_ID' => 35,
        'LOBBY_CODE' => 'S592',
        'SECRET_KEY' => 'A872BAFDFA8349CC824A460E7AC02515',
        'MD5_KEY' => 'GgaIMaiNNtg',
        'ENCRYPT_KEY' => 'g9G16nTs',
        'API_URL' => 'http://api.sp-portal.com/api/api.aspx'
    ],
    'ygg'=>[
        'api_url' => 'https://static-stage-tw.248ka.com/init/launchClient.html?',
        'Org' => 'TIGERGAMES',
        'topOrg' => 'TIGERGAMESGroup',
        'provider_id' => 38,
    ],
    'spade'=>[
        'prefix' => 'TIGERG', 
        'api_url'=> 'https://api-egame-staging.sgplay.net/api',
        'merchantCode' => 'TIGERG',  
        'siteId' => 'SITE_USD1',
        'provider_id' => 37,
    ],
    'majagames'=>[
        'auth' => 'wsLQrQM1OC1bVscK',
        'provider_id' => 39,
        'prefix' => 'MAJA_', 
        'api_url'=> 'http://api-integration.mj-02.com/api/MOGI', //slot api
        'tapbet_api_url'=> 'https://tbb-integration.mj-02.com/api', //tapbet api
    ],
    'spade_curacao'=>[
        'prefix' => 'TIGERG', 
        'api_url'=> 'https://api-egame-staging.spadecasino777.com/api',
        'lobby_url'=> 'https://lobby-egame-staging.spadecasino777.com/TIGEREU/auth/?',
        'merchantCode' => 'TIGEREU',
        'siteId' => 'SITE_EU1',
        'provider_id' => 37,
    ],
    'vivo' => [
        'PROVIDER_ID' => 34,
        'OPERATOR_ID' => '75674',
        'SERVER_ID' => '51681981',
        'PASS_KEY' => '7f1c5d',
        'VIVO_URL' => 'https://games.vivogaming.com/',
        'BETSOFT_URL' => 'https://1vivo.com/FlashRunGame/RunRngGame.aspx',
        'SPINOMENAL_URL' => 'https://www.1vivo.com/flashRunGame/RunSPNRngGame.aspx',
        'TOMHORN_URL' => 'https:///www.1vivo.com/FlashRunGame/Prod/RunTomHornGame.aspx',
        'NUCLEUS_URL' => 'https://2vivo.com/FlashRunGame/set2/RunNucGame.aspx',
        'PLATIPUS_URL' => 'https://www.1vivo.com/flashrungame/set2/RunPlatipusGame.aspx',
        'LEAP_URL' => 'https://www.2vivo.com/flashrungame/RunGenericGame.aspx'
    ],
    'oryx' => [
        'PROVIDER_ID' => 18,
        'GAME_URL' => 'https://play-prodcopy.oryxgaming.com/agg_plus_public/launch/wallets/WELLTREASURETECH/games/'
    ],
    'netent' => [
        'provider_db_id' => 45,
        'casinoID' => "tigergames",//casinoID
        'merchantId' => "testmerchant",//soap api login
        'merchantPassword' => "testing",//soap api login
    ],
    'goldenF'=>[
        'api_url'=> 'http://tgr.test.gf-gaming.com/gf',
        'secrete_key' => 'b18d99f11861042e2c66f11a1f9a62cb',
        'operator_token' => '009583d3138a9e3934787112c345ef10',
        'wallet_code' => 'gf_gps_wallet',
        'provider_id' => 41,
    ],
    'ultraplay'=>[
        'domain_url'=> 'https://stage-tet.ultraplay.net',
    ],
];

?>