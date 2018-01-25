<?php

namespace App\Http\Controllers;

use App\ApiGis;
use App\GisPlace;
use App\User;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Facebook\FacebookRequest;
use Facebook\GraphNodes\GraphNodeFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\HandlerStack;
class SocialNetworksController extends Controller
{

    public function google()
    {
        require_once public_path() . '/../vendor//autoload.php';
        session_start();
        $client = new \Google_Client();
        $client->setAuthConfig(public_path() . '/client_secrets.json');
        $client->setAccessType("offline");
        $client->setIncludeGrantedScopes(true);
        $client->addScope('https://www.googleapis.com/auth/userinfo.email');
        $client->addScope('https://www.googleapis.com/auth/plus.login');
        $client->addScope('https://www.googleapis.com/auth/userinfo.profile');
        $client->addScope('https://www.googleapis.com/auth/plus.me');

        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $client->setAccessToken($_SESSION['access_token']);
            $oauth2 = new \Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();
            dd($userInfo);
        } else {
            header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
        }
        echo 'g';
    }

    public function google_code()
    {
        require_once public_path() . '/../vendor//autoload.php';
        session_start();

        $client = new \Google_Client();
        $client->setAuthConfig(public_path() . '/client_secrets.json');
        $client->addScope('https://www.googleapis.com/auth/userinfo.email');
        $client->addScope('https://www.googleapis.com/auth/plus.login');
        $client->addScope('https://www.googleapis.com/auth/userinfo.profile');
        $client->addScope('https://www.googleapis.com/auth/plus.me');

        if (! isset($_GET['code'])) {
            $redirect_uri = $client->createAuthUrl();
            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
            echo 'y';
        } else {
            $client->authenticate($_GET['code']);
            $_SESSION['access_token'] = $client->getAccessToken();
            $redirect_uri = route('google');
            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
            echo 'k';
        }
    }

    public function fb_code()
    {
        session_start();
        $fb = new Facebook([
            'app_id' => '1908224952787499', // Replace {app-id} with your app id
            'app_secret' => 'a4565739d8a0e75f5ad973f8164f1d13',
            'default_graph_version' => 'v2.9',
        ]);

        $helper = $fb->getRedirectLoginHelper();
        dd($helper->getAccessToken());
        try {
            $accessToken = $helper->getAccessToken();
        } catch (FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if (!isset($accessToken)) {
            if ($helper->getError()) {
                header('HTTP/1.0 401 Unauthorized');
                echo "Error: " . $helper->getError() . "\n";
                echo "Error Code: " . $helper->getErrorCode() . "\n";
                echo "Error Reason: " . $helper->getErrorReason() . "\n";
                echo "Error Description: " . $helper->getErrorDescription() . "\n";
            } else {
                header('HTTP/1.0 400 Bad Request');
                echo 'Bad request';
            }
            exit;
        }

        // The OAuth 2.0 client handler helps us manage access tokens
        $oAuth2Client = $fb->getOAuth2Client();
        // Get the access token metadata from /debug_token
        $tokenMetadata = $oAuth2Client->debugToken($accessToken);
        // Validation (these will throw FacebookSDKException's when they fail)
        $tokenMetadata->validateAppId('1908224952787499'); // Replace {app-id} with your app id
        // If you know the user ID this access token belongs to, you can validate it here
        //$tokenMetadata->validateUserId('155618448311875');
        $tokenMetadata->validateExpiration();

        if (!$accessToken->isLongLived()) {
            // Exchanges a short-lived access token for a long-lived one
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (FacebookSDKException $e) {
                echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
                exit;
            }

            echo '<h3>Long-lived</h3>';
            var_dump($accessToken->getValue());
        }
        ///////////////////////////////
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $fb->get('/me?fields=picture,id,name,email,friends,currency,first_name,last_name,middle_name,short_name,gender,birthday,' .
                'hometown,interested_in,languages,link,locale,location,meeting_for,political,public_key,quotes,' .
                'relationship_status,religion,sports,third_party_id,timezone,updated_time,verified,website,work', $accessToken);
        } catch (FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        $user = $response->getGraphUser();
        $avatar = $fb->get('/me/picture?redirect=false&width=1280', $accessToken);
        $avatar = $avatar->getGraphNode();
        $loged_user = User::where('fb_id', $user['id'])->first();
        if($loged_user)
        {
            $loged_user->update([
                'name' => $user['first_name'],
                'surname' => $user['last_name'],
                'email' => $user['email'],
                'dob' => isset($user['birthday']) ? $user['birthday'] : null,
                'photo' => isset($avatar['url']) ? $avatar['url'] : null,
            ]);
            return redirect(route('home'));
        }

//        dd($user);
        User::create([
            'name' => $user['first_name'],
            'surname' => $user['last_name'],
            'email' => $user['email'],
            'gender' => ($user['gender'] == 'male') ? 1 : 2,
            'dob' => isset($user['birthday']) ? $user['birthday'] : null,
            'password' => bcrypt(11111),
            'photo' => isset($avatar['url']) ? $avatar['url'] : null,
            'fb_id' => $user['id'],
        ]);
        return redirect(route('home'));
        /////////////////////////////////

//$_SESSION['fb_access_token'] = (string) $accessToken;}
    }
    public function twitter()
    {
        $appID = "Vl3wYN4mRtUQKcTE0VwzzTRm2";
        $appSecret = "Hh645vtxfOxbjZ27oJGb5xXAjnQbdRS6jGCImfhgadr9GpIohv";

        $url = "https://api.twitter.com/oauth/request_token";

        $oauth = [
            "oauth_callback" => "http://tegtegergregr.com/twitter/sign",
            "oauth_consumer_key" => $appID,
            "oauth_nonce" => base64_encode(time()),
            "oauth_signature_method" => "HMAC-SHA1",
            "oauth_timestamp" => time(),
            "oauth_version" => "1.0",
        ];

        $token_string = "POST&" . rawurlencode($url) . "&" . rawurlencode(http_build_query($oauth));
        $signing_key = rawurlencode($appSecret) . "&";
        $signature = base64_encode(hash_hmac("sha1", $token_string, $signing_key, true));

        $oauth["oauth_signature"] = $signature;

        $header = [];
        foreach ($oauth as $key => $value)
        {
            $header[] = rawurlencode($key) . "=\"" . rawurlencode($value) . "\"";
        }
        $header = implode(", ", $header);
        $header = "Authorization: OAuth " . $header;

        $opts = ['http' => [
            'method'  => "POST",
            'header'  => $header,
        ]];
        $context  = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);

        $arr = explode('&', $result);
        $tokens = [];
        foreach ($arr as $item)
        {
            $tokens[] = explode('=', $item);
        }
        $oauth_token = $tokens[0][1];//сохранить токен в базе
        $oauth_token_secret = $tokens[1][1];
        header('Location: ' . "https://api.twitter.com/oauth/authenticate?oauth_token=$oauth_token");
        echo 'f';
    }

    public function twitter_sign(Request $request)
    {
        $oauth_token = $request->oauth_token;//проверить совпадают ли токены
        $oauth_verifier = $request->oauth_verifier;

        $appID = "Vl3wYN4mRtUQKcTE0VwzzTRm2";
        $appSecret = "Hh645vtxfOxbjZ27oJGb5xXAjnQbdRS6jGCImfhgadr9GpIohv";

        $url = "https://api.twitter.com/oauth/access_token?oauth_verifier=$oauth_verifier";

        $oauth = [
            "oauth_consumer_key" => $appID,
            "oauth_nonce" => base64_encode(time()),
            "oauth_signature_method" => "HMAC-SHA1",
            "oauth_timestamp" => time(),
            "oauth_token" => $oauth_token,
            "oauth_version" => "1.0",
        ];

        $token_string = "POST&" . rawurlencode($url) . "&" . rawurlencode(http_build_query($oauth));
        $signing_key = rawurlencode($appSecret) . "&";
        $signature = base64_encode(hash_hmac("sha1", $token_string, $signing_key, true));

        $oauth["oauth_signature"] = $signature;

        $header = [];
        foreach ($oauth as $key => $value)
        {
            $header[] = rawurlencode($key) . "=\"" . rawurlencode($value) . "\"";
        }
        $header = implode(", ", $header);
        $header = "Authorization: OAuth " . $header;

        $opts = ['http' => [
            'method'  => "POST",
            'header'  => $header,
        ]];
        $context  = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
//        dd($result);
        $arr = explode('&', $result);
        $tokens = [];
        foreach ($arr as $item)
        {
            $tokens[] = explode('=', $item);
        }
        $oauth_token = $tokens[0][1];//сохранить токен в базе
//        dd($oauth_token);
        $oauth_token_secret = $tokens[1][1];
//        dd($oauth_token_secret);




        $stack = HandlerStack::create();

        $middleware = new Oauth1([
            'consumer_key'    => 'Vl3wYN4mRtUQKcTE0VwzzTRm2',
            'consumer_secret' => 'Hh645vtxfOxbjZ27oJGb5xXAjnQbdRS6jGCImfhgadr9GpIohv',
            'token'           => $oauth_token,
            'token_secret'    => $oauth_token_secret,
        ]);

        $stack->push($middleware);

        $client = new Client([
            'base_uri' => 'https://api.twitter.com/1.1/',
            'handler' => $stack,
            'auth' => 'oauth',
        ]);

        $res = $client->get('account/verify_credentials.json?include_email=true');

        dd(json_decode($res->getBody()));
//        $res = $client->get('friends/list.json?count=200');//друзья
//        dd(json_decode($res->getBody()));

    }

    public $city = '';
    public function search()
    {

        $categories = ['Рюмочные', 'Фреш-бары / Точки безалкогольных коктейлей / горячих напитков', 'Суши-бары / рестораны', 'Столовые',
            'Рестораны', 'Пиццерии', 'Ночные клубы', 'Кулинария', 'Кафе-кондитерские / Кофейни', 'Кафе / рестораны быстрого питания',
            'Кафе', 'Доставка готовых блюд', 'Бары', 'Банкетные залы', 'Антикафе'];
        $city_ids = [27, 65, 41, 11, 70, 89, 40, 61, 109, 5, 58, 94, 34, 23, 10, 73, 86, 56, 26, 113, 89, 96, 29, 82, 12, 45, 6, 74, 76, 103, 48, 71, 42, 80, 95, 90, 44, 85, 43, 63, 30, 57, 60, 54, 39, 72, 81, 47, 97, 22, 3, 36, 13, 37, 55, 83, 35, 53, 64, 88, 50, 28];
        $pp = ApiGis::where('url', 'http://catalog.api.2gis.ru/2.0/region/list?key=rulqsf7935&country_code_filter=ru&fields=items.name_grammatical_cases,items.domain,items.locales,items.time_zone,items.bounds,items.statistics,items.locale,items.settlements,items.satellites')->first();
        $pp = json_decode($pp->data)->result->items;
        set_time_limit(0);
        foreach ($pp as $city)
        {
            if(in_array($city->id, $city_ids))
            {
                $this->city = $city->name;
                $polygon = $city->bounds;
                $key = 'rulqsf7935';
                $region_id = $city->id;
                $client = new Client();
                $url = "http://catalog.api.2gis.ru/2.0/catalog/rubric/list?key=$key&region_id=$region_id&sort=name";
                $result = $client->get($url);
                ApiGis::create([
                    'url' => $url,
                    'data' => $result->getBody(),
                    'descr' => "Список рубрик $this->city"
                ]);
                $parent_id = 0;
                foreach (json_decode($result->getBody())->result->items as $main_category)
                {
                    if($main_category->name == 'Досуг / Развлечения / Общественное питание')
                    {
                        $parent_id = $main_category->id;
                    }
                }
                $client = new Client();
                $url = "http://catalog.api.2gis.ru/2.0/catalog/rubric/list?key=$key&parent_id=$parent_id&region_id=$region_id&sort=name";
                $result = $client->get($url);
                ApiGis::create([
                    'url' => $url,
                    'data' => $result->getBody(),
                    'descr' => "Список рубрик по питанию $this->city"
                ]);
                $rubric_id = '';
                foreach (json_decode($result->getBody())->result->items as $category)
                {
                    if(in_array($category->name, $categories))
                    {
                        $rubric_id .= $category->id . ',';
                    }
                }
                $rubric_id = substr($rubric_id, 0, -1);
                $fields = 'items.region_id,items.point,items.adm_div,items.dates,items.photos,items.see_also,items.flags,items.locale,items.address,items.schedule,items.name_ex,dym';
                $page = 1;
                $client = new Client();
                $url = "http://catalog.api.2gis.ru/2.0/catalog/branch/list?key=$key&rubric_id=$rubric_id&region_id=$region_id&polygon=$polygon&page_size=50&fields=$fields&page=$page";
                $result = $client->get($url);
                ApiGis::create([
                    'url' => $url,
                    'data' => $result->getBody(),
                    'descr' => "Список мест $this->city"
                ]);

                $pages = ceil(json_decode($result->getBody())->result->total / 50) + 1;
                for($page = 2; $page < $pages; $page++) {
                    $client = new Client();
                    $url = "http://catalog.api.2gis.ru/2.0/catalog/branch/list?key=$key&rubric_id=$rubric_id&region_id=$region_id&polygon=$polygon&page_size=50&fields=$fields&page=$page";
                    $result = $client->get($url);
                    ApiGis::create([
                        'url' => $url,
                        'data' => $result->getBody(),
                        'descr' => "Список мест $this->city"
                    ]);
                }
            }
//            $this->save_place();
        }

//            $polygon = 'POLYGON((39.611585 59.337288,40.07794 59.334267,40.072156 59.150523,38.986034 59.105979,38.426083 59.179531,38.063961 59.066342,37.680388 59.063007,37.674991 59.20788,37.98548 59.312602,38.873687 59.226731,39.610251 59.262879,39.611585 59.337288))';
//
//            $client = new Client();
//
////
//            $url = "http://catalog.api.2gis.ru/2.0/catalog/branch/list?key=$key&rubric_id=$rubric_id&region_id=$region_id&polygon=$polygon&page_size=50&fields=$fields&page=$page";
//            $result = $client->get($url);
//            ApiGis::create([
//                'url' => $url,
//                'data' => $result->getBody(),
//                'descr' => "Список рубрик $city"
////                'descr' => "Список рубрик по питанию $city"
////                'descr' => "Список мест $city"
//            ]);
////        }
//        dd(json_decode($result->getBody()));
    }

    public function search_one()
    {
        $one = ApiGis::where('descr', '70000001021531049_Blih9f66G44A312322304548t55kAu36G6G6G9I9G1I46BH1ostf9B9I2A7AG72003063816hw7huv6C075A63227BH2638AH3G4HG2IH3e')->first();
        dd(json_decode($one->data));
        $fields = 'items.region_id,items.dates,items.photos,items.see_also,items.flags,items.locale,items.schedule,items.name_ex,items.point,items.adm_div,items.address';
//        'items.ads'
        $id = '70000001021531049_Blih9f66G44A312322304548t55kAu36G6G6G9I9G1I46BH1ostf9B9I2A7AG72003063816hw7huv6C075A63227BH2638AH3G4HG2IH3e';
        $key = 'rulqsf7935';
        $client = new Client();
        $url = "http://catalog.api.2gis.ru/2.0/catalog/branch/get?key=$key&id=$id&fields=$fields";

        $result = $client->get($url);

        ApiGis::create([
            'url' => $url,
            'data' => $result->getBody(),
            'descr' => $id
        ]);
        dd(json_decode($result->getBody()));
    }

    public function delete_dubl()
    {
        $places = GisPlace::all();

        foreach ($places as $place)
        {
            $dubl = GisPlace::where('id', 'like', "$place->id%")->get();
            if(count($dubl) > 1)
            {
                GisPlace::where('id', 'like', "$place->id%")->first()->delete();
            }
        }
        dd(GisPlace::all());
    }

    public function save_place()
    {
        set_time_limit(0);
        $this->delete_dubl();
        dd('cdf');
        $city_ids = [65, 41, 11, 70, 89, 40, 61, 109, 5, 58, 94, 34, 23, 10, 73, 86, 56, 26, 113, 89, 96, 29, 82, 12, 45, 6, 74, 76, 103, 48, 71, 42, 80, 95, 90, 44, 85, 43, 63, 30, 57, 60, 54, 39, 72, 81, 47, 97, 22, 3, 36, 13, 37, 55, 83, 35, 53, 64, 88, 50, 28];
        $pp = ApiGis::where('url', 'http://catalog.api.2gis.ru/2.0/region/list?key=rulqsf7935&country_code_filter=ru&fields=items.name_grammatical_cases,items.domain,items.locales,items.time_zone,items.bounds,items.statistics,items.locale,items.settlements,items.satellites')->first();
        $pp = json_decode($pp->data)->result->items;
        set_time_limit(0);
        foreach ($pp as $city)
        {
            if (in_array($city->id, $city_ids)) {
                $city = $city->name;
                $places = ApiGis::where('descr', "Список мест $city")->get();
                foreach ($places as $place)
                {
                    foreach (json_decode($place->data)->result->items as $item)
                    {
                        $photos = [];
                        if(isset($item->flags->photos))
                        {
                            if($item->flags->photos)
                            {
                                foreach ($item->photos->items as $photo)
                                {
                                    $photos[] = $photo->urls->original;
                                }
                            }
                        }

                        GisPlace::create([
                            'lat' => isset($item->point->lat) ? $item->point->lat : 11.111111,
                            'lon' => isset($item->point->lon) ? $item->point->lon : 11.111111,
                            'name' => isset($item->name_ex->primary) ? $item->name_ex->primary : '',
                            'id' => $item->id,
                            'type' => isset($item->name_ex->extension) ? $item->name_ex->extension : '',
                            'options' => json_encode($item),
                            'photos' => json_encode($photos),
                            'city' => $city
                        ]);
                    }
                }
            }
        }
    }

    public function save_photos()
    {
        $places = GisPlace::where('photo_uploaded', false)->count();
        dd($places);
        $places = GisPlace::where('photo_uploaded', false)->limit(100)->get();
//        dd(($places));
        $client = new Client();
        set_time_limit(0);
        foreach ($places as $place)
        {
//            dd($place);
            $photos = [];
            if($place->photos != '[]')
            {
                $photos = json_decode($place->photos);
//                dd($photos);
                foreach ($photos as $index => $photo)
                {
                    try{
                        $client->get($photo);
                    } catch (\Exception $e){
//                        dd($e->getMessage());
                        if($e->getCode() == 404){
                            if(($key = array_search($photo, $photos)) !== false)
                            {
                                unset($photos[$key]);
                            }
                        }
                    }
                }
//                dd($photos);

                foreach ($photos as $index => $photo)
                {
                    $client->get($photo, ['sink' => "images/" . $place->id . '_' . ($index + 1) . '.jpg']);
                }
            }
            $place->update([
                'photo_uploaded' => true,
                'photos' => json_encode($photos),
            ]);
        }
        return redirect(route('save_photos'));
    }
}
