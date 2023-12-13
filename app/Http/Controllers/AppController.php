<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

class AppController extends Controller
{
    public function taskImagesApp(Request $request){
        
        Log::info('from ibrar images');
        
        Log::info($request->file('image')->getClientOriginalName());
        Log::info($request->input('task_id'));
        
        $task_id = $request->input('task_id');
        $file = $request->file('image');
        
        $images = DB::table('tasks')->where('id', $task_id)->pluck('images')[0];
        
        if($images != null){
            $images = json_decode($images, true);
        }else{
            $images = array();
        }
        
        array_push($images,  strtotime("now").$file->getClientOriginalName());
        
        $imagesToEnter = json_encode($images);
        
        DB::table('tasks')->where('id', $task_id)->update(['images' => $imagesToEnter]);
        
        $destinationPath = public_path('taskImages');
        $file->move($destinationPath.'/' . $task_id . '/', strtotime("now").$file->getClientOriginalName());
        
        return 'ok';
    }
    
    public function taskCompleteApp(Request $request){
        
        Log::info($request->all());
        
        $task_id = $request->input('data_id');
        
        $status = 'Complete';
        
        if($request->input('data_snags_0_Remarks')){
            $remarks = $request->input('data_snags_0_Remarks');
        }else{
            $remarks = $request->input('data_Remarks');
        } 
        
        
        $snag_id = $request->input('data_snags_0_id');
        
        DB::table('audit_result')
              ->where('id', $snag_id)
              ->update([  
                    'task_assignment_status' => $status,
                    'Remarks' => $remarks
                ]);

        
        DB::table('tasks')->where('id', $task_id)->update([
            
            'status' => $status,
            'Remarks' => $remarks,
        
        ]);
        
        return 'success';
        
    }
    
    public function taskAcceptanceStatusApp(Request $request){
        
        Log::info('Acceptance Status');
        
        Log::info($request->all());
        
        return 'hello world';
        
    }
    
    public function appLogin(Request $request){
        $email = $request->input('email');
        $password = $request->input('password');
        if ($email && $password){
            $checker = DB::table('members')->where('email',$email)->pluck('id');
            if(count($checker) > 0){
               $db_password = DB::table('members')->where('id',$checker[0])->pluck('password')[0];
               
               if (Hash::check($password, $db_password)){
                   $member =  DB::table('members')->where('id', $checker[0])->get()[0];
                   return $member;
               }else{
                return abort(500, 'incorrect password'); 
               }
            }else{
                return abort(500, 'email or password incorrect'); 
            }
        }else{
            return abort(500, 'no email and password');
        }
    }
    
    public function getTasksApp(Request $request){
        
        $final = array();
        
        $id = $request->input('id');
        
        $taskIds = DB::table('task_assignment')->where('member_id', $id)->whereNull('status')->pluck('task_id');
        
        $tasksCustom = DB::table('tasks')->whereIn('id', $taskIds)->where('status' , '!=', 'Complete')->where('type', 'Custom')->pluck('id');
        
        $tasksSnag = DB::table('tasks')->whereIn('id', $taskIds)->where('status' , '!=', 'Complete')->where('type', 'Snag')->pluck('id');
        
        $final['tasksCustom'] = $tasksCustom;
        
        $final['tasksSnag'] = $tasksSnag;
        
        return $final;
        
    }
    
    public function getSingleTaskApp(Request $request){
        
        $task_id = $request->input('task_id');
        $user_id = $request->input('user_id');
        
        
        DB::table('task_assignment')->where('task_id', $task_id)->where('member_id', $user_id)->update(['status' => 'read']);
        
        $task = DB::table('tasks')->where('id', $task_id)->get();
        
        $snagsIds = [$task[0]->snag_id];
        
        $response = Http::post('https://joynaudits.com/api/getTaskSnags', [
            'snagsIds' => $snagsIds,
        ]);
        
        if($response->failed() || $response->clientError() || $response->serverError()){
            return abort(500, 'Ambigious response');
        }
        
        $task[0]->snags = $response->json();
        
        $task[0]->approval_status = DB::table('task_assignment')->where('task_id', $task_id)->where('member_id', $user_id)->pluck('approval_status')[0];
        
        $task[0]->approval_remarks = DB::table('task_assignment')->where('task_id', $task_id)->where('member_id', $user_id)->pluck('remarks')[0];
        
        $task[0]->images = array();
        
        Log::info($task);
        
        return $task;
    }
    
    public function getSnagsApp(Request $request){
        
        $task_id = $request->input('id');
        
        $snagsIds = DB::table('task_snags')->where('task_id', $task_id)->pluck('snag_id');
        
        $response = Http::post('https://joynaudits.com/api/getTaskSnags', [
            'snagsIds' => $snagsIds,
        ]);
        
        if($response->failed() || $response->clientError() || $response->serverError()){
            return abort(500, 'Ambigious response');
        }
        
        return $response->json();
    }
}


