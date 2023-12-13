<?php

namespace App\Http\Controllers; 

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Mail;


use Illuminate\Http\Request;

class AuditorsController extends Controller
{
    public function addAuditor(Request $request){
       $auditor = $request->all();
       Log::info($auditor);
       $password = strtolower($auditor['name']);
       $password = str_replace(' ', '', $password);
       $password = $password . '_1234@';
       $passwordHash = Hash::make($password);
       $auditor['password'] = $passwordHash;
       $auditor['cust_id'] = 2;

       DB::table('auditors')->insert($auditor);

       return $auditor;
    }

}
