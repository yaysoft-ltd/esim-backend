<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flaggroup;
use App\Models\Language;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Systemflag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SystemFlagController extends Controller
{
    public function getSystemFlag(Request $req)
    {
        try {
            if (!Auth::check()) {
                return redirect('/admin/login');
            }

            $flagGroup = Flaggroup::whereNull('parentFlagGroupId')
                ->with([
                    'systemFlag',
                    'subGroup.systemFlag' => function ($q) {
                        $q->where('isActive', 1);
                    }
                ])
                ->get();

            $language = Language::all();

            return view('admin.systemflags.system-flag', compact('flagGroup', 'language'));
        } catch (\Exception $e) {
            return dd($e->getMessage());
        }
    }

    public function editSystemFlag(Request $req)
    {
        if (!Auth::guard('web')->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        DB::beginTransaction();
        try {
            $groups = $req->input('group', []);

            foreach ($groups as $groupIndex => $group) {
                // Top-level system flags
                if (!empty($group['systemFlag'])) {
                    foreach ($group['systemFlag'] as $flagIndex => $flag) {
                        // Check if there's a file upload for this specific flag
                        $fileKey = "group.{$groupIndex}.systemFlag.{$flagIndex}.value";
                        if ($req->hasFile($fileKey)) {
                            $flag['value'] = $req->file($fileKey);
                            $this->processAndUpdateFlag($flag);
                        } elseif (($flag['valueType'] ?? '') !== 'File' && ($flag['valueType'] ?? '') !== 'Video') {
                            // Only process non-file fields if no file upload
                            $this->processAndUpdateFlag($flag);
                        }
                        // Skip processing if it's a file/video field with no new upload
                    }
                }

                // Sub-groups
                if (!empty($group['subGroup'])) {
                    foreach ($group['subGroup'] as $subGroupIndex => $subGroup) {
                        if (!empty($subGroup['systemFlag'])) {
                            foreach ($subGroup['systemFlag'] as $subFlagIndex => $flag) {
                                // Check if there's a file upload for this specific sub-group flag
                                $fileKey = "group.{$groupIndex}.subGroup.{$subGroupIndex}.systemFlag.{$subFlagIndex}.value";
                                if ($req->hasFile($fileKey)) {
                                    $flag['value'] = $req->file($fileKey);
                                    $this->processAndUpdateFlag($flag);
                                } elseif (($flag['valueType'] ?? '') !== 'File' && ($flag['valueType'] ?? '') !== 'Video') {
                                    // Only process non-file fields if no file upload
                                    $this->processAndUpdateFlag($flag);
                                }
                                // Skip processing if it's a file/video field with no new upload
                            }
                        }
                    }
                }
            }

            // Optional: update subgroup isActive via flaggroups[..]
            foreach ($req->input('flaggroups', []) as $id => $payload) {
                Flaggroup::whereKey($id)->update([
                    'isActive' => isset($payload['isActive']) ? (int) $payload['isActive'] : 0,
                ]);
            }

            DB::commit();
            return response()->json(['success' => 'SystemFlag Updated']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Update failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function processAndUpdateFlag(array $flag): void
    {
        if (!array_key_exists('name', $flag)) {
            return;
        }

        $name = $flag['name'];
        $type = $flag['valueType'] ?? null;
        $value = $flag['value'] ?? null;

        // Handle upload/clear for File & Video
        if ($type === 'File' || $type === 'Video') {
            // Only process if we have an actual file upload
            if ($value instanceof UploadedFile) {
                $newPath = $this->handleUploadOrClear($name, $value, $type);
                if ($newPath !== null) {
                    Systemflag::where('name', $name)->update(['value' => $newPath]);
                }
            }
            // Skip update if no file uploaded
            return;
        }
        // CSV for MultiSelect
        elseif ($type === 'MultiSelect') {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
        }
        // JSON for MultiSelectWebLang
        elseif ($type === 'MultiSelectWebLang') {
            if (is_array($value)) {
                $value = json_encode(array_values($value));
            }
        }
        // Other types: use as-is

        // Update non-file fields
        Systemflag::where('name', $name)->update(['value' => $value]);
    }

    /**
     * Returns:
     * - string path when a new file is saved
     * - "" when cleared
     * - null when nothing changed (no upload, not cleared)
     */
    private function handleUploadOrClear(string $name, $value, string $type): ?string
    {
        $relativeDir = 'assets/img/uploads';
        $absoluteDir = public_path($relativeDir);

        if (!File::isDirectory($absoluteDir)) {
            File::makeDirectory($absoluteDir, 0755, true);
        }

        // New upload
        if ($value instanceof UploadedFile) {
            // Delete existing file first
            $this->deleteExistingFile($name);

            $ext = $value->getClientOriginalExtension();
            if (!$ext) {
                $ext = $type === 'Video' ? 'mp4' : 'png';
            }

            $basename = Str::slug($name) . '-' . now()->timestamp . '.' . strtolower($ext);

            // Move the uploaded file
            $value->move($absoluteDir, $basename);

            return $relativeDir . '/' . $basename;
        }

        // Explicit clear (e.g., Video toggle sent empty string)
        if ($value === '' || $value === null) {
            $this->deleteExistingFile($name);
            return '';
        }

        // no new upload & not cleared => nothing to change
        return null;
    }

    private function deleteExistingFile(string $name): void
    {
        $existing = Systemflag::where('name', $name)->value('value');
        if ($existing) {
            $path = public_path(ltrim($existing, '/'));
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }
}
