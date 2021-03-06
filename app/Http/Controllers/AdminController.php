<?php

namespace App\Http\Controllers;

use App\Models\Osis;
use App\Models\User;
use App\Models\Pemilu;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.admin');
    }

    public function showAllPemilu()
    {
        $pemilu = Pemilu::all();
        return view('admin.pemilu',compact('pemilu'));
    }

    public function storePemilu()
    {
        $validator = Validator::make(request()->all(),[
            'name' => 'required|string|unique:pemilu,name',
            'start_date' => 'required|date_format:Y-m-d H:i|after_or_equal:today',
            'end_date' => 'required|date_format:Y-m-d H:i|after:start_date',
        ]);
        if($validator->fails())
        {
            return redirect('admin/pemilu')->withInput()->withErrors($validator);
        }
        Pemilu::create([
            'name' => request('name'),
            'start_date' => request('start_date'),
            'end_date' => request('end_date'),
        ]);
        return redirect('admin/pemilu')->with('status','Data pemilu berhasil ditambahkan');
    }

    public function updatePemilu($pemiluID)
    {
        $pemilu = Pemilu::find($pemiluID);
        if(!$pemilu)
        {
            return redirect('admin/pemilu')->with('status','Data pemilu tidak ditemukan');
        }
        $validator = Validator::make(request()->all(),[
            'name' => 'required|string|unique:pemilu,name,'.$pemilu->id,
            'start_date' => 'required|date_format:Y-m-d H:i|after_or_equal:'.$pemilu->start_date,
            'end_date' => 'required|date_format:Y-m-d H:i|after:start_date',
        ]);
        if($validator->fails())
        {
            return redirect('admin/pemilu')->withInput()->withErrors($validator);
        }
        $pemilu->update([
            'name' => request('name'),
            'start_date' => request('start_date'),
            'end_date' => request('end_date'),
        ]);
        return redirect('admin/pemilu')->with('status','Data pemilu berhasil diupdate');
    }

    public function updateStatusPemilu($pemiluID)
    {
        $pemilu = Pemilu::find($pemiluID);
        if(!$pemilu)
        {
            return redirect('admin/pemilu')->with('status','Data pemilu tidak ditemukan');
        }
        $pemilu->status == 'ACTIVE' ? $pemilu->update(['status' => 'INACTIVE']) : $pemilu->update(['status' => 'ACTIVE']);
        return redirect('admin/pemilu')->with('status','Status pemilu berhasil diupdate');
    }

    public function deletePemilu($pemiluID)
    {
        $pemilu = Pemilu::find($pemiluID);
        if(!$pemilu)
        {
            return redirect('admin/pemilu')->with('status','Data pemilu tidak ditemukan');
        }
        Pemilu::destroy($pemiluID);
        return redirect('admin/pemilu')->with('status','Data pemilu berhasil dihapus');
    }

    public function showAllKetua()
    {
        $pemilu = Pemilu::where('status','ACTIVE')->get();
        $osis = Osis::all();
        return view('admin.calon',compact('osis','pemilu'));
    }

    public function storeKetua()
    {
        $validator = Validator::make(request()->all(),[
            'name' => 'required|string',
            'kelas' => 'required|string',
            'pemilu_id' => 'required',
            'visi' => 'required|string',
            'misi' => 'required|string',
            'photo' => 'required|max:10240|mimes:png,jpg,jpeg',
        ]);
        if($validator->fails())
        {
            return redirect('admin/calon')->withInput()->withErrors($validator);
        }
        $pemilu = Pemilu::find(request('pemilu_id'));
        if(request()->hasFile('photo'))
        {
            $photo = time()."_".request()->photo->getClientOriginalName();
            request()->photo->move(public_path('data_calon/'.$pemilu->name.'/'.request('name').'/photo'), $photo); 
        }
        Osis::create([
            'name' => request('name'),
            'kelas' => request('kelas'),
            'pemilu_id' => request('pemilu_id'),
            'visi' => request('visi'),
            'misi' => request('misi'),
            'photo' => $photo,
        ]);
        return redirect('admin/calon')->with('status','Data calon ketua berhasil ditambahkan');
    }

    public function updateKetua($osisID)
    {
        $osis = Osis::find($osisID);
        $validator = Validator::make(request()->all(),[
            'name' => 'required|string',
            'kelas' => 'required|string',
            'pemilu_id' => 'required',
            'visi' => 'required|string',
            'misi' => 'required|string',
            'photo' => 'required|max:10240|mimes:png,jpg,jpeg',
        ]);
        if($validator->fails())
        {
            return redirect('admin/calon')->withInput()->withErrors($validator);
        }
        $pemilu = Pemilu::find(request('pemilu_id'));
        if(request()->hasFile('photo'))
        {
            $photo = time()."_".request()->photo->getClientOriginalName();
            request()->photo->move(public_path('data_calon/'.$pemilu->name.'/'.request('name').'/photo'), $photo); 
            File::delete('data_calon/'.$osis->pemilu->name.'/'.$osis->name.'/photo/'.$osis->photo);
        }
        $osis->update([
            'name' => request('name'),
            'kelas' => request('kelas'),
            'pemilu_id' => request('pemilu_id'),
            'visi' => request('visi'),
            'misi' => request('misi'),
            'photo' => $photo,
        ]);
        return redirect('admin/calon')->with('status','Data calon ketua berhasil diupdate');
    }

    public function deleteKetua($osisID)
    {
        $osis= Osis::find($osisID);
        if(!$osis)
        {
            return redirect('admin/calon')->with('status','Data ketua tidak ditemukan');
        }
        File::delete('data_calon/'.$osis->pemilu->name.'/'.$osis->name.'/photo/'.$osis->photo);
        Osis::destroy($osisID);
        return redirect('admin/calon')->with('status','Data calon ketua berhasil dihapus');
    }

    public function showPemilih($pemiluID)
    {
        $pemilih = User::where('pemilu_id',$pemiluID)->whereHas('roles', function ($query) {
            $query->where('name', '=', 'pemilih');
        })->get();
        return view('admin.pemilih',compact('pemilih','pemiluID'));
    }

    public function storePemilih($pemiluID)
    {
        $validator = Validator::make(request()->all(),[
            'jumlah' => 'required',
        ]);
        if($validator->fails())
        {
            return redirect('admin/'.$pemiluID.'/pemilih')->withInput()->withErrors($validator);
        }
        for($i = 0 ; $i < request('jumlah') ; $i++)
        {
            $user = User::create([
                'pemilu_id' => $pemiluID,
                'password' => Str::random(8),
            ]);
            $user->assignRole('pemilih');
        }
        return redirect('admin/'.$pemiluID.'/pemilih')->with('status','Data pemilih berhasil dibuat');
    }

    public function deletePemilih($pemiluID,$pemilihID)
    {
        $pemilu = Pemilu::find($pemiluID);
        if(!$pemilu)
        {
            return redirect('admin/'.$pemiluID.'/pemilih')->with('status','Data pemilu tidak ditemukan');
        }
        $user = User::find($pemilihID);
        if(!$user)
        {
            return redirect('admin/'.$pemiluID.'/pemilih')->with('status','Data pemilih tidak ditemukan');
        }
        User::destroy($pemilihID);
        return redirect('admin/'.$pemiluID.'/pemilih')->with('status','Data pemilih berhasil dihapus');
    }

    public function hasilPemilu($pemiluID)
    {
        $pemilu = Pemilu::find($pemiluID);
        if(!$pemilu)
        {
            return redirect('admin/pemilu')->with('status','Data pemilu tidak ditemukan');
        }
        $users = Osis::where('pemilu_id',$pemiluID)->get();
        $jumlah = array();
        foreach($users as $u)
        {
            $jumlah[] = $u->pemilih_osis()->count();
        }
        return view('admin.hasil',compact('users','jumlah'));
    }
}
