<?php

namespace App\Http\Controllers\Client;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Content;
use App\Category;
use App\Settings;
use App\Teaser;
use App\Photo;

class ContentController extends Controller
{


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:client');
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['page_title'] = 'Videos';
        $data['contents'] = Content::all()->where('client_id', Auth::id())->sortByDesc('created_at');
        return view('client.content.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['page_title'] = 'Content';
        $data['categories'] = Category::all()->sortBy('name');
        $data['languages'] = Settings::LANGUAGES;
        $data['genres'] = Settings::GENRES;
        return view('client.content.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data['page_title'] = 'Content';
        $data['categories'] = Category::all()->sortBy('name');
        $data['languages'] = Settings::LANGUAGES;
        $data['genres'] = Settings::GENRES;

        $validationData = $request->validate(
            [
                'category' => ['required'],
                'name' => ['required', 'string', 'max:255'],
                'videofile' => 'sometimes|nullable|mimes:mpeg,ogg,mp4,webm,3gp,mov,flv,avi,wmv,ts|max:' . config('constants.MAX_VIDEO_UPLOAD_SIZE'),
                'file' => 'required|mimes:png,PNG,jpg,JPG,jpeg,JPEG|max:' . config('constants.MAX_FILE_UPLOAD_SIZE'),
                'language' => ['required', 'string'],
                'genres' => ['required', 'string'],
                'artist' => ['required', 'string'],
                'castandcrew' => ['required', 'string'],
                'description' => ['required', 'string'],

            ]
        );
        if ($request->hasfile('file')) {
            $image = $request->file('file');
            $image_name = time() . '_' . $image->getClientOriginalName();
            //$image_path = $request->file('file')->storeAs('uploads', $image_name);
            $image_path = 'banner_images/' . $image_name;
            Storage::disk('s3')->put($image_path, file_get_contents($image));
        }
        if ($request->hasfile('videofile')) {
            $video = $request->file('videofile');
            $video_name = time() . '_' . $video->getClientOriginalName();
            $video_extension = $video->getClientOriginalExtension();
            //$video_path = $request->file('videofile')->storeAs('uploads', $video_name);
            //$duration = Settings::getDuration($video);
            $video_path = 'client_videos/' . $video_name;
            //Storage::disk('s3')->put($video_path, file_get_contents($video));
            Storage::disk('s3')->put($video_path, \fopen($video, 'r+'));
        }
        //print($duration);

        $save_data = [
            'category_id' => $request->category,
            'name' => $request->name,
            'banner_image' => $image_path,
            'language' => $request->language,
            'genres' => $request->genres,
            'content_link' => $video_path,
            'format' => $video_extension,
            'client_id' => Auth::id(),
            'artist' => $request->artist,
            'castandcrew' => $request->castandcrew,
            'description' => $request->description
        ];
        //dd($save_data);
        $content = Content::create($save_data);
        if ($content) {
            return redirect('client/contents')->with('success', "Content Added Successfully.");
        } else {
            return redirect('client/contents')->with('failure', "Oops! Content Not added.");
        }
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view($id)
    {
        $data['content'] = Content::findorfail($id);
        $data['page_title'] = 'Content Details';
        $data['countries'] = Settings::COUNTRIES;
        //$data['privacy_settings'] = json_decode($data['content']->privacy_settings);
        return view('client.content.view', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function change_privacy($id)
    {
        $data['content'] = Content::findorfail($id);

        $data['page_title'] = 'Content Privacy';
        $data['countries'] = Settings::COUNTRIES;
        // $data['genres'] = Settings::GENRES;
        return view('client.content.privacy', $data);
    }

    public function privacy_store(Request $request, $id)
    {
        $privacy_data = [
            'Allow_Comments' => $request->comments,
            'Allow_Ratings' => $request->ratings,
            'Allow_Child' => $request->child,
            'Restricted_Origins' => explode(',', $request->origins)
        ];
        // dd($privacy_data);
        $save_data = ['privacy' => 'yes', 'privacy_parameters' => json_encode($privacy_data)];
        $privacy = Content::whereId($id)->update($save_data);
        if ($privacy) {
            return redirect('client/contents/view/' . $id)->with('success', "Privacy Settings Changed Successfully.");
        } else {
            return redirect('client/contents/view' . $id)->with('failure', "Oops! Privacy Settings Not added.");
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function teaser_add($id)
    {
        $data['content'] = Content::findorfail($id);
        $data['page_title'] = 'Add Teaser';
        // $data['genres'] = Settings::GENRES;
        return view('client.content.teaser_add', $data);
    }
    public function teaser_store(Request $request, $id)
    {
        $data['page_title'] = 'Add Teaser';
        $data['content'] = Content::findorfail($id);


        $validationData = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'videofile' => 'required|mimes:mpeg,ogg,mp4,webm,3gp,mov,flv,avi,wmv,ts|max:' . config('constants.MAX_TEASER_UPLOAD_SIZE'),
                'description' => ['required', 'string'],
            ]
        );
        if ($request->hasfile('videofile')) {
            $video = $request->file('videofile');
            $video_name = time() . '_' . $video->getClientOriginalName();
            $video_extension = $video->getClientOriginalExtension();
            //$video_path = $request->file('videofile')->storeAs('uploads', $video_name);
            //$duration = Settings::getDuration($video);
            $video_path = 'teasers/' . $video_name;
            //Storage::disk('s3')->put($video_path, file_get_contents($video));
            Storage::disk('s3')->put($video_path, \fopen($video, 'r+'));
        }
        $save_data = [
            'name' => $request->name,
            'link' => $video_path,
            'content_id' => $id,
            'description' => $request->description
        ];
        //dd($save_data);
        $teaser = Teaser::create($save_data);
        if ($teaser) {
            return redirect('client/contents/view/' . $id)->with('success', "Teaser Added Successfully.");
        } else {
            return redirect('client/contents/view/' . $id)->with('failure', "Oops! Teaser Not added.");
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function poster_add($id)
    {
        $data['content'] = Content::findorfail($id);
        $data['page_title'] = 'Add Poster';
        // $data['genres'] = Settings::GENRES;
        return view('client.content.poster_add', $data);
    }

    public function poster_store(Request $request, $id)
    {
        $data['page_title'] = 'Add Poster';
        $data['content'] = Content::findorfail($id);
        $validationData = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'file' => 'required|mimes:png,PNG,jpg,JPG,jpeg,JPEG|max:' . config('constants.MAX_FILE_UPLOAD_SIZE'),
                'description' => ['required', 'string'],
            ]
        );
        if ($request->hasfile('file')) {
            $file = $request->file('file');
            $file_name = time() . '_' . $file->getClientOriginalName();
            //$video_path = $request->file('videofile')->storeAs('uploads', $video_name);
            //$duration = Settings::getDuration($video);
            $file_path = 'posters/' . $file_name;
            //Storage::disk('s3')->put($video_path, file_get_contents($video));
            Storage::disk('s3')->put($file_path, \fopen($file, 'r+'));
        }
        $save_data = [
            'name' => $request->name,
            'link' => $file_path,
            'content_id' => $id,
            'description' => $request->description
        ];
        //dd($save_data);
        $poster = Photo::create($save_data);
        if ($poster) {
            return redirect('client/contents/view/' . $id)->with('success', "Poster Added Successfully.");
        } else {
            return redirect('client/contents/view/' . $id)->with('failure', "Oops! Poster Not added.");
        }
    }
}