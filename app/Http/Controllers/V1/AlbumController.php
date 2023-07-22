<?php

namespace App\Http\Controllers\V1;


use App\Http\Controllers\Controller;
use App\Http\Requests\AlbumRequest;
use App\Http\Requests\UpdateAlbumRequest;
use App\Http\Resources\V1\AlbumResource;
use App\Models\Album;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
      return AlbumResource::collection(Album::query()->where('user_id', $request->user()->id)->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AlbumRequest $request)
    {
        $data = $request->all();
        $data['user_id'] = $request->user()->id;

        $album = Album::create($data);

        return new AlbumResource($album);
    }

    /**
     * Display the specified resource.
     */
    public function show(Album $album)
    {
        if($album->user_id != request()->user()->id) return abort(403, 'Unauthorized');

        return new AlbumResource($album);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAlbumRequest $request, Album $album)
    {
        if($album->user_id != request()->user()->id) return abort(403, 'Unauthorized');
         $album->update($request->all());

         return new AlbumResource($album);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Album $album)
    {
        if($album->user_id != request()->user()->id) return abort(403, 'Unauthorized');
        $album->delete();

        return response('', 204);
    }
}
