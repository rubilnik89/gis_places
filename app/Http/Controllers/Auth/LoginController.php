<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;


class LoginController extends Controller
{
    use AuthenticatesUsers;
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */
    public function showLoginForm()
    {
        session_start();
        $fb = new \Facebook\Facebook([
            'app_id' => '1908224952787499', // Replace {app-id} with your app id
            'app_secret' => 'a4565739d8a0e75f5ad973f8164f1d13',
            'default_graph_version' => 'v2.9',
        ]);
        $helper = $fb->getRedirectLoginHelper();

        $permissions = ['public_profile,email,user_friends']; // Optional permissions
        $loginUrl = $helper->getLoginUrl('http://tegtegergregr.com/fb/fb_code', $permissions);

        return view('auth.login', compact('loginUrl'));
    }


    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
