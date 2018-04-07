<?php

namespace App\Http\Controllers;

use App\Circle;
use Illuminate\Http\Request;
use App\User;
use App\LeafsnapRes;
use App\lib\leafSnapAPI\AipImageClassify;

class UserController extends Controller
{
    /*
     * 个人介绍页面
     */
    public function show(Request $request, User $user)
    {
        // 这个人的文章
        $posts = $user->posts()->withCount('targets', 'comments')->orderBy('created_at', 'desc')->take(10)->get();
        // 这个人的关注／粉丝／文章
        $user = \App\User::withCount(['stars', 'fans', 'posts'])->find($user->id);
        $circles = $user->circles()->with( 'circleImgs')->withCount('targets', 'comments')->orderBy('created_at', 'desc')->take(20)->get();
        $fans = $user->fans($user->id)->get();
        $stars = $user->stars()->get();

        if($request['type'] == 'ajax'){
            return compact('user', 'posts', 'circles', 'fans', 'stars');
        }
        return view("user/show", compact('user', 'posts', 'fans', 'stars'));
    }

    public function fan(User $user)
    {
        $me = \Auth::user();
//        \App\Fan::firstOrCreate(['fan_id' => $me->id, 'star_id' => $user->id]);
        \App\Relationship::firstOrCreate(['user_id' => $me->id, 'target_id' => $user->id, 'target_type' => 'App\User']);
        return [
            'error' => 0,
            'msg' => ''
        ];
    }

    public function unfan(User $user)
    {
        $me = \Auth::user();
//        \App\Fan::where('fan_id', $me->id)->where('star_id', $user->id)->delete();
        \App\Relationship::where('user_id',$me->id)->where('target_id', $user->id)->where('target_type', 'App\User')->delete();
        return [
            'error' => 0,
            'msg' => ''
        ];
    }

    public function setting()
    {
        $me = \Auth::user();
        return view('user/setting', compact('me'));
    }

    public function settingStore(Request $request, User $user)
    {
        $this->validate(request(),[
            'nickName' => 'min:3',
        ]);

        $nickName = request('nickName');
        if ($nickName != $user->nickName) {
            if(\App\User::where('nickName', $nickName)->count() > 0) {
                return back()->withErrors(array('message' => '用户名称已经被注册'));
            }
            $user->nickName = request('nickName');
        }
        if ($request->file('avatarUrl')) {
//            return back()->withErrors(array('message' => $request->file('avatarUrl')));
            $path = $request->file('avatarUrl')->storePublicly(md5(\Auth::id() . time()));
            $user->avatarUrl = env('APP_URL')."/storage/". $path;
        }

        $user->save();
        return back();
    }



    public function leaf(Request $request){

        $path = $request->file('file')->storePublicly(md5(\Auth::id() . time()));
//        return asset('/storage/'. $path);


        function request_post($url = '', $param = '') {
            if (empty($url) || empty($param)) {
                return false;
            }

            $postUrl = $url;
            $curlPost = $param;
            $curl = curl_init();//初始化curl
            curl_setopt($curl, CURLOPT_URL,$postUrl);//抓取指定网页
            curl_setopt($curl, CURLOPT_HEADER, 0);//设置header
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
            curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
            curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
            $data = curl_exec($curl);//运行curl
            curl_close($curl);

            return $data;
        }

        $url = 'https://aip.baidubce.com/oauth/2.0/token';
        $post_data['grant_type']       = 'client_credentials';
        $post_data['client_id']      = env('BAIDU_KEY');
        $post_data['client_secret'] = env('BAIDU_SECRET');
        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);

        $res = request_post($url, $post_data);
        $token = json_decode($res, true)['access_token'];
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/plant?access_token=' . $token;
        $img = file_get_contents(asset('/storage/'. $path));
        $img = base64_encode($img);
        $bodys = array(
            'image' => $img
        );
        $res = json_decode(request_post($url, $bodys), true);

        $leafsnapRes = new LeafsnapRes();
        $leafsnapRes->imgUrl = $path;
        $leafsnapRes->res = $res;
        $leafsnapRes->create();

        return compact('leafsnapRes');
    }

    public function shareIndex(LeafsnapRes $leafsnapRes){
        $leafsnapRes = LeafsnapRes::get($leafsnapRes->id);
        return compact('leafsnapRes');
    }
}
