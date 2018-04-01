<?php

namespace App\Http\Controllers;

use App\Circle;
use App\CircleImg;
use App\Topic;
use App\Comment;
use App\Relationship;
use Illuminate\Http\Request;

class LeafsnapController extends Controller
{
    /*
     * 文章列表
     */
    public function index(Request $request)
    {
        $user = \Auth::user();
        $banners = Topic::with('posts')->find(1);
        $hotTopic = Topic::where('parent_id', 13)->get();
        $circles = Circle::aviable()->orderBy('created_at', 'desc')->with('user', 'circleImgs')->withCount(["targets", "comments"])->paginate(20);
        return compact('banners', 'hotTopic', 'circles');
    }

    public function circleList(Request $request)
    {
        $circles = Circle::aviable()->orderBy('created_at', 'desc')->with('user', 'circleImgs')->withCount(["targets", "comments"])->paginate(20);
        return compact('circles');
    }

    public function imageUpload(Request $request)
    {
        $path = $request->file('file')->storePublicly(md5(\Auth::id() . time()));
        return asset('/storage/'. $path);
    }

    public function store(Request $request)
    {
//        $this->validate($request, [
//            'content' => 'required|min:10',
//        ]);
//        $params = array_merge(request(['content']), ['user_id' => \Auth::id()]);
        $circle = new Circle();
        $circle->content = request('content');
        $circle->user_id = \Auth::id();
        $circle->save();
        if( !empty( request('circle_imgs'))){
            foreach (request('circle_imgs') as $imgUrl) {
                $ci = new CircleImg(compact('imgUrl'));
                $circle->circleImgs()->save($ci);
            }
        }
        return compact('circle');
    }

    public function edit(Circle $circle)
    {
        return view('circle/edit', compact('circle'));
    }

    public function show(Request $request, \App\Circle $circle)
    {
        $circle = Circle::with('user', 'comments.user', 'circleImgs', 'targets')->find($circle->id);
        $hasZan = \Auth::user()->hasZan($circle->id, 'App\Circle');
        return compact('circle', 'hasZan');
    }

    public function update(Request $request, Circle $circle)
    {

        $this->authorize('update', $circle);

        $circle->update(request(['title', 'content']));
        return redirect("/circles/{$circle->id}");
    }

    /*
     * 文章评论保存
     */
    public function comment(Request $request)
    {
        $this->validate(request(),[
            'circle_id' => 'required|exists:circles,id'
        ]);

        $user_id = \Auth::id();
        $comment = new Comment();
        $comment->user_id = $user_id;
        $comment->content = request('content');
        $circle = new circle();
        $circle->id = request('circle_id');
//        $params = array_merge(
//            compact('commentable_id'),
//            request(['content']),
//            compact('user_id'),
//            compact('parent_id')
//        );
//        \App\Comment::create($params);
        $circle->commentable()->save($comment);
        return compact('circle');
    }

    /*
     * 点赞
     */
    public function zan(Circle $circle)
    {
        //commentable_type取值例如：App\Post，App\Page等等
//        $target = app('App\Post')->where('id', $post->id)->firstOrFail();
        $relationship = new Relationship();
        $relationship->user_id = \Auth::id();

        $circle->targets()->save($relationship);
        return compact('circle');
    }

    /*
     * 取消点赞
     */
    public function unzan(Circle $circle)
    {
        $circle->target(\Auth::id())->delete();
        return compact('circle');
    }

    /*
     * 搜索页面
     */
    public function search()
    {
        $this->validate(request(),[
            'query' => 'required'
        ]);

        $query = request('query');
        $circles = Circle::search(request('query'))->paginate(10);
        return view('circle/search', compact('circles', 'query'));
    }
}