<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    /**
     * Store a Trix attachment and return its public URL so the editor can
     * embed it in the comment body.
     */
    public function store(Request $request): JsonResponse
    {
        $maxSize = (int) config('nova-comments.attachments.max-size', 12288);
        $mimes = config('nova-comments.attachments.mimes');

        $rules = ['file', 'max:'.$maxSize];

        if (is_array($mimes) && $mimes !== []) {
            $rules[] = 'mimes:'.implode(',', $mimes);
        }

        $validated = $request->validate([
            'attachment' => $rules,
        ]);

        $file = $validated['attachment'];

        $disk = (string) config('nova-comments.attachments.disk', 'public');
        $directory = trim((string) config('nova-comments.attachments.path', 'nova-comments/attachments'), '/');

        $name = Str::ulid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $name, ['disk' => $disk]);

        return response()->json([
            'url' => Storage::disk($disk)->url((string) $path),
        ]);
    }

    /**
     * Delete an attachment that was removed from the editor before the comment
     * was saved. Best-effort: a missing file is not an error.
     */
    public function destroy(Request $request): JsonResponse
    {
        $url = (string) $request->input('url', '');
        $directory = trim((string) config('nova-comments.attachments.path', 'nova-comments/attachments'), '/');

        $marker = $directory.'/';
        $position = strpos($url, $marker);

        if ($position !== false) {
            $disk = (string) config('nova-comments.attachments.disk', 'public');
            $path = substr($url, $position);

            Storage::disk($disk)->delete($path);
        }

        return response()->json(['deleted' => true]);
    }
}
