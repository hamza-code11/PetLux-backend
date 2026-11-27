<?php

namespace App\Http\Controllers;

use App\Models\CmsSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CmsSectionController extends Controller
{
    public function show($key)
    {
        $section = CmsSection::where('key', $key)->first();

        if (!$section) {
            return response()->json([
                'status'  => false,
                'message' => 'Section not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'key'     => $section->key,
                'content' => $section->content,
            ],
        ]);
    }

    public function update(Request $request, $key)
    {
        $section = CmsSection::firstOrCreate(['key' => $key]);
        $content = $request->input('content', []);

        // Food Section Image Upload
        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            ]);

            $path = $request->file('image')->store('cms', 'public');

            $content['image'] = $path;
        }

        if (!empty($content['categories']) && is_array($content['categories'])) {
            foreach ($content['categories'] as $idx => &$cat) {
                if ($request->hasFile("images.$idx")) {
                    $path = $request->file("images.$idx")->store('cms', 'public');
                    $cat['image'] = $path;
                }
            }
            unset($cat);
        }

        // Handle uploaded images: content.images[i].file (File)
        if (!empty($content['images']) && is_array($content['images'])) {
            foreach ($content['images'] as $idx => &$img) {
                // If a new file was uploaded for this slot
                if ($request->hasFile("images.$idx")) {
                    $path = $request->file("images.$idx")->store('cms', 'public');
                    $img['src'] = $path;   // store relative path
                }
                // Remove temp field
                unset($img['file']);
            }
            unset($img);
        }


        // Sync uploaded team images with team members
        if (
            !empty($content['images']) &&
            !empty($content['team']['members'])
        ) {
            foreach ($content['images'] as $img) {
                if (
                    isset($img['key'], $img['src']) &&
                    preg_match('/^team_(\d+)$/', $img['key'], $matches)
                ) {
                    $index = (int) $matches[1];

                    if (isset($content['team']['members'][$index])) {
                        $content['team']['members'][$index]['image'] = $img['src'];
                    }
                }
            }
        }
        

        $section->update(['content' => $content]);

        return response()->json([
            'status'  => true,
            'message' => 'Section updated successfully.',
            'data'    => [
                'key'     => $section->key,
                'content' => $section->content,
            ],
        ]);
    }
}
