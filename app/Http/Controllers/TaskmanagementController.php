<?php

namespace App\Http\Controllers; 

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Mail;


use Illuminate\Http\Request;

class TaskmanagementController extends Controller
{
    public function getMembers(Request $request){
        
        $final = array();
        $members = DB::table('members')->get();
        $functions = DB::table('functions')->get();
        foreach($members as $member){
            
            $function = $member->function;
            $funcName = DB::table('functions')->where('id', $function)->pluck('name')[0];
            $member->function = $funcName;
        }
        $final['members'] = $members;
        $final['functions'] = $functions;
        return $final;
        
    }
    
    public function addMember(Request $request){
        
        if($request->input('crudType') == 'add'){
        
            $member = $request->input('member');
            
            $password = $member['name'] . '_1234@';
    
            $passwordHash = Hash::make($password);
            
            $users = [$member['email']];
            $data = ['email' => $member['email'], 'password' => $password];
            
            try{
                Mail::send('memberCreateMail', $data, function($messages) use ($users){
                    foreach($users as $user){
                        $messages->to($user);
                        $messages->subject('New Member');
                    }
                });
            }
            catch(\Exception $e){
                Log::info($e);
                return 'failed';
            }
            
            $id = DB::table('members')->insertGetId($member);
                
            DB::table('members')
              ->where('id', $id)
              ->update(['password' => $passwordHash]);
        
            return 'done';
        }
        if($request->input('crudType') == 'update'){
            
            DB::table('members')->where('id',$request->input('member_id'))->update($request->input('member'));
            
        }
    }
    
    public function addFunction(Request $request){
        
        DB::table('functions')->insert([
                'name' => $request->input('function')
            ]);
        return 'done';
        
    }
    
    public function getTasks(Request $request){
        $final = array();
        $members = DB::table('members')->get();
        $functions = DB::table('functions')->get();
        $tasks = DB::table('tasks')->get();
        foreach($members as $member){
            $function = $member->function;
            $funcName = DB::table('functions')->where('id', $function)->pluck('name')[0];
            $member->function = $funcName;
        }
        foreach($tasks as $task){
            $assigned = DB::table('task_assignment')->where('task_id', $task->id)->pluck('member_id');
            $assignedMembers = DB::table('members')->whereIn('id', $assigned)->get();
            $task->members = $assignedMembers;
            if($task->location != null){
                $task->location = json_decode($task->location);
            }
        }
        $final['members'] = $members;
        $final['functions'] = $functions;
        $final['tasks'] = $tasks;
        
        return $final;
        
    }
    
    public function addTasks(Request $request){
        
        $task = $request->input('task');
        $user_id = $request->input('user_id');
        Log::info($user_id);
        $user_name = DB::table("users")->where('id',$user_id)->pluck('name')[0];
        $task['assigned_by_id'] = $user_id;
        $task['assigned_by_name'] = $user_name;
        $startDate = strtotime($task['startDate']);
        $dueDate = strtotime($task['dueDate']);
        $now = strtotime("now");
        if($now < $startDate){
          $task['status'] = 'assinged';
        }else if($now > $startDate && $now < $dueDate){
          $task['status'] = 'ongoing';
        }else{
          $task['status'] = 'pending';
        }
        DB::table('tasks')->insert($task);
        return 'done';
    }
    
    public function assignMembers(Request $request){
        
        $task = $request->input('task');
        $members = $request->input('members');
    
        
        $assigned = DB::table('task_assignment')->where('task_id', $task['id'])->select('member_id')->get();
        
        $assignedMembers = array();
        $assignedMails = array();
        
        foreach($assigned as $assi){
            array_push($assignedMembers, $assi->member_id);
            array_push($assignedMails, DB::table('members')->where('id', $assi->member_id)->pluck('email')[0]);
        }
        
        $memberEmails = array();
        
        DB::table('task_assignment')->where('task_id', $task['id'])->delete();

        foreach($members as $member){
            if (!in_array($member['id'], $assignedMembers)){
                DB::table('task_assignment')->insert([
                    'task_id' => $task['id'],
                    'member_id' => $member['id']
                ]);
                array_push($memberEmails, $member['email']);
            }
        }
        
        $data = ['task' => $task['name'], 'startDate' => $task['startDate'], 'dueDate' => $task['dueDate'], 'city' => $task['city'], 'region' => $task['region'], 'description' => $task['description']];

        $mailStatus = 'ok';

        try{
            Mail::send('taskAssignedMail', $data, function($messages) use ($memberEmails){
                foreach($memberEmails as $user){
                    $messages->to($user);
                    $messages->subject('Task Assigned');
                }
            });
        }
        catch(\Exception $e){
            $mailStatus = 'nok';
            Log::info('mail error');
            Log::info($e);
        }

        try{
            Mail::raw('Task named ' . '(' .$task['name'] . ')' . ' is no longer assigned to you', function ($message) use ($assignedMails) {
                $message->to($assignedMails);
                $message->subject('Task Unassinged');
              });
        }
        catch(\Exception $e){
            $mailStatus = 'nok';
            Log::info('mail error');
            Log::info($e);
        }
        
        return $mailStatus;
    }
    
