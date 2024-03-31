<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;

// use Intervention\Image\Image;
use App\Events\OurExampleEvent;
use Illuminate\Validation\Rule;
// use Illuminate\Support\Facades\Storage;
use App\Events\OurExampleListener;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;

class UserController extends Controller
{
    public function storeAvatar(Request $request) {
        $request->validate([
            'avatar'=>'required|image|max:3000'
        ]);

        $user = auth()-> user();

        $filename = $user->id . '-' . uniqid() . '.jpg';

        $image = $request->file('avatar');
        $ext = $image->extension();
        $imgData = file_get_contents($image);
        Storage::put('public/avatars/' . $filename, $imgData);

        // update the database with the new avatar
        /** @var \App\Models\User $user **/

        $oldAvatar = $user->avatar;

        $user->avatar = $filename;
        $user->save();

        // delete old avatar if it exists
        if ($oldAvatar != "/fallback-avatar.jpg") {
            Storage::delete(str_replace("/storage/", "public/", $oldAvatar));
        };

        return back()->with('success', 'Avatar updated!');
    }

    public function showAvatarForm() {
        return view('avatar-form');
    }

    private function getSharedData(User $user) {
        // if not logged in, set the default as false
        $currentlyFollowing = 0;

        if (auth()->check()) {
            $currentlyFollowing = Follow::where([
                ['user_id', '=', auth()->user()->id], 
                ['followeduser', '=', $user->id]
            ])->count();
        }

        View::share('sharedData', [
            'currentlyFollowing' => $currentlyFollowing,
            'avatar' => $user->avatar,
            'username' => $user->username, 
            'postCount'=>$user->posts()->count(),
            'followerCount'=>$user->followers()->count(),
            'followingCount'=>$user->followingTheseUsers()->count(),
        ]);
    }

    public function profile(User $user) { 
        $this->getSharedData($user);

        return view('profile-posts', [
            'posts'=>$user->posts()->latest()->get(),
        ]);
    }

    // 'raw' links
    public function profileRaw(User $user) { 
      
        return response()->json([
            'theHtml' => view('profile-posts-only', ['posts' => $user->posts()->latest()->get()])->render(),
            'docTitle' => $user->username . "'s profile"
        ]);
    }


    public function profileFollowers(User $user) {
        $this->getSharedData($user);
       
        return view('profile-followers', [
            'followers'=>$user->followers()->latest()->get(),
        ]);
    }

    // 'raw' links for profile followers
    public function profileFollowersRaw(User $user) {
       
        return response()->json([
            'theHtml' => view('profile-followers-only', ['followers'=>$user->followers()->latest()->get()])->render(),
            'docTitle' => $user->username . "'s followers"
        ]);
    }

    public function profileFollowing(User $user) {
        $this->getSharedData($user);

        return view('profile-following', [
            'following'=>$user->followingTheseUsers()->latest()->get(),
        ]);
    }

    // raw links for profile following
    public function profileFollowingRaw(User $user) {

        return response()->json([
            'theHtml'=> view('profile-following-only', ['following'=>$user->followingTheseUsers()->latest()->get()])->render(),
            'docTitle'=> $user->username . "'s following list"
        ]);
    }

    public function logout() {
        if (auth()->check()) {
            $username = auth()->user()->username;
            auth()->logout();

            event(new OurExampleEvent([
                'username'=>$username, 
                'action'=>"logged out"
            ]));
        } else {
           Log::warning("Someone tried to log out without being logged in"); 
        }
        return redirect('/')->with('success', 'You are now logged out.');
    }

    public function showCorrectHomePage() {
        if (auth()->check()) {
            return view('homepage-feed', ['posts'=>auth()->user()->feedPosts()->latest()->paginate(5)]);
        } else {
            $postCount = Cache::remember('postCount', 10, function() {
                return Post::count();
            });
            return view('homepage', ['postCount'=>$postCount]);
        }
    }

    public function loginApi(Request $request) {
        $incomingFields = $request->validate([
            'username'=> 'required',
            'password'=>'required'
        ]);

        if (auth()->attempt($incomingFields)) {
            $user = User::where('username', $incomingFields['username'])->first();
            $token = $user->createToken('ourapptoken')->plainTextToken;
            return $token;
        }
        return 'Details are incorrect';
    }


    public function login(Request $request) {
        $incomingFields = $request->validate([
            'loginusername'=> ['required'],
            'loginpassword'=> ['required']
        ]);

        if (auth()-> attempt([
            'username' => $incomingFields['loginusername'], 
            'password' => $incomingFields['loginpassword']])) {
            
            $request->session()->regenerate();
            event(new OurExampleEvent([
                'username'=>auth()->user()->username, 
                'action' => "logged in"
            ]));
            return redirect('/')->with('success', 'You have successfully logged in!');
        } else {
            return redirect('/')->with('failure', 'Invalid login. Please confirm your details and try again.');
        }
    }

    public function register(Request $request) {
        $incomingFields = $request->validate([
            'username'=> ['required', 'min:3', 'max:20', Rule::unique('users', 'username')],
            'email'=> ['required', 'email', Rule::unique('users', 'email')],
            'password'=> ['required', 'min:8', 'confirmed']
        ]);
        $incomingFields['password'] = bcrypt($incomingFields['password']);
        
        $user = User::create($incomingFields);
        auth()->login($user);
        return redirect('/')->with('success', 'Your account has been created!');
    }
}
