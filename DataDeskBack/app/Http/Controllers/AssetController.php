<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Asset::with(['company', 'branch', 'responsibleUser']);

        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    // Public search by Serial Number
    public function search($serialNumber)
    {
        $asset = Asset::with(['company', 'branch', 'responsibleUser'])
            ->where('serial_number', $serialNumber)
            ->orWhere('asset_code', $serialNumber)
            ->first();

        if (!$asset) {
            return response()->json(['message' => 'ไม่พบข้อมูลทรัพย์สิน'], 404);
        }

        return response()->json($asset);
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_code' => 'required|string|unique:assets,asset_code',
            'serial_number' => 'required|string',
            'type' => 'required|string',
            'brand' => 'required|string',
            'model' => 'required|string',
            'start_date' => 'required|date',
            'location' => 'required|string',
            'company_id' => 'required|string',
            'branch_id' => 'required|string',
            'responsible' => 'required',
        ], [
            'asset_code.unique' => 'เลขครุภัณฑ์นี้มีอยู่ในระบบแล้ว',
        ]);

        $data = $request->all();
        if (empty($data['id'])) {
            $lastAsset = Asset::orderBy('id', 'desc')->first();
            $nextId = 'A001';
            
            if ($lastAsset && preg_match('/^A(\d+)$/', $lastAsset->id, $matches)) {
                $num = intval($matches[1]) + 1;
                $nextId = 'A' . str_pad($num, 3, '0', STR_PAD_LEFT);
            }
            $data['id'] = $nextId;
        }

        $asset = Asset::create($data);
        return response()->json($asset, 201);
    }

    public function show(string $id)
    {
        $asset = Asset::with(['company', 'branch', 'responsibleUser', 'tickets'])->findOrFail($id);
        return response()->json($asset);
    }

    public function update(Request $request, string $id)
    {
        $asset = Asset::findOrFail($id);
        $request->validate([
            'asset_code' => 'sometimes|string|unique:assets,asset_code,' . $id,
        ], [
            'asset_code.unique' => 'เลขครุภัณฑ์นี้มีอยู่ในระบบแล้ว',
        ]);
        $asset->update($request->all());
        return response()->json($asset);
    }

    public function destroy(string $id)
    {
        Asset::findOrFail($id)->delete();
        return response()->json(['message' => 'ลบทรัพย์สินสำเร็จ']);
    }

    public function uploadImages(Request $request, string $id)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'string', // base64 strings
        ]);

        $asset = Asset::findOrFail($id);
        $currentImages = $asset->images ?? [];
        $asset->images = array_merge($currentImages, $request->images);
        $asset->save();

        return response()->json($asset);
    }

    public function deleteImage(Request $request, string $id, int $imageIndex)
    {
        $asset = Asset::findOrFail($id);
        $images = $asset->images ?? [];

        if (isset($images[$imageIndex])) {
            array_splice($images, $imageIndex, 1);
            $asset->images = $images;
            $asset->save();
        }

        return response()->json($asset);
    }
}
