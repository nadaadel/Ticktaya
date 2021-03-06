<?php
namespace App\Http\Controllers;
use App\Article;
use App\Category;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DB;
class ArticlesController extends Controller
{

    public function index(){
        $articles = Article::all();
        if(Auth::check()){
            if(Auth::user()->hasrole('admin')){
                return view('admin.articles.index' , compact('articles'));
       }
        }

        return view('articles.index' , compact('articles'));
      }
      public function show($id){
        $article = Article::find($id);
        $liker="";
        if(Auth::check()){
            $liker=DB::table('article_user')->where(['article_id'=>$id,'user_id'=> Auth::user()->id])->first();
   
        if(Auth::user()->hasrole('admin')){
           return view('admin.articles.show' , compact('article','liker'));
       }

       }
       
        return view('articles.show' , compact('article','liker'));
       
    }
    public function create(){
       if(Auth::user()->hasrole('admin')){
        $categories = Category::all();
            return view('admin.articles.create'  , compact('categories'));
       }
       return view('notfound');
    }

    public function edit($id){
        if(Auth::user()->hasrole('admin')){
             $article = Article::find($id);
             $categories = Category::all();
             return view('admin.articles.edit' , compact('article' , 'categories'));
        }
        return view('notfound');
     }
    public function store(Request $request){
        if(Auth::user()->hasrole('admin')){
            $request->validate([
                'title'=>'required|min:10',
                'description'=>'required|min:100',
                'photo'=>'required|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
                'user_id' => 'exists:users,id',
                'category_id' => 'exists:categories,id',
            ]);
        $article = new Article();
        $article->title = $request->title;
        $article->user_id = Auth::user()->id;
        $article->description = Str::lower($request->description);
        $article->category_id = $request->category;

        if($request->hasFile('photo')){
            $request->file('photo')->store('public/images/articles');
            $file_name = $request->file('photo')->hashName();
            $article->photo= $file_name;
        $article->save();

        return redirect('/articles');
    }
  }
    return view('notfound');

  }
  public function update(Request $request, $id){
    if(Auth::user()->hasrole('admin')){
        $request->validate([
            'title'=>'required|min:10',
            'description'=>'required|min:100',
            'photo'=>'required|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
            'user_id' => 'exists:users,id',
            'category_id' => 'exists:categories,id',
        ]);
    $article = Article::find($id);
    $article->title = $request->title;
    $article->description = $request->description;
    $article->user_id = Auth::user()->id;
    $article->category_id = $request->category;

    if($request->hasFile('photo')){
        $request->file('photo')->store('public/images/articles');
        $file_name = $request->file('photo')->hashName();
        $article->photo= $file_name;
    $article->save();
    return redirect('/articles');
    }
   }

    return view('notfound');
 }
  public function delete($id){
      $article = Article::find($id)->delete();
    return redirect('/articles');

  }

  public function like ($id){
    
    if(Auth::check()){
    DB::table('article_user')->insert([
        'article_id' => $id,
        'user_id' => Auth::user()->id
   ]);
   $article=Article::find($id);
   $likes=$article->likes->count();

   return response()->json(['response' => 'success','likes'=>$likes]);
    }
  }

  public function dislike ($id){
    $disliker =DB::table('article_user')->where('article_id' ,'=' ,$id)
    ->where('user_id' , '=' , Auth::user()->id);
    $disliker->delete();
    $article=Article::find($id);  
    $likes=$article->likes->count();
   return response()->json(['response' => 'success','likes'=>$likes]);


  }
}
