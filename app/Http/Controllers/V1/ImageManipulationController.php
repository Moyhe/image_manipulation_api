<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResizeImageRequest;
use App\Http\Resources\V1\ImageManipulationResource;
use App\Models\Album;
use App\Models\ImageManipulation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;


class ImageManipulationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return  ImageManipulationResource::collection(ImageManipulation::query()->where('user_id', request()->user()->id)->paginate());
    }

    public function byAlbum(Album $album)
    {
        if($album->user_id != request()->user()->id) return abort(403, 'Unauthorized');

      $where = [
        'album_id' => $album->id,
      ];

      return  ImageManipulationResource::collection(ImageManipulation::query()->where($where)->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function resize(ResizeImageRequest $request)
    {
        $all = $request->all();

        /** @var UploadedFile|string $image */

         $image = $all['image'];

         unset($all['image']);

         $data = [
            'type' => ImageManipulation::TYPE_RESIZE,
            'data' => json_encode($all),
            'user_id' => $request->user()->id
         ];

         if(isset($all['album_id'])) {

            $album = Album::find($all['album_id']);
            if ($album->user_id != $request->user()->id) return abort(403, 'Unauthorized');

            $data['album_id'] = $all['album_id'];
         }

         $relativePath = 'images/'. Str::random(). '/';
         $absolutePath = public_path($relativePath);

         File::makeDirectory($absolutePath);

         if ($image instanceof UploadedFile) {

              $data['name'] = $image->getClientOriginalName();
              $fileName = pathinfo($data['name'], PATHINFO_FILENAME);
              $extension = $image->getClientOriginalExtension();

              $originalPath = $absolutePath . $data['name'];
              $image->move($absolutePath, $data['name']);


         } else {

            $data['name'] = pathinfo($image, PATHINFO_BASENAME);
            $fileName = pathinfo($image, PATHINFO_FILENAME);
            $extension = pathinfo($image, PATHINFO_EXTENSION);
            $originalPath = $absolutePath . $data['name'];

            copy($image, $originalPath);

         }

           $data['path'] = $relativePath . $data['name'];

           $w = $all['w'];
           $h = $all['h'] ?? false;


          list($width, $height, $image) = $this->getWidthAndHeight($w, $h, $originalPath);


          $resizedFilename = $fileName . '-resized.' . $extension;
          $image->resize($width, $height)->save($absolutePath . $resizedFilename);

          $data['output_path'] = $relativePath . $resizedFilename;

          $imageManipulation = ImageManipulation::create($data);

          return new ImageManipulationResource($imageManipulation);
    }

    protected function getWidthAndHeight($w, $h, $originalPath)
    {
        $image = Image::make($originalPath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        if (str_ends_with($w, '%')) {
            $ratioW = (float)(str_replace('%', '', $w));
            $ratioH = $h ? (float)(str_replace('%', '', $h)) : $ratioW;
            $newWidth = $originalWidth * $ratioW / 100;
            $newHeight = $originalHeight * $ratioH / 100;
        } else {
            $newWidth = (float)$w;
            $newHeight = $h ? (float)$h : ($originalHeight * $newWidth / $originalWidth);
        }

        return [$newWidth, $newHeight, $image];
    }



    /**
     * Display the specified resource.
     */
    public function show(ImageManipulation $image)
    {
        if($image->user_id != request()->user()->id) return abort(403, 'Unauthorized');
        return new ImageManipulationResource($image);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ImageManipulation $image)
    {
        if($image->user_id != request()->user()->id) return abort(403, 'Unauthorized');
        $image->delete();

        return response('', 204);
    }
}