    public function createTaskSnag(Request $request){
        
        $final = array();
        
        $snags = $request->input('snags');
        $task = $request->input('task');
        $members = $request->input('members');
        $user_id = $request->input('user_id');
        $status = 'pending';

        $startDate = strtotime($task['startDate']);
        $dueDate = strtotime($task['dueDate']);
        $now = strtotime("now");

        if($now < $startDate){
            $status = 'assinged';
          }else if($now > $startDate && $now < $dueDate){
            $status = 'ongoing';
          }else{
            $status = 'pending';
          }
        
        $user_name = DB::table("users")->where('id',$user_id)->pluck('name')[0];
        
        foreach($snags as $snag){

            DB::table('audit_result')
              ->where('id', $snag['id'])
              ->update([
                    'task_assignment_status' => $status,
                    'assigned_by_id' => $user_id,
                    'assigned_by_name'=> $user_name,

                ]);
        }

        $taskToInsert = array();
        $tasks = array();
        
        foreach($snags as $snag){
                $taskToInsert['name'] = $snag['q_name'];
                $taskToInsert['urgency'] = $task['urgency'];
                $taskToInsert['city'] = $snag['City'];
                $taskToInsert['region'] = $snag['Region'];
                $taskToInsert['area'] = $snag['Area'];
                $taskToInsert['description'] = $task['description'];
                $taskToInsert['status'] = $status;
                $taskToInsert['startDate'] = $task['startDate'];
                $taskToInsert['dueDate'] = $task['dueDate'];
                $taskToInsert['Type'] = $task['Type'];
                $taskToInsert['snag_id'] = $snag['id'];
                $taskToInsert['location'] = $snag['position'];
                $taskToInsert['assigned_by_id'] = $user_id;
                $taskToInsert['assigned_by_name'] = $user_name;
                $taskToInsert['Remarks'] = $snag['Remarks'];
                
                
                array_push($tasks, $taskToInsert);
                
                $task_id = DB::table('tasks')->insertGetId($taskToInsert);
                
                foreach($members as $member){
                    DB::table('task_assignment')->insert([
                        'task_id' => $task_id,
                        'member_id' => $member['id']
                    ]);
                }
        
        }
                
        
        $memberEmails = array();
        
        foreach($members as $member){
                array_push($memberEmails, $member['email']);
        }
        
        Log::info('emails');
        Log::info($memberEmails);

        $mailStatus = 'ok';
              
        $data = ['tasks' => $tasks];

        try{
            Mail::send('taskAssignedMail', $data, function($messages) use ($memberEmails){
                foreach($memberEmails as $user){
                    $messages->to($user);
                    $messages->subject('Task Assigned');
                }
            });
        }
        catch(\Exception $e){
            $mailStatus = 'nok';
            Log::info('mail error');
            Log::info($e);
        }
        
        $tasksPositions = DB::table('tasks')->whereNotNull('location')->select('location')->get();
        
        $locations = array();
        
        foreach($tasksPositions as $position){
            if(!in_array($position->location, $locations)){
                array_push($locations, $position->location);
            }
        }
        
        $final['snags'] = $snags;
        $final['task'] = $task;
        $final['members'] = $members;
        $final['taskPostions'] = $locations;
        $final['mailStatus'] = $mailStatus;
        
        return $final;
    }
    
    public function getSnags(Request $request){
        $task_id = $request->input('task_id');
        
        $snagsIds = DB::table('task_snags')->where('task_id', $task_id)->pluck('snag_id');

        $snags = DB::table('audit_result')->whereIn('id', $snagsIds)->get();

        return $snags;
    }
    
    public function taskDelete(Request $request){
        
        $task_id = $request->input('task_id');
        
        $snagsIds = DB::table('tasks')->where('id', $task_id)->pluck('snag_id');
        
        foreach($snagsIds as $snagId){

            DB::table('audit_result')
              ->where('id', $snagId)
              ->update([
                'task_assignment_status' => NULL,
                'assigned_by_id' => NULL,
                'assigned_by_name'=> NULL
                ]);

              
        }
        
        
        DB::table('tasks')->where('id', $task_id)->delete();
        DB::table('task_assignment')->where('task_id', $task_id)->delete();
        
       
        
        return 'done';
        
    }

    public function memberDelete(Request $request){
        
        $member_id = $request->input('id');
        
        DB::table('members')->where('id', $member_id)->delete();
        
        return 'done';
    }
}
