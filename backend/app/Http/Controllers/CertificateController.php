<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificateRequest;
use App\Http\Requests\UpdateCertificateRequest;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::query();

        // Fitur Filter: Jika frontend mengirim ?featured=1, ambil yang featured saja
        if ($request->has('featured') && $request->featured == '1') {
            $query->where('is_featured', true);
        }

        // Urutkan tetap dari yang terbaru
        $certificates = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $certificates,
        ]);
    }

    public function store(StoreCertificateRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if (config('filesystems.default') === 'cloudinary') {
                $data['image_path'] = $request->file('image')->storeOnCloudinary('certificates')->getSecurePath();
            } else {
                $data['image_path'] = $request->file('image')->store('certificates', 'public');
            }
        }

        $certificate = Certificate::create($data);

        return response()->json([
            'message' => 'Certificate created',
            'data' => $certificate,
        ], 201);
    }

    public function update(UpdateCertificateRequest $request, $id)
    {
        $certificate = Certificate::findOrFail($id);
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Hapus file lama jika ada (hanya jika tersimpan di disk lokal)
            if ($certificate->image_path && !str_starts_with($certificate->image_path, 'http')) {
                Storage::disk('public')->delete($certificate->image_path);
            }
            if (config('filesystems.default') === 'cloudinary') {
                $data['image_path'] = $request->file('image')->storeOnCloudinary('certificates')->getSecurePath();
            } else {
                $data['image_path'] = $request->file('image')->store('certificates', 'public');
            }
        }

        $certificate->update($data);

        return response()->json([
            'message' => 'Certificate updated',
            'data' => $certificate->fresh(), // Mengambil data terbaru termasuk URL gambar baru
        ]);
    }

    public function show($id)
    {
        $certificate = Certificate::findOrFail($id);
        return response()->json($certificate);
    }

    public function destroy($id)
    {
        $certificate = Certificate::findOrFail($id);

        if ($certificate->image_path && !str_starts_with($certificate->image_path, 'http')) {
            Storage::disk('public')->delete($certificate->image_path);
        }

        $certificate->delete();
        return response()->json(['message' => 'Certificate deleted']);
    }
}
