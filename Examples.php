<?php
////////////////////////////////////////////////////////////////////

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
use Auth;
use App\Http\Requests\SavePostRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;


class PostController extends Controller
{
    public function index()
    {
        $posts = Post::latest('created_at')->paginate(3);

        return view('post.index')
            ->with('posts', $posts);
    }

    /**
     * Display the specified resource
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function show($slug)
    {
        $post = Post::whereSlug($slug)
                    ->firstOrFail();

        return view('post.show')
            ->with('post', $post);
    }

    /** Show the form for creating a new resource. ... */
    public function create()
    {
        return view('post.create')
            ->with('title', 'Add new post');
    }

    /** Store a newly created resource in storage
     *
     * hint: VALIDATION happens in app/Http/Requests/SavePostRequest
     *
     * @param SavePostRequest $request
     * @return \Illuminate\Http\RedirectResponse|Response
     */
    public function store(SavePostRequest $request)
    {
        // create new post for this user
        $post = $this->createPost($request);

        // success message
        flash()->success('Added!');

        // redirect to new post
        return redirect()->route('post.show', $post->slug);
    }

    /** Show the form for editing the specified resource. ... */
    public function edit($id)
    {
       $post = Post::findOrFail($id);

       $this->authorize('edit-post', $post);

       $post->tags;

       return view('post.edit')
           ->with('title', 'Edit post')
           ->with('post', $post);
    }

    /** Show the form for removing the specified resource. ...
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|Response
     */
    public function delete($id)
    {
        $post = Post::findOrFail($id);

        $this->authorize('edit-post', $post);

        return view('post.delete')
            ->with('post', $post);
    }

    /**
     * Update the specified resource in storage
     *
     * @param SavePostRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|Response
     */
    public function update(SavePostRequest $request, $id)
    {
        $post = Post::findOrFail($id);

        $this->authorize('edit-post', $post);

        // update post
        $post->update( $request->all() );

        // attach tags
        $this->syncTags($post, $request->get('tags'));

        // redirect to post
        flash()->success('updated!');
        return redirect()->route('post.show', $post->slug);
    }

    /**
     * Synchronize tags for this post
     *
     * @param $post
     * @param $tags
     */
    private function syncTags($post, $tags): void
    {
        $post->tags()->sync($tags ?: []);
    }

    /**
     * Create new post
     *
     * @param SavePostRequest $request
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function createPost(SavePostRequest $request): \Illuminate\Database\Eloquent\Model
    {
        // create new post for this user
        $post = \Auth::user()->posts()->create($request->all());

        // attach tags to post
        $this->syncTags($post, $request->get('tags'));

        return $post;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|Response|\Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        // if authorized, delete
        $this->authorize('edit-post', $post);
        $post->delete();

        //go home
        flash()->success("it's gone!");
        return redirect('/');
    }
}

////////////////////////////////////////////////////////////////////////////////

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TagController extends Controller
{
    public function show($id)
    {
        $tag = Tag::findOrfail($id);

        return view('post.index')
            ->with('title', $tag->tag)
            ->with('posts', $tag->posts);
    }
}

///////////////////////////////////////////////////////////////////

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->unsigned();
            $table->string('title', 200);
            $table->text('text');
            $table->string('slug', 200);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}

////////////////////////////////////////////////////////////////
