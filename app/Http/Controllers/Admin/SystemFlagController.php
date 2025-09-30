<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
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
use Illuminate\Support\Facades\Log;

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
            $currencies = Currency::get();

            return view('admin.systemflags.system-flag', compact('flagGroup', 'language', 'currencies'));
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
                \Log::info("Processing group {$groupIndex}: ", $group);

                // Top-level system flags
                if (!empty($group['systemFlag'])) {
                    foreach ($group['systemFlag'] as $flagIndex => $flag) {
                        \Log::info("Processing flag {$groupIndex}-{$flagIndex}: ", $flag);

                        // Check if this is a file field and if file exists in request
                        if (isset($flag['valueType']) && ($flag['valueType'] === 'File' || $flag['valueType'] === 'Video')) {
                            $fieldName = "group.{$groupIndex}.systemFlag.{$flagIndex}.value";
                            $hasFile = $req->hasFile($fieldName);
                            \Log::info("File field {$fieldName} - Has file: " . ($hasFile ? 'YES' : 'NO'));

                            if ($hasFile) {
                                $file = $req->file($fieldName);
                                \Log::info("File details: ", [
                                    'original_name' => $file->getClientOriginalName(),
                                    'mime_type' => $file->getMimeType(),
                                    'size' => $file->getSize(),
                                    'is_valid' => $file->isValid(),
                                    'error' => $file->getError()
                                ]);
                                // Replace the array value with actual file object
                                $flag['value'] = $file;
                            } elseif (!isset($flag['value']) || empty($flag['value'])) {
                                // Skip this flag if no file uploaded and no existing value
                                continue;
                            }
                        }

                        $this->processAndUpdateFlag($flag);
                    }
                }

                // Sub-groups
                if (!empty($group['subGroup'])) {
                    foreach ($group['subGroup'] as $subIndex => $subGroup) {
                        if (!empty($subGroup['systemFlag'])) {
                            foreach ($subGroup['systemFlag'] as $subFlagIndex => $flag) {
                                \Log::info("Processing sub-flag {$groupIndex}-{$subIndex}-{$subFlagIndex}: ", $flag);

                                // Check if this is a file field
                                if (isset($flag['valueType']) && ($flag['valueType'] === 'File' || $flag['valueType'] === 'Video')) {
                                    $fieldName = "group.{$groupIndex}.subGroup.{$subIndex}.systemFlag.{$subFlagIndex}.value";
                                    $hasFile = $req->hasFile($fieldName);
                                    \Log::info("Sub-file field {$fieldName} - Has file: " . ($hasFile ? 'YES' : 'NO'));

                                    if ($hasFile) {
                                        $file = $req->file($fieldName);
                                        $flag['value'] = $file;
                                    } elseif (!isset($flag['value']) || empty($flag['value'])) {
                                        // Skip this flag if no file uploaded and no existing value
                                        continue;
                                    }
                                }

                                $this->processAndUpdateFlag($flag);
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
            \Log::error('Update failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Update failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function processAndUpdateFlag(array $flag): void
    {
        \Log::info('=== PROCESSING FLAG ===');
        \Log::info('Flag data: ', $flag);

        if (!array_key_exists('name', $flag)) {
            \Log::info('No name key found, skipping');
            return;
        }

        $name  = $flag['name'];
        $type  = $flag['valueType'] ?? null;
        $value = $flag['value'] ?? null;

        \Log::info("Processing flag: {$name}, type: {$type}, value type: " . gettype($value));

        // Handle upload/clear for File & Video
        if ($type === 'File' || $type === 'Video') {
            \Log::info('Processing file/video field');
            $newPath = $this->handleUploadOrClear($name, $value, $type);
            \Log::info("File processing result: " . ($newPath ?? 'NULL'));

            if ($newPath === null) {
                \Log::info('No file change, keeping existing value');
                return;
            }
            $value = $newPath;
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

        \Log::info("Updating {$name} with value: {$value}");
        Systemflag::where('name', $name)->update(['value' => $value]);
    }

    private function handleUploadOrClear(string $name, $value, string $type): ?string
    {
        \Log::info('=== HANDLE UPLOAD OR CLEAR ===');
        \Log::info("Name: {$name}, Type: {$type}, Value type: " . gettype($value));

        if ($value instanceof \Illuminate\Http\UploadedFile) {
            \Log::info('Value is UploadedFile instance');
            \Log::info('File details: ', [
                'name' => $value->getClientOriginalName(),
                'size' => $value->getSize(),
                'mime' => $value->getMimeType(),
                'valid' => $value->isValid(),
                'error' => $value->getError()
            ]);
        } else {
            \Log::info('Value is not UploadedFile: ' . print_r($value, true));
        }

        $relativeDir = 'assets/img/uploads';
        $absoluteDir = public_path($relativeDir);

        if (!File::isDirectory($absoluteDir)) {
            File::makeDirectory($absoluteDir, 0755, true);
            \Log::info("Created directory: {$absoluteDir}");
        }

        // Explicit clear
        if ($value === '' || $value === null) {
            \Log::info('Clearing file');
            $this->deleteExistingFile($name);
            return '';
        }

        // New upload
        if ($value instanceof \Illuminate\Http\UploadedFile && $value->isValid()) {
            \Log::info('Processing file upload');

            $ext = $value->getClientOriginalExtension();
            if (!$ext) {
                $ext = $type === 'Video' ? 'mp4' : 'png';
            }

            $basename = Str::slug($name) . '-' . now()->timestamp . '.' . strtolower($ext);
            \Log::info("Generated filename: {$basename}");

            try {
                $value->move($absoluteDir, $basename);
                \Log::info("File moved to: {$absoluteDir}/{$basename}");

                // Verify file was actually moved
                $newFilePath = $absoluteDir . '/' . $basename;
                if (File::exists($newFilePath)) {
                    \Log::info('File upload successful, deleting old file');
                    $this->deleteExistingFile($name);
                    return $relativeDir . '/' . $basename;
                } else {
                    \Log::error('File was not found after move operation');
                    return null;
                }
            } catch (\Exception $e) {
                \Log::error('File upload failed: ' . $e->getMessage());
                return null;
            }
        }

        \Log::info('No valid file upload found, returning null');
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
