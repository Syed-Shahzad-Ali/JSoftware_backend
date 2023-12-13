<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

class auditFormsController extends Controller
{
    public function init(Request $request){

        $d_id = $request->input('d_id');
        $formId = $request->input('formId');

        $final = array();

        if($formId == 0){
            $allForms = DB::table('audit_config')->where('type', 'form')->where('d_id', $d_id)->get();
            $forms = DB::table('audit_config')->where('type', 'form')->where('d_id', $d_id)->get();
            $checklists = DB::table('audit_config')->where('type', 'checklist')->get();
            $headings = DB::table('audit_config')->where('type', 'heading')->get();
            $questions = DB::table('audit_config')->where('type', 'q')->get();
        }else{
            $allForms = DB::table('audit_config')->where('type', 'form')->where('d_id', $d_id)->get();
            $forms = DB::table('audit_config')->where('type', 'form')->where('d_id', $d_id)->where('id', $formId)->get();
            $formsCheck = array();
            foreach($forms as $form){
                array_push($formsCheck, $form->id);
            }
            $checklists = DB::table('audit_config')->where('type', 'checklist')->whereIn('d_id', $formsCheck)->get();
            $checklistsCheck = array();
            foreach($checklists as $checklist){
                array_push($checklistsCheck, $checklist->id);
            }
            $headings = DB::table('audit_config')->where('type', 'heading')->whereIn('d_id', $checklistsCheck)->get();
            $headingsCheck = array();
            foreach($headings as $heading){
                array_push($headingsCheck, $heading->id);
            }
            $questions = DB::table('audit_config')->where('type', 'q')->whereIn('d_id', $headingsCheck)->get();
        }

        $final['questions'] = array();

        foreach($headings as $heading){
            
            $final['questions'][$heading->id] = array();

            foreach($questions as $question){
                if($question->d_id == $heading->id){
                    array_push($final['questions'][$heading->id], $question);
                }

            }

        }

        $final['options'] = array();

        foreach($questions as $question){

            $final['options'][$question->id] = DB::table('audit_config')->where('d_id', $question->id)->get();

        }

        $final['allForms'] = $allForms;
        $final['forms'] = $forms;
        $final['checklists'] = $checklists;
        $final['headings'] = $headings;
        $final['formId'] = $formId;

        return $final;

        
    }

    public function addFormType(Request $request){

        $name = $request->input('name');
        $type= 'form';
        $d_id = $request->input('d_id');

        DB::table('audit_config')->insert([
            'name' => $name,
            'type' => $type,
            'd_id' => $d_id
        ]);

        return 'added';
    }

    public function addChecklist(Request $request){

        $name = $request->input('name');
        $type = 'checklist';
        $d_id = $request->input('d_id');

        DB::table('audit_config')->insert([
            'name' => $name,
            'type' => $type,
            'd_id' => $d_id
        ]);

        return 'added';
    }

    public function addChecklistHeading(Request $request){

        $name = $request->input('name');
        $type = 'heading';
        $d_id = $request->input('d_id');

        DB::table('audit_config')->insert([
            'name' => $name,
            'type' => $type,
            'd_id' => $d_id
        ]);

        return 'added';
    }

    public function addQuestion(Request $request){

        $name = $request->input('name');
        $type = 'q';
        $d_id = $request->input('d_id');
        $defaultValue = $request->input('defaultValue');

        Log::info($defaultValue);

        DB::table('audit_config')->insert([
            'name' => $name,
            'type' => $type,
            'd_id' => $d_id,
            'defaultValue' => $defaultValue
        ]);

        return 'added';
    }

    public function addFields(Request $request){

        $fields = $request->input('fields');
        $d_id = $request->input('d_id');

        $question = DB::table('audit_config')->where('id', $d_id)->get();

        $defaultValue = json_decode($question[0]->defaultValue, true);

        foreach($fields as $field){
            $id = DB::table('audit_config')->insertGetId([
                'name' => $field['name'],
                'type' => $field['type'],
                'd_id' => $d_id
            ]);

            if($field['type'] == 'c_ro'){
                $defaultValue['c_ro'][$id] = "";
            }
            if($field['type'] == 'text'){
                $defaultValue['text'][$id] = "";
            }
        }
        
        if(count($defaultValue['c_ro']) == 0){
            $defaultValue['c_ro'] = (object)array();
        }
        
        if(count($defaultValue['text']) == 0){
            $defaultValue['text'] = (object)array();
        }


        $defaultValueStringified = json_encode($defaultValue);

        DB::table('audit_config')
              ->where('id', $d_id)
              ->update(['defaultValue' => $defaultValueStringified]);

        return 'added';
    }

    public function addDefaults(Request $request){
        $defaultValue = $request->input('defaultValue');
        $d_id = $request->input('d_id');
        
        $defaultValue = json_decode($defaultValue, true);

        Log::info($defaultValue);
        Log::info($d_id);

        $severity = DB::table('audit_config')->where('id', $d_id)->select('defaultValue')->get();

        $severity1 = json_decode($severity[0]->defaultValue, true);
        

        Log::info($severity1);

        $defaultValue['sev'] = $severity1['sev'];
        
        if(count($defaultValue['c_ro']) == 0){
            $defaultValue['c_ro'] = (object)array();
        }
        
        if(count($defaultValue['text']) == 0){
            $defaultValue['text'] = (object)array();
        }

        $defaultValue = json_encode($defaultValue);

        Log::info($defaultValue);

        DB::table('audit_config')
              ->where('id', $d_id)
              ->update(['defaultValue' => $defaultValue]);

        return 'added';
    }

    public function delete(Request $request){
        $final = array();

        $type= $request->input('type');
        $id = $request->input('id');

        if($type == 'form'){
            $checklists = DB::table('audit_config')->where('d_id', $id)->pluck('id');
            $headings = DB::table('audit_config')->whereIn('d_id', $checklists)->pluck('id');
            $questions = DB::table('audit_config')->whereIn('d_id', $headings)->pluck('id');
            $options = DB::table('audit_config')->whereIn('d_id', $questions)->pluck('id');
            DB::table('audit_config')->where('id', $id)->delete();

            DB::table('audit_config')->whereIn('id', $checklists)->delete();
            DB::table('audit_config')->whereIn('id', $headings)->delete();
            DB::table('audit_config')->whereIn('id', $questions)->delete();
            DB::table('audit_config')->whereIn('id', $options)->delete();
        }
        if($type == 'checklist'){
            $headings = DB::table('audit_config')->where('d_id', $id)->pluck('id');
            $questions = DB::table('audit_config')->whereIn('d_id', $headings)->pluck('id');
            $options = DB::table('audit_config')->whereIn('d_id', $questions)->pluck('id');
            DB::table('audit_config')->where('id', $id)->delete();

            DB::table('audit_config')->whereIn('id', $headings)->delete();
            DB::table('audit_config')->whereIn('id', $questions)->delete();
            DB::table('audit_config')->whereIn('id', $options)->delete();
        }
        if($type == 'heading'){

            $questions = DB::table('audit_config')->where('d_id', $id)->pluck('id');
            $options = DB::table('audit_config')->whereIn('d_id', $questions)->pluck('id');
            DB::table('audit_config')->where('id', $id)->delete();

            DB::table('audit_config')->whereIn('id', $questions)->delete();
            DB::table('audit_config')->whereIn('id', $options)->delete();
        }
        if($type == 'q'){

            DB::table('audit_config')->where('id', $id)->orWhere('d_id', $id)->delete();
        }

        return $final;
    }
}
