<?php

namespace App\Http\Controllers\Client;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Content;
use App\Category;
use App\ChannelContent;
use App\ContentMonetize;
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

    protected function client()
    {
        //$user = Auth::id();
        //dd(Auth::user()->parent_id);
        if (Auth::user()->parent_id)
            $client_id = Auth::user()->parent_id;
        else
            $client_id = Auth::id();
        return $client_id;
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['page_title'] = 'Videos';
        if ($this->client() == Auth::id())
            $data['contents'] = Content::all()->where('client_id', Auth::id())->sortByDesc('created_at');
        else
            $data['contents'] = Content::all()->where('created_by', Auth::id())->sortByDesc('created_at');

        // $client = Auth::user();
        // $data['contents'] = $client->client_contents;
        // dd($data['contents']);

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
                //'videofile' => 'sometimes|nullable|mimes:mpeg,ogg,mp4,webm,3gp,mov,flv,avi,wmv,ts|max:' . config('constants.MAX_VIDEO_UPLOAD_SIZE'),
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
        $tags = ($request->tags) ? explode(',', $request->tags) : '';
        $display_tags = ($request->display_tags) ? explode(',', $request->display_tags) : '';

        $save_data = [
            'category_id' => $request->category,
            'name' => $request->name,
            'banner_image' => $image_path,
            'language' => $request->language,
            'genres' => $request->genres,
            //'content_link' => ($video_path) ?? '',
            //'format' => ($video_extension) ?? '',
            'tags' => json_encode($tags),
            'display_tags' => json_encode($display_tags),
            'client_id' => $this->client(),
            'artist' => $request->artist,
            'castandcrew' => $request->castandcrew,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];
        //dd($save_data);
        $content = Content::create($save_data);
        if ($content) {
            return redirect('client/contents/view/' . $content->id)->with('success', "Content Added Successfully.");
        } else {
            return redirect('client/contents')->with('failure', "Oops! Content Not added.");
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data['content'] = Content::findorfail($id);
        $this->authorize('edit', $data['content']);
        $data['page_title'] = 'Update Content';
        $data['categories'] = Category::all()->sortBy('name');
        $data['languages'] = Settings::LANGUAGES;
        $data['genres'] = Settings::GENRES;

        return view('client.content.edit', $data);
    }
    /**
     * Update a created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data['page_title'] = 'Content';
        $data['categories'] = Category::all()->sortBy('name');
        $data['languages'] = Settings::LANGUAGES;
        $data['genres'] = Settings::GENRES;

        $validationData = $request->validate(
            [
                'category' => ['required'],
                'name' => ['required', 'string', 'max:255'],
                //'videofile' => 'sometimes|nullable|mimes:mpeg,ogg,mp4,webm,3gp,mov,flv,avi,wmv,ts|max:' . config('constants.MAX_VIDEO_UPLOAD_SIZE'),
                'file' => 'sometimes|nullable|mimes:png,PNG,jpg,JPG,jpeg,JPEG|max:' . config('constants.MAX_FILE_UPLOAD_SIZE'),
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

        //print($duration);
        $tags = ($request->tags) ? explode(',', $request->tags) : '';
        $display_tags = ($request->display_tags) ? explode(',', $request->display_tags) : '';

        $save_data = [
            'category_id' => $request->category,
            'name' => $request->name,
            'language' => $request->language,
            'genres' => $request->genres,
            //'content_link' => ($video_path) ?? '',
            //'format' => ($video_extension) ?? '',
            'tags' => json_encode($tags),
            'display_tags' => json_encode($display_tags),
            //'client_id' => $this->client(),
            'artist' => $request->artist,
            'castandcrew' => $request->castandcrew,
            'description' => $request->description,
            'updated_by' => Auth::id(),
        ];
        if (isset($image_path))
            $save_data = array_merge($save_data, ['banner_image' => $image_path]);
        //dd($save_data);
        $content = Content::whereId($id)->update($save_data);
        if ($content) {
            return redirect('client/contents/view/' . $id)->with('success', "Content Update Successfully.");
        } else {
            return redirect('client/contents/view/' . $id)->with('failure', "Oops! Content Not Updated.");
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function video_add($id)
    {
        $data['content'] = Content::findorfail($id);

        $data['page_title'] = 'Content Add';
        return view('client.content.video_add', $data);
    }

    public function video_store(Request $request, $id)
    {
        $data['page_title'] = 'Add Video';
        if ($request->hasfile('videofile')) {
            $video = $request->file('videofile');
            $video_name = time() . '_' . $video->getClientOriginalName();
            $video_extension = $video->getClientOriginalExtension();
            //$video_path = $request->file('videofile')->storeAs('uploads', $video_name);
            $duration = Settings::getDuration($video);
            $video_path = 'client_videos/' . $video_name;
            //Storage::disk('s3')->put($video_path, file_get_contents($video));
            Storage::disk('s3')->put($video_path, \fopen($video, 'r+'));
        }
        $save_data = [
            'content_link' => ($video_path) ?? '',
            'format' => ($video_extension) ?? '',
            'duration' => ($duration) ?? '',
            'updated_by' => Auth::id(),
        ];
        // dd($save_data);
        $content = Content::whereId($id)->update($save_data);
        //dd($save_data['content_link']);
        if ($content) {
            return response()->json(['file' => $video_path]);
            //return response()->json(array('file' => $content->content_link), 200);
        } else {
            return response()->json(['error' => 'File Not Uploaded']);
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view($id)
    {
        $user = Auth::user();
        $data['content'] = Content::findorfail($id);
        $this->authorize('view', $data['content']);
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
        $save_data = ['privacy_settings' => json_encode($privacy_data)];
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
            'description' => $request->description,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
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
            'description' => $request->description,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];
        //dd($save_data);
        $poster = Photo::create($save_data);
        if ($poster) {
            return redirect('client/contents/view/' . $id)->with('success', "Poster Added Successfully.");
        } else {
            return redirect('client/contents/view/' . $id)->with('failure', "Oops! Poster Not added.");
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function monetize_add($id)
    {
        $data['content'] = Content::findorfail($id);
        $data['page_title'] = 'Monetize';
        $data['currencies'] = Settings::CURRENCIES;
        return view('client.content.monetize_add', $data);
    }

    public function monetize_store(Request $request, $id)
    {
        $data['page_title'] = 'Monetize';
        $data['content'] = Content::findorfail($id);
        $validationData = $request->validate(
            [
                'price' => ['required', 'numeric'],
                'currency' => ['required', 'string'],
                'file' => 'required|mimes:png,PNG,jpg,JPG,jpeg,JPEG|max:' . config('constants.MAX_FILE_UPLOAD_SIZE'),
                //'description' => ['required', 'string'],
            ]
        );
        if ($request->hasfile('file')) {
            $file = $request->file('file');
            $file_name = time() . '_' . $file->getClientOriginalName();
            //$video_path = $request->file('videofile')->storeAs('uploads', $video_name);
            //$duration = Settings::getDuration($video);
            $file_path = 'coupons/' . $file_name;
            //Storage::disk('s3')->put($video_path, file_get_contents($video));
            Storage::disk('s3')->put($file_path, \fopen($file, 'r+'));
        }
        $save_data = [
            'price' => $request->price,
            'currency' => $request->currency,
            'giftcoupon_image' => $file_path,
            'content_id' => $id,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        //dd($save_data);
        $poster = ContentMonetize::create($save_data);
        $content = Content::whereId($id)->update(['monetize' => 'yes']);
        if ($poster) {
            return redirect('client/contents/view/' . $id)->with('success', "Monetized  Successfully.");
        } else {
            return redirect('client/contents/view/' . $id)->with('failure', "Oops! Monetize Not added.");
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function monetize_edit(Request $request, $id)
    {
        $data['content'] = ContentMonetize::findorfail($id);
        $data['page_title'] = 'Monetize';
        $data['currencies'] = Settings::CURRENCIES;
        return view('client.monetize.monetize_edit', $data);
    }

    public function monetize_update(Request $request, $id)
    {
        $data['content'] = ContentMonetize::findorfail($id);
        $data['page_title'] = 'Monetize';
        $data['currencies'] = Settings::CURRENCIES;
        $validationData = $request->validate(
            [
                'price' => ['required', 'numeric'],
                'currency' => ['required', 'string'],
                'file' => 'sometimes|nullable|mimes:png,PNG,jpg,JPG,jpeg,JPEG|max:' . config('constants.MAX_FILE_UPLOAD_SIZE'),
                //'description' => ['required', 'string'],
            ]
        );
        if ($request->hasfile('file')) {
            $file = $request->file('file');
            $file_name = time() . '_' . $file->getClientOriginalName();
            //$video_path = $request->file('videofile')->storeAs('uploads', $video_name);
            //$duration = Settings::getDuration($video);
            $file_path = 'coupons/' . $file_name;
            //Storage::disk('s3')->put($video_path, file_get_contents($video));
            Storage::disk('s3')->put($file_path, \fopen($file, 'r+'));
        }
        $save_data = [
            'price' => $request->price,
            'currency' => $request->currency,
            'content_id' => $request->content_id,
            'updated_by' => Auth::id(),
        ];
        if (isset($file_path))
            $save_data = array_merge($save_data, ['giftcoupon_image' => $file_path]);

        //dd($save_data);
        //dd($data['content']['content_id']);
        $poster = ContentMonetize::whereId($id)->update($save_data);
        if ($request->makeitfree == 'yes') {
            $content = Content::whereId($request->content_id)->update(['monetize' => 'no']);
            ContentMonetize::destroy($id);
        }
        if ($poster) {
            return redirect('client/contents/view/' . $request->content_id)->with('success', "Monetized  Successfully.");
        } else {
            return redirect('client/contents/view/' . $request->content_id)->with('failure', "Oops! Monetize Not added.");
        }
    }

    public function publish($id)
    {
        $data['content'] = Content::findorfail($id);
        $client = $data['content']->client;
        $channel = $client->channel;
        //dd($data['content']['id']);
        $client_slots = $client->client_subscription->clientsubscription['slots'];
        //dd($client_slots);
        $client_used_slots = ChannelContent::where('channel_id', $channel->id)->sum('number_of_slots');
        //dd($client_used_slots);


        //dd($available_slots);
        list($hours, $minutes, $sec) = explode(':', $data['content']['duration'], 3);
        $seconds = $sec + $minutes * 60 + $hours * 3600;
        //dd($client['slot_duration']);
        $default_slot_duration_in_sec = $client['slot_duration'] * 60;
        // dd($default_slot_duration_in_sec);
        $numberofslotsrequired = intval(ceil($seconds / $default_slot_duration_in_sec));
        //dd($numberofslotsrequired);
        if ($client_slots == '') {
            $available_slots = $numberofslotsrequired + 1;
        } else {
            $available_slots = $client_slots - $client_used_slots;
        }
        if ($numberofslotsrequired <= $available_slots) {
            // dd("u used " . $numberofslotsrequired . "slots");
            $save_data = [
                'channel_id' => $channel['id'],
                'content_id' => $data['content']['id'],
                'number_of_slots' => $numberofslotsrequired,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ];

            $content = ChannelContent::create($save_data);

            if ($content) {
                $update_data = ['publish' => 'yes'];
                Content::whereId($id)->update($update_data);

                return redirect('client/contents/view/' . $id)->with('success', "Content Published Successfully.");
            } else {
                return redirect('client/contents/view/' . $id)->with('failure', "Oops! Something went wrong.");
            }
        } else {
            return redirect('client/contents/view/' . $id)->with('failure', "Oops! $numberofslotsrequired slot(s) required to publish this content those many slots are Not available for you.");
        }

        //dd($client);




    }
}
