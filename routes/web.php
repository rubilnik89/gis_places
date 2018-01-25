<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('search', 'SocialNetworksController@search')->name('search');
Route::get('search_one', 'SocialNetworksController@search_one')->name('search_one');
Route::get('save_place', 'SocialNetworksController@save_place')->name('save_place');
Route::get('save_photos', 'SocialNetworksController@save_photos')->name('save_photos');
Route::get('google', 'SocialNetworksController@google')->name('google');
Route::get('google/code', 'SocialNetworksController@google_code')->name('google_code');
Route::get('google/sign_out', 'SocialNetworksController@google_sign_out')->name('google_sing_out');
Route::get('fb/fb_code', 'SocialNetworksController@fb_code')->name('fb_code');
Route::get('fb/sign_out', 'SocialNetworksController@fb_sign_out')->name('fb_sing_out');
Route::get('twitter', 'SocialNetworksController@twitter')->name('twitter');
Route::get('twitter/sign', 'SocialNetworksController@twitter_sign')->name('twitter_sign');
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
