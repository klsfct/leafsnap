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

//    public function request_post($url = '', $param = '') {
//            if (empty($url) || empty($param)) {
//                return false;
//            }
//
//            $postUrl = $url;
//            $curlPost = $param;
//            $curl = curl_init();//初始化curl
//            curl_setopt($curl, CURLOPT_URL,$postUrl);//抓取指定网页
//            curl_setopt($curl, CURLOPT_HEADER, 0);//设置header
//            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
//            curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
//            curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
//            $data = curl_exec($curl);//运行curl
//            curl_close($curl);
//
//            return $data;
//    }

    public function request_get($url = '',$param = '')
    {
        if (empty($url)) {
            return false;
        }

        $getUrl = $url;
        $getCurl = curl_init();//初始化curl
        curl_setopt($getCurl, CURLOPT_URL, $getUrl);//抓取指定网页
        curl_setopt($getCurl, CURLOPT_HEADER, false);
        curl_setopt($getCurl, CURLOPT_RETURNTRANSFER, true);
        if (!empty($param)) {
            curl_setopt($getCurl, CURLOPT_POST, 1);//如果存在param则采用post提交方式
            curl_setopt($getCurl, CURLOPT_POSTFIELDS, $param);
        }
        $data = curl_exec($getCurl);//运行curl
        curl_close($getCurl);

        return $data;
    }

    public function searchBySolr($strs = ''){
//        $postData = "strs=".$strs."&type=name";
//        $postUrl = 'http://124.205.250.31/lyportal/solr/solrList';
//        $zhikuParams = $this->request_get($postUrl, $postData);//运行curl

        $getUrl = 'http://zhishi.me/api/entity/'.$strs.'?baike=baidubaike';
        $getRes = json_decode($this->request_get($getUrl), true);

        $baikeParams = array();
        if(empty($getRes)){
            $baikeParams = null;
        }
        else if(array_key_exists('abstracts', $getRes)){
            $baikeParams = array('abstracts' => $getRes['abstracts'],'imageUrl' => $getRes['relatedImage'][0]);
        } else if(array_key_exists('pageDisambiguates', $getRes)){
            foreach ($getRes['pageDisambiguates'] as $page){
                $page = json_decode($this->request_get('http://zhishi.me/api/entity/'.trim(strrchr($page, '/'),'/').'?baike=baidubaike'), true);
                array_push($baikeParams, array('abstracts' => $page['abstracts'],'imageUrl' => $page['relatedImage'][0]));
            }
        } else if(array_key_exists('pageRedirects', $getRes)){
            foreach ($getRes['pageRedirects'] as $page){
                $page = json_decode($this->request_get('http://zhishi.me/api/entity/'.trim(strrchr($page, '/'),'/').'?baike=baidubaike'), true);
                array_push($baikeParams, array('abstracts' => $page['abstracts'],'imageUrl' => $page['relatedImage'][0]));
            }
        } else{
            $baikeParams = $getRes;
        }
        return $baikeParams;
    }

    public function leaf(Request $request){

        $path = $request->file('file')->storePublicly(md5(\Auth::id() . time()));
//        return asset('/storage/'. $path);



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

        $res = $this->request_get($url, $post_data);


        $token = json_decode($res, true)['access_token'];
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/plant?access_token=' . $token;
        $img = file_get_contents(asset('/storage/'. $path));
        $img = base64_encode($img);
        $bodys = array(
            'image' => $img
        );
        $res = $this->request_get($url, $bodys);

        $imgUrl = asset('/storage/'. $path);
        $params = array_merge(['imgUrl' => asset('/storage/'. $path)], ['res' => $res],['created_at' => date("Y-m-d H:i:s",time())],['updated_at' => date("Y-m-d H:i:s",time())]);
        $id = LeafsnapRes::insertGetId($params);
        $res = json_decode($res, true);

        if(!array_key_exists('name', $res['result'])) {
            foreach ($res['result'] as &$plant) {
                $plant['desList'] = $this->searchBySolr($plant['name']);
            }
        }

        return compact('id', 'res', 'imgUrl');
    }

    public function shareIndex(LeafsnapRes $leafsnapRes){
        $leafsnapRes = LeafsnapRes::find($leafsnapRes->id);
        return compact('leafsnapRes');
    }

}
