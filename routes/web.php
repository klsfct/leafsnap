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
Route::get('/', "\App\Http\Controllers\LoginController@index");

//Route::group(['middleware' => 'auth:web'], function(){
    // 文章
    Route::get('/posts', '\App\Http\Controllers\PostController@index');
    Route::get('/posts/senJiuTang', '\App\Http\Controllers\PostController@senJiuTang');
    Route::get('/posts/activity', '\App\Http\Controllers\PostController@activity');
    Route::get('/posts/search', '\App\Http\Controllers\PostController@search');
    Route::get('/posts/create', '\App\Http\Controllers\PostController@create');
    Route::post('/posts/img/upload', '\App\Http\Controllers\PostController@imageUpload');
    Route::post('/posts/comment', '\App\Http\Controllers\PostController@comment');
    Route::post('/posts', '\App\Http\Controllers\PostController@store');
    Route::get('/posts/{post}', '\App\Http\Controllers\PostController@show');
    Route::get('/posts/{post}/edit', '\App\Http\Controllers\PostController@edit');
    Route::put('/posts/{post}', '\App\Http\Controllers\PostController@update');
    Route::get('/posts/{post}/zan', '\App\Http\Controllers\PostController@zan');
    Route::get('/posts/{post}/unzan', '\App\Http\Controllers\PostController@unzan');

    // 个人主页
    Route::post('/user/leafSnap', '\App\Http\Controllers\UserController@leaf');
    Route::get('/user/{user}', '\App\Http\Controllers\UserController@show');
    Route::post('/user/{user}/fan', '\App\Http\Controllers\UserController@fan');
    Route::post('/user/{user}/unfan', '\App\Http\Controllers\UserController@unfan');

    //多态
//    Route::get('/user/{user}/rel','\App\Http\Controllers\ZanController@zan');

    // 个人设置
    Route::get('/user/{user}/setting', '\App\Http\Controllers\UserController@setting');
    Route::post('/user/{user}/setting', '\App\Http\Controllers\UserController@leaf');

    // 专题
    Route::get('/topic/{topic}', '\App\Http\Controllers\TopicController@show');
    Route::get('/topic/index/{topic}', '\App\Http\Controllers\TopicController@index');
    Route::get('/topic/{topic}/submit', '\App\Http\Controllers\TopicController@submit');

    //圈子
    Route::get('/circles', '\App\Http\Controllers\CircleController@index');
    Route::get('/circles/create', '\App\Http\Controllers\CircleController@create');
    Route::get('/circles/circleList', '\App\Http\Controllers\CircleController@circleList');
//    Route::get('/circles/search', '\App\Http\Controllers\PostController@search');
    Route::post('/circles', '\App\Http\Controllers\CircleController@store');
    Route::get('/circles/{circle}', '\App\Http\Controllers\CircleController@show');
    Route::get('/circles/{circle}/edit', '\App\Http\Controllers\CircleController@edit');
    Route::put('/circles/{circle}', '\App\Http\Controllers\CircleController@update');
    Route::post('/circles/img/upload', '\App\Http\Controllers\CircleController@imageUpload');
    Route::post('/circles/comment', '\App\Http\Controllers\CircleController@comment');
    Route::get('/circles/{circle}/zan', '\App\Http\Controllers\CircleController@zan');
    Route::get('/circles/{circle}/unzan', '\App\Http\Controllers\CircleController@unzan');

    // 通知
    Route::get('/notices', '\App\Http\Controllers\NoticeController@index');
//});

Route::get('/login', "\App\Http\Controllers\LoginController@index")->name('login');
Route::post('/login', "\App\Http\Controllers\LoginController@login");
Route::post('/loginWeChat', "\App\Http\Controllers\LoginController@loginWeChat");
Route::get('/logout', "\App\Http\Controllers\LoginController@logout");

Route::get('/register', "\App\Http\Controllers\RegisterController@index");
Route::post('/register', "\App\Http\Controllers\RegisterController@register");

include_once("admin.php");