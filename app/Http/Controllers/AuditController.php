<?php

namespace App\Http\Controllers\Customer\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
// use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use DateTime;
use DatePeriod;
use DateInterval;
use Auth;
use Carbon;
class AuditController extends Controller
{
    public function auditlogin(Request $request){
        $email = $request->input('email');
        $password = $request->input('pass');
        $temp = DB::table('auditors')->where('email',$email)->select('id','password')->get()->toArray();
        if($temp == null)return "0";
        $temp = array_map(function ($value) {
            return (array)$value;
        }, $temp);
        
        if($temp[0]['id'] && Hash::check($password, $temp[0]['password'])){
            $data2 = array();
            $data2['workerid'] = $temp[0]['id'];
            $customer_id=2;
            $data2['id']=$customer_id;
            return json_encode($data2);
        }else{
            return "0";
        }
    }
    public function getauditsites(Request $request){
            $id = $request->input('id');
            $sitesfield=DB::table('customer_fields')->where('destination_id',$id)->where('field_type','Input')->pluck('field_name')[0];
                $data = DB::table($id.'_datatable')->select($sitesfield)->get()->toArray();;
                return json_encode($data);
           return json_encode($data);
    }
    public function getauditfields(Request $request){
        $sequence=$request->input('id');
        $forms=[];
        //get forms type
        $formtype=DB::table('audit_type')->pluck('id');
        for($formtypeid=0;$formtypeid<count($formtype);$formtypeid++){
            $data=[];
            //get form according to form type
            $formnames=DB::table('audit_config')->where('d_id',$sequence)->where('type','form')->where('form_type',$formtype[$formtypeid])->get()->toArray();
            $formnames = array_map(function ($value) {
                    return (array)$value;
                },$formnames);
            for($z=0;$z<count($formnames);$z++){
                $data[$z]['formname']=$formnames[$z]['name'];
                //checklists
                $checklist=DB::table('audit_config')->where('d_id',$formnames[$z]['id'])->get()->toArray();
                $checklist = array_map(function ($value) {
                    return (array)$value;
                },$checklist);
                 $data[$z]['checklist']=[];
              for($i=0;$i<count($checklist);$i++){
                $data[$z]['checklist'][$i]=$checklist[$i];
                //headings
                $headings=DB::table('audit_config')->where('d_id',$checklist[$i]['id'])->get()->toArray();
                $headings = array_map(function ($value) {
                    return (array)$value;
                },$headings);
                $data[$z]['checklist'][$i]['headings']=[];
                for($j=0;$j<count($headings);$j++){
                    $data[$z]['checklist'][$i]['headings'][$j]= $headings[$j];
                    $para=DB::table('audit_config')->where('d_id',$headings[$j]['id'])->get()->toArray();
                    $para = array_map(function ($value) {
                        return (array)$value;
                    },$para);
                    $data[$z]['checklist'][$i]['headings'][$j]['paras']=[];
                    for($k=0;$k<count($para);$k++){
                        $data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]=$para[$k];
                        $option=DB::table('audit_config')->where('d_id',$para[$k]['id'])->get()->toArray();
                        $option = array_map(function ($value) {
                            return (array)$value;
                        },$option);
                        $data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]['text']= [];
                        $data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]['option']= [];
                        $data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]['dropdown']= [];
                        $data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]['multioptions']= [];
                        $data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]['dropdowncondition']=false;
                        
                        for($l=0;$l<count($option);$l++){
                            if($option[$l]['type']=='text'){
                                array_push($data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]['text'],$option[$l]);
                            }elseif($option[$l]['type']=='c_co'||$option[$l]['type']=='c_cr'){
                               array_push($data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]['option'],$option[$l]); 
                            }elseif($option[$l]['type']=='d_do'){
                                 array_push($data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]['dropdown'],$option[$l]); 
                            }elseif($option[$l]['type']=='m_so'){
                                 array_push($data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]['multioptions'],$option[$l]); 
                            }
                        }
                        if(count($data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]['dropdown'])>0){
                            $data[$z]['checklist'][$i]['headings'][$j]['paras'][$k]['dropdowncondition']=true;
                        }
                    }
                }
              }
            }
            $forms[$formtypeid]=$data;
        }
         return (json_encode($forms));
    }
    public function uploadauditform(Request $request){
        //check commit
        Log::info("haider here");
        Log::info(json_decode($request->input('location'))->lat);
        Log::info("haider out");
        



        Log::info('location in request');
        Log::info($request->input('location'));
        $formdata=json_decode($request->input('formdata'),true);
        $siteid=$request->input('siteid');
        $sequence=$request->input('userid');
        $location=json_decode($request->input('location'), true);
        $new_string = '{"lat":'.$location['lat'].',"lng":'.$location['lng'].'}';
        //location logged
        // Log::info(round($location->lat,2));
        // Log::info($location->lng);
        // $lol = [
        //     "lat"=>round($location->lat,2),
        //     "lng"=>round($location->lng,2)
        // ];
        
         
        // $location = ["lat"=> round($location1->lat,7),"lng"=> round($location1->lng,7)];
        Log::info($new_string);
        Log::info($location);
        $sitemedia=$request->input('sitemedia');

        $type=$request->input('type');
        $sitesfield=DB::table('customer_fields')->where('destination_id',$sequence)->where('field_type','Input')->pluck('field_name')[0];
        $id=DB::table( $sequence.'_datatable')->where($sitesfield,$siteid)->pluck('id');
        $cityid='';
        $areaid='';
        $regionid='';
        $cityvalue='';
        $areavalue='';
        $regionvalue='';
        $snagstatus='';
        $Remarks = '';
        $dateValue=date('Y-m');
        foreach($formdata as $data){
              for($j=0;$j<count($data['para']);$j++){
                if($data['para'][$j]['value']['d_do'] != 0 || $data['para'][$j]['value']['d_do'] != ''){
                   $name=DB::table('audit_config')->where('id',$data['para'][$j]['q_id'])->pluck('name')[0];
                   if($name=='City'){
                     $cityid=$data['para'][$j]['value']['d_do'];
                     $dd=DB::table('audit_config')->where('id',$cityid)->pluck('name');
                     if(count($dd)>0){
                        $cityvalue= $dd[0];
                     }
                   }else if($name=='Region'){
                     $regionid=$data['para'][$j]['value']['d_do'];
                     $dd=DB::table('audit_config')->where('id',$regionid)->pluck('name');
                     if(count($dd)>0){
                        $regionvalue= $dd[0];
                     }
                   }else if($name=='Area'){
                       foreach($data['para'][$j]['value']['text'] as $key=>$val){
                          if($data['para'][$j]['value']['text'][$key]!=''){
                              $areavalue=$data['para'][$j]['value']['text'][$key]; 
                          }
                       }
                       
                   }
                }
            }
          }
        if($cityvalue==''|| $regionvalue=='' || $areavalue==''){
            return '0';
        }
        if($request->input('siteid')&&$type=='1'){
            if(count($id)==0){
                $id=DB::table( $sequence.'_datatable')->insertGetId([
                   $sitesfield=>$siteid,'latitude'=>$location['lat'],'longitude'=>$location['lng']
                    ]);
               }else{
                   $id=$id[0];
               }
          foreach($formdata as $data){
              $formname=$data['formname'];
              for($j=0;$j<count($data['para']);$j++){
                  $qname=DB::table('audit_config')->where('id',$data['para'][$j]['q_id'])->pluck('name')[0];
                  $status = false;
                   if(count($data['para'][$j]['value']['text']) == 0){
                       $status = false;
                   }else{
                       foreach($data['para'][$j]['value']['text'] as $key => $dat){
                           if($dat != ''){
                               $status = true;
                           }
                       }
                   }
                   if($data['para'][$j]['value']['c_co'] != 0){
                       $snagstatus = DB::table('audit_config')->where('id', $data['para'][$j]['value']['c_co'])->pluck('name')[0];
                   }else{
                       $snagstatus = '';
                   }
                   if(count($data['para'][$j]['value']['text']) != 0){
                       foreach($data['para'][$j]['value']['text'] as $key=>$tex){
                           $texName = DB::table('audit_config')->where('id', $key)->pluck('name')[0];
                           if($texName == 'Remarks'){
                               $Remarks = $tex;
                           }
                       }
                   }
                   if($data['para'][$j]['value']['c_co'] != 0 || $data['para'][$j]['value']['d_do'] != 0 || $status || count($data['para'][$j]['value']['m_so'])>0){
                        DB::table('audit_result')->insert([
                         'type'=>$type,
                         'identification'=>$id,
                         'customer_id'=>$sequence,
                         'name'=>$siteid,
                         'media'=>$sitemedia,
                         'q_id'=>$data['para'][$j]['q_id'],
                         'q_name'=>$qname,
                         'value'=>json_encode($data['para'][$j]['value']),
                         'position'=>$new_string,
                         'status'=>'latest',
                         'worker_id'=>$request->input('workerid'),
                         'City' => $cityvalue,
                         'Region' => $regionvalue,
                         'Area' => $areavalue,
                         'Snag_Status' => $snagstatus,
                         'dateEntered'=>$dateValue,
                         'form_name'=>$formname,
                         'severity'=>$data['para'][$j]['value']['sev'],
                         'Remarks' => $Remarks,
                         ]);
                       
                   }
            }
          }
        }
        if($type=='2'){
            $id=DB::table('audit_result')->get();
            if(count($id)>0){
                $identification_id = $id[count($id) - 1]->id + 1;
            }else{
                $identification_id=1;
            }
           foreach($formdata as $data){
                  $formname=$data['formname'];
                  for($j=0;$j<count($data['para']);$j++){
                  $qname=DB::table('audit_config')->where('id',$data['para'][$j]['q_id'])->pluck('name')[0];
                   $status = false;
                   if(count($data['para'][$j]['value']['text']) == 0){
                       $status = false;
                   }else{
                       foreach($data['para'][$j]['value']['text'] as $key => $dat){
                           if($dat != ''){
                               $status = true;
                           }
                       }
                   }
                   if($data['para'][$j]['value']['c_co'] != 0){
                       $snagstatus = DB::table('audit_config')->where('id', $data['para'][$j]['value']['c_co'])->pluck('name')[0];
                   }else{
                       $snagstatus = '';
                   }
                   if(count($data['para'][$j]['value']['text']) != 0){
                       foreach($data['para'][$j]['value']['text'] as $key=>$tex){
                           $texName = DB::table('audit_config')->where('id', $key)->pluck('name');
                           if(count($texName) != 0){
                               if($texName[0] == 'Remarks'){
                                    $Remarks = $tex;
                               }
                           }
                       }
                   }
                   if($data['para'][$j]['value']['c_co'] != 0 || $data['para'][$j]['value']['d_do'] != 0 || $status || count($data['para'][$j]['value']['m_so'])>0){
                        DB::table('audit_result')->insert([
                         'type'=>$type,
                         'identification'=>$identification_id,
                         'customer_id'=>$sequence,
                         'name'=>$siteid,
                         'media'=>$sitemedia,
                         'q_id'=>$data['para'][$j]['q_id'],
                         'q_name'=>$qname,
                         'value'=>json_encode($data['para'][$j]['value']),
                         'position'=>$new_string,
                         'status'=>'latest',
                         'worker_id'=>$request->input('workerid'),
                         'City' => $cityvalue,
                         'Region' => $regionvalue,
                         'Area' => $areavalue,
                         'Snag_Status' => $snagstatus,
                         'dateEntered'=>$dateValue,
                         'form_name'=>$formname,
                         'severity'=>$data['para'][$j]['value']['sev'],
                         'Remarks' => $Remarks,
                         ]);
                   }
            } 
           }
        }
        return response('0');
    }
    public function uploadauditmedia(Request $request){
            if($request->file('file') ){
                $Name = $request->file('file')->getClientOriginalName();
                Log::info($Name);
                $destinationPath = public_path('../../public_html/Auditmedia'.'/'.$request->input('userid').'/'.$request->input('siteid'));
                $request->file('file')->move($destinationPath,$Name);
              }
         
        return "0";
    }
    
    public function auditsnags(Request $request){
        
        $sequence = $request->input('userid');
        $data=[];
        $data['forms'] = array();
        $data['auditMarkers'] = array();
        $data['questions'] = array();
        $position=[];
        $auditMarkers = DB::table('audit_result')->where('customer_id', $sequence)->select('position','name')->where('status','latest')->get();
        foreach($auditMarkers as $marker){
            if(!in_array($marker->position, $position)){
                array_push($position,$marker->position);
                array_push($data['auditMarkers'],['position'=>$marker->position,'name'=>$marker->name]);
            } 
        }
        foreach($data['auditMarkers'] as $item){
            $temp = array();
            $temp['position'] = $item;
            $temp['questions'] = array();
                $questions = DB::table('audit_result')->where('position', $item['position'])->where('status','latest')->get();
                foreach($questions as $q){
                    $tompo = array();
                    $tompo['value'] = $q->value;
                    $tompo['id'] = $q->id;
                    $tompo['q_id'] = $q->q_id;
                    $tompo['type'] = $q->type;
                    $tompo['identification'] = $q->identification;
                    $tompo['identificationname'] = $q->name;
                    $qdetail = DB::table('audit_config')->where('id', $q->q_id)->get();
                    $checkoptions = DB::table('audit_config')->where('d_id', $qdetail[0]->id)->where('type', 'c_co')->get();
                    $dropdownoptions = DB::table('audit_config')->where('d_id', $qdetail[0]->id)->where('type', 'd_do')->get();
                    $texts = DB::table('audit_config')->where('d_id', $qdetail[0]->id)->where('type', 'text')->get();
                    $multiselectOptions = DB::table('audit_config')->where('d_id', $qdetail[0]->id)->where('type', 'm_so')->get();
                    $tompo['checkoptions'] = $checkoptions;
                    $tompo['dropdownoptions'] = $dropdownoptions;
                    $tompo['texts'] = $texts;
                    $tompo['media'] = [];
                    $tompo['multiselectOptions'] = $multiselectOptions;
                        $c_co1 = json_decode($q->value,true);
                        $c_co = $c_co1['c_co'];
                        $tompo['name'] = $qdetail[0]->name;
                        if($c_co != '' && $c_co != 0){
                            $checkOption = DB::table('audit_config')->where('id', $c_co)->get();
                            $tompo['status'] = $checkOption[0]->name;
                        }else{
                            $tompo['status'] = 'NA';
                        }
                        // $heading = DB::table('audit_config')->where('id', $qdetail[0]->d_id)->get();
                        // $tompo['heading'] = $heading[0]->name;
                        // $checklist = DB::table('audit_config')->where('id', $heading[0]->d_id)->get();
                        // $tompo['checklist'] = $checklist[0]->name;
                        // $form = DB::table('audit_config')->where('id', $checklist[0]->d_id)->get();
                        // $tompo['form'] = $form[0]->name;
                        
                        // $cityFind = DB::table('audit_result')->where('identification', $q->identification)->where('customer_id', $sequence)->get();
                        
                        // foreach($cityFind as $item1){
                        //     $itemdetail = DB::table('audit_config')->where('id', $item1->q_id)->get();
                        //     if($itemdetail[0]->name == 'City'){
                        //         $d_do1 = json_decode($item1->value,true);
                        //         $d_do = $d_do1['d_do'];
                        //         if($d_do != 0){
                        //             $dropdownoption = DB::table('audit_config')->where('id', $d_do)->get();
                        //             $tompo['city'] = $dropdownoption[0]->name;
                        //         }else{
                        //             $tompo['city'] = 'not entered';
                        //         }
                        //     }
                        // }
                        
                        
                        if($tompo['status'] == 'NOK'){
                            array_push($temp['questions'], $tompo);
                        }
          
                }
            array_push($data['questions'], $temp);
        }
        
        return $data;
    }
    public function uploadsnagmedia(Request $request){
        $sequence = $request->input('userid');
        if($request->file('file') ){
            $Name = $request->file('file')->getClientOriginalName();
            $destinationPath = public_path('Auditmedia'.'/'.$sequence.'/'.$request->input('siteid'));
            $request->file('file')->move($destinationPath,$Name);
            }
         
        return "0";
    }
    
    public function uploadsnagform(Request $request){
        $formdata=json_decode($request->input('formdata'),true);
        $siteid=$request->input('siteid');
        $sequence = $request->input('userid');
        $allmedia=[];
        for($i=0;$i<count($formdata);$i++){
            for($j=0;$j<count($formdata[$i]['media']);$j++){
                array_push($allmedia,$formdata[$i]['media'][$j]);
            }
        }
        foreach($formdata as $data){
              $status = false;
              
              $value=json_decode($data['value'],true);
                   if(count($value['text']) == 0){
                       $status = false;
                   }else{
                       foreach($value['text'] as $key => $dat){
                           if($dat != ''){
                               $status = true;
                           }
                       }
                   }
                   if($value['c_co'] != 0 || $value['d_do'] != 0 || $status || count($value['m_so'])>0){
                        DB::table('audit_result')->where('id',$data['id'])->update([
                            'status'=>'old',
                        ]);
                        $location=DB::table('audit_result')->where('id',$data['id'])->pluck('position')[0];
                        DB::table('audit_result')->insert([
                         'type'=>$data['type'],
                         'identification'=>$data['identification'],
                         'customer_id'=>$sequence,
                         'name'=>$data['identificationname'],
                         'media'=>json_encode($allmedia),
                         'q_id'=>$data['q_id'],
                         'value'=>$data['value'],
                         'position'=>$location,
                         'status'=>'latest',
                         'worker_id'=>$request->input('workerid'),
                         ]);
                       
                   }
          }
        return response('0');
    }
    
    
    
    
    
    
    public function auditsnagsget_positions(Request $request){
        //$sequence = $request->input('userid');
        $sequence = 2;
        $data=[];
        $position = array();
        $checker = array();
        $temp['questions'] = array();

        $auditMarkers = array();
        
        $auditMarkers['positions'] = DB::table('audit_result')->where('customer_id', $sequence)->where('Snag_Status', 'NOK')->where('status','latest')->distinct('position')->pluck('position');
        $auditMarkers = json_encode($auditMarkers);
        return $auditMarkers;
    }
    
     public function auditsnagsget_position_question(Request $request){
        // $sequence = $request->input('userid');
        // $position = $request->input("position");
        // $sequence = 2;
        // if($request->input('position')){
        //     $position = $request->input('position');
        //     $position = json_encode($position);
        // }else{
        //     $position = '{"lat":"33.6127383","lng":"72.9992933"}';
        // }

        // $data=[];
        // $checker = array();
        // $temp = array();
        // $temp['questions'] = array();
        // $temp['cities'] = array();
        
        // $questions = DB::table('audit_result')->where('position', json_encode($position))->get();
        
        // $cities = array();
        // $cityIds = array();
        // $city_idents = array();

        // foreach($questions as $question){
        //     $q = DB::table('audit_config')->where('id', $question->q_id)->get();

        //     if($q[0]->name == 'City'){
        //         $city = $question->value;
        //         $d_do1 = json_decode($city, true);
        //         $d_do = $d_do1['d_do'];
        //         $cityName = DB::table('audit_config')->where('id', $d_do)->select('name')->get();
        //         array_push($cities, $cityName[0]->name);
        //         $cities = array_unique($cities);
        //         array_push($cityIds, $question->q_id);
        //         $identifications = DB::table('audit_result')->whereIn('q_id', $cityIds)->get();
        //     }
        // }

        // foreach($cities as $city){
        //     $temporary['city'] = $city;
        //     $temporary['Identifications'] = array();
        //     foreach($identifications as $identification){
        //         $value = json_decode($identification->value, true)['d_do'];
        //         $tempCity = DB::table('audit_config')->where('id', $value)->select('name')->get();
        //         if($city == $tempCity[0]->name){
        //             array_push($temporary['Identifications'] , $identification->identification);
        //         }
        //     }

        //     array_push($city_idents, $temporary);
        // }
        
        // foreach($city_idents as $key => $value){
        //         $city_idents[$key]['Identifications'] = array_unique($value['Identifications']);
        // }
        
        // $temp['cities'] = $city_idents;
        
        // return $temp;
        
        // foreach($questions as $q){
        //     $heading_id = 
        //     $tompo = array();
        //     $tompo['value'] = $q->value;
        //     $tompo['id'] = $q->id;
        //     $tompo['q_id'] = $q->q_id;
        //     $tompo['type'] = $q->type;
        //     $tompo['identification'] = $q->identification;
        //     $tompo['identificationname'] = $q->name;
        //     $checkoptions = DB::table('audit_config')->where('d_id', $q->q_id)->where('type', 'c_co')->get();
        //     array_push($checker,$checkoptions);
        //     $dropdownoptions = DB::table('audit_config')->where('d_id', $q->q_id)->where('type', 'd_do')->get();
        //     $texts = DB::table('audit_config')->where('d_id', $q->q_id)->where('type', 'text')->get();
        //     $multiselectOptions = DB::table('audit_config')->where('d_id', $q->q_id)->where('type', 'm_so')->get();
        //     $tompo['checkoptions'] = $checkoptions;
        //     $tompo['dropdownoptions'] = $dropdownoptions;
        //     $tompo['texts'] = $texts;
        //     $tompo['media'] = [];
        //     $tompo['multiselectOptions'] = $multiselectOptions;
        //     $decoded = json_decode($q->value,true);
        //     $c_co = $decoded['c_co'];
        //     //$d_do = $decoded['d_do'];
        //     $tompo['name'] = DB::table('audit_config')->where('id', $q->q_id)->pluck("name")[0];
        //     if($c_co != '' && $c_co != 0){
        //         $checkOption = DB::table('audit_config')->where('id', $c_co)->get();
        //         $tompo['status'] = $checkOption[0]->name;
        //     }else{
        //         $tompo['status'] = 'NA';
        //     }
        //      $heading = DB::table('audit_config')->where('id', DB::table('audit_config')->where('id', $q->q_id)->pluck("d_id")[0])->select('name', 'd_id')->get();
        //      $tompo['heading'] = $heading[0]->name;
        //      $checklist = DB::table('audit_config')->where('id', $heading[0]->d_id)->select('name', 'd_id')->get();
        //      $tompo['checklist'] = $checklist[0]->name;
        //      $form = DB::table('audit_config')->where('id', $checklist[0]->d_id)->select('name', 'id')->get();
        //      $tompo['form'] = $form[0]->name;
             
        //      $areaChecklist = DB::table('audit_config')->where('d_id', $form[0]->id)->where('type', 'checklist')->where('name', 'Area Info')->pluck('id')[0];
             
        //      $areaHeading = DB::table('audit_config')->where('d_id', $areaChecklist)->where('type', 'heading')->pluck('id')[0];
             
        //      $cityQuestion = DB::table('audit_config')->where('d_id', $areaHeading)->where('type', 'q')->where('name', 'City')->pluck('id')[0];
             
        //      $tempCities = DB::table('audit_config')->where('d_id', $cityQuestion)->select('name', 'id')->get();
             
        //      $tompo['cities'] = $tempCities;
             
        //      //$cityFind = DB::table('audit_result')->where('identification', $q->identification)->where('customer_id', $sequence)->where('q_id', $cityQuestion)->count();
             
        //     //  $tompo['cityFind'] = $cityFind;
             
        //     //  $d_do1 = json_decode($cityFind[0],true);
        //     //  $d_do = $d_do1['d_do'];
             
        //     //  $tompo['d_do'] = $d_do;
             
                        
        //     // foreach($cityFind as $item1){
        //     //     $name = DB::table('audit_config')->where('id', $item1->q_id)->pluck('name')[0];
        //     //     if($name == 'City'){
        //     //         $d_do1 = json_decode($item1->value,true);
        //     //         $d_do = $d_do1['d_do'];
        //     //         if($d_do != 0){
        //     //             $dropdownoption = DB::table('audit_config')->where('id', $d_do)->get();
        //     //             $tompo['city'] = $dropdownoption[0]->name;
        //     //         }else{
        //     //             $tompo['city'] = 'not entered';
        //     //         }
        //     //     }
        //     // }
                        
                        
        //     if($tompo['status'] == 'NOK'){
        //         array_push($temp['questions'], $tompo);
        //     }
          
                
                
        //     }
        // return $temp;

        /* New Code For Quesions */

        Log::info("reaching");
        
        $final = array();
        
        // $sequence = 2;
        
        // $position = $request->input('position');

        if($request->input('cust_id')){
            $sequence = $request->input('cust_id');
        }else{
            $sequence = 2;
        }
        if($request->input('position')){
            $position = $request->input('position');
            $position = json_encode($position);
        }else{
            $position = '{"lat":33.6127383,"lng":72.9992933}';
        }
        
        $finalQuestions = array();
        
        $forms = array();
        
        $tempforms = DB::table('audit_config')->where('d_id', $sequence)->where('type', 'form')->get();
        
        foreach($tempforms as $form){
            $temp = array();
            $temp['name'] = $form->name;
            $temp['checklists'] = array();
            
            $formchecklists = DB::table('audit_config')->where('d_id', $form->id)->where('type', 'checklist')->get();
            
            foreach($formchecklists as $checkl){
                $tempe = array();
                $tempe['name'] = $checkl->name;
                $tempe['headings'] = array();
                
                $headings = DB::table('audit_config')->where('d_id', $checkl->id)->where('type', 'heading')->get();
                
                foreach($headings as $head){
                    array_push($tempe['headings'], $head->name);
                }
                
                array_push($temp['checklists'], $tempe);
            }
           
            array_push($forms, $temp);
        }
        
        $dates = DB::table('audit_result')->where('customer_id', $sequence)->where('position', $position)->distinct()->pluck('dateEntered');
        
        foreach($dates as $date){
                $temporary = array();
                $temporary['month'] = $date;
                $temporary['question'] = array();
                $questions = DB::table('audit_result')->where('customer_id', $sequence)->where('dateEntered', $date)->where('position', $position)->where('Snag_Status', 'NOK')->get();
                foreach($questions as $q){
                    $tompo = array();
                    $qdetail = DB::table('audit_config')->where('id', $q->q_id)->get();
                    if(count($qdetail) > 0){
                        $tompo['name'] = $qdetail[0]->name;
                        $checkoptions = DB::table('audit_config')->where('d_id', $qdetail[0]->id)->where('type', 'c_co')->get();
                        $dropdownoptions = DB::table('audit_config')->where('d_id', $qdetail[0]->id)->where('type', 'd_do')->get();
                        $texts = DB::table('audit_config')->where('d_id', $qdetail[0]->id)->where('type', 'text')->get();
                        $multiselectOptions = DB::table('audit_config')->where('d_id', $qdetail[0]->id)->where('type', 'm_so')->get();
                        $tompo['checkoptions'] = $checkoptions;
                        $tompo['dropdownoptions'] = $dropdownoptions;
                        $tompo['texts'] = $texts;
                        $tompo['multiselectOptions'] = $multiselectOptions;
                        $value = json_decode($q->value,true);
                        $tompo['c_co'] = $value['c_co'];
                        $tompo['d_do'] = $value['d_do'];
                        $tompo['text'] = $value['text'];
                        $tompo['m_so'] = $value['m_so'];
                            
                        $heading = DB::table('audit_config')->where('id', $qdetail[0]->d_id)->get();
                        $tompo['heading'] = $heading[0]->name;
                        $checklist = DB::table('audit_config')->where('id', $heading[0]->d_id)->get();
                        $tompo['checklist'] = $checklist[0]->name;
                        $form = DB::table('audit_config')->where('id', $checklist[0]->d_id)->get();
                        $tompo['form'] = $form[0]->name;
                        
                        array_push($temporary['question'], $tompo);
                    }
          
                }
                //$temporary['question']= $questions;
                array_push($finalQuestions, $temporary);
            }
        
        $final['position'] = $position;
        $final['dates'] = $dates;
        $final['question'] = $finalQuestions;
        $final['forms'] = $forms;
        
        return $final;

        /* New Code For Questions */
    }
    
    
    
    
    public function auditsnagsget(Request $request){

     $data['NOK']=0;
     $data['OK']=0;
     $data['LahoreOk']=0;
     $data['LahoreNOk']=0;
     $data['IslamabadOk']=0;
     $data['IslamabadNOk']=0;
     $data['GujranwalaOk']=0;
     $data['GujranwalaNOk']=0;
     $data['SialkotOk']=0;
     $data['SialkotNOk']=0;
     $data['peshawarOk']=0;
     $data['peshawarNOk']=0;
     $data['FaisalabadOk']=0;
     $data['FaisalabadNOk']=0;
     $data['SargodhaOk']=0;
     $data['SargodhaNOk']=0;
     $alldata=DB::table('audit_result')->where('customer_id','145')->where('status','latest')->select('City','Snag_Status')->get();
     foreach($alldata as $dat){
        switch ($dat->City) {
                  case 'Lahore':
                      switch ($dat->Snag_Status) {
                          case 'OK':
                              $data['OK']+=1;
                              $data['LahoreOk']+=1;
                            break;
                          case 'NOK':
                              $data['NOK']+=1;
                              $data['LahoreNOk']+=1;
                            break;
                          default:
                        }
                    break;
                  case 'Islamabad':
                        switch ($dat->Snag_Status) {
                          case 'OK':
                              $data['OK']+=1;
                              $data['IslamabadOk']+=1;
                            break;
                          case 'NOK':
                              $data['NOK']+=1;
                              $data['IslamabadNOk']+=1;
                            break;
                          default:
                        }
                    break;
                  case 'Gujranwala':
                        switch ($dat->Snag_Status) {
                          case 'OK':
                              $data['OK']+=1;
                              $data['GujranwalaOk']+=1;
                            break;
                          case 'NOK':
                              $data['NOK']+=1;
                              $data['GujranwalaNOk']+=1;
                            break;
                          default:
                        }
                    break;
                  case 'Sialkot':
                        switch ($dat->Snag_Status) {
                          case 'OK':
                              $data['OK']+=1;
                              $data['SialkotOk']+=1;
                            break;
                          case 'NOK':
                              $data['NOK']+=1;
                              $data['SialkotNOk']+=1;
                            break;
                          default:
                        }
                    break; 
                  case 'peshawar':
                        switch ($dat->Snag_Status) {
                          case 'OK':
                              $data['OK']+=1;
                              $data['peshawarOk']+=1;
                            break;
                          case 'NOK':
                              $data['NOK']+=1;
                              $data['peshawarNOk']+=1;
                            break;
                          default:
                        }
                    break;
                  case 'Sargodha':
                        switch ($dat->Snag_Status) {
                          case 'OK':
                              $data['OK']+=1;
                              $data['SargodhaOk']+=1;
                            break;
                          case 'NOK':
                              $data['NOK']+=1;
                              $data['SargodhaOk']+=1;
                            break;
                          default:
                        }
                    break;
                  default:
                }        
        }

     return $data;
    }
    
        
    public function visitrevisit(Request $request){

     $visit['visit']=0;
     $revisit['revisit']=0;
     $visit['Lahorevisit']=0;
     $revisit['Lahorerevisit']=0;
     $visit['Islamabadvisit']=0;
     $revisit['Islamabadrevisit']=0;
     $visit['Gujranwalavisit']=0;
     $revisit['Gujranwalarevisit']=0;
     $visit['Sialkotvisit']=0;
     $revisit['Sialkotrevisit']=0;
     $visit['peshawarvisit']=0;
     $revisit['peshawarrevisit']=0;
     $visit['Faisalabadvisit']=0;
     $revisit['Faisalabadrevisit']=0;
     $visit['Sargodhavisit']=0;
     $revisit['Sargodharevisit']=0;
     $alldata=DB::table('audit_result')->where('customer_id','145')->where('status','latest')->select('City','Snag_Status')->get();
     foreach($alldata as $dat){
        switch ($dat->City) {
                  case 'Lahore':
                      switch ($dat->Snag_Status) {
                          case 'OK':
                              $visit['visit']+=1;
                              $visit['Lahorevisit']+=1;
                            break;
                          case 'NOK':
                              $revisit['revisit']+=1;
                              $revisit['Lahorerevisit']+=1;
                            break;
                          default:
                        }
                    break;
                  case 'Islamabad':
                        switch ($dat->Snag_Status) {
                          case 'OK':
                              $visit['visit']+=1;
                              $visit['Islamabadvisit']+=1;
                            break;
                          case 'NOK':
                              $revisit['revisit']+=1;
                              $revisit['Islamabadrevisit']+=1;
                            break;
                          default:
                        }
                    break;
                  case 'Gujranwala':
                        switch ($dat->Snag_Status) {
                          case 'OK':
                              $visit['visit']+=1;
                              $visit['Gujranwalavisit']+=1;
                            break;
                          case 'NOK':
                              $revisit['revisit']+=1;
                              $revisit['Gujranwalarevisit']+=1;
                            break;
                          default:
                        }
                    break;
                  case 'Sialkot':
                        switch ($dat->Snag_Status) {
                          case 'OK':
                              $visit['visit']+=1;
                              $visit['Sialkotvisit']+=1;
                            break;
                          case 'NOK':
                              $revisit['revisit']+=1;
                              $revisit['Sialkotrevisit']+=1;
                            break;
                          default:
                        }
                    break; 
                  case 'peshawar':
                        switch ($dat->Snag_Status) {
                          case 'OK':
                              $visit['visit']+=1;
                              $visit['peshawarvisit']+=1;
                            break;
                          case 'NOK':
                              $revisit['revisit']+=1;
                              $revisit['peshawarrevisit']+=1;
                            break;
                          default:
                        }
                    break;
                  case 'Sargodha':
                        switch ($dat->Snag_Status) {
                          case 'OK':
                              $visit['visit']+=1;
                              $visit['Sargodhavisit']+=1;
                            break;
                          case 'NOK':
                              $revisit['revisit']+=1;
                              $revisit['Sargodharevisit']+=1;
                            break;
                          default:
                        }
                    break;
                  default:
                }        
            }

     return $data;
    }
    public function auditdatapopulation(Request $request){
       $postdata=$request->getContent();
       $aa=json_decode($postdata,true);
       $bb=$aa['data'];
       $dateValue=date('Y-m');
        $id=DB::table('audit_result')->pluck('id');
            $size=count($id);
            if($size>0){
                $id=$id[$size-1]+1;
            }else{
                $id=1;
            }
        $idincreae=0;        
       for($i=1;$i<700;$i++){
        $id=$id+$idincreae;
        $formdata=$bb;
        $siteid='test'.$i;
        $sequence=145;
        $location=['lat'=>'33.6'.$i,'lng'=>'73.'.$i];
        $sitemedia='';
        $type=2;
        $cityid='Faisalabad';
        $areaid='area'.$i;
        $regionid='Central';
        $cityvalue='Islamabad';
        $areavalue='area'.$i;
        $regionvalue='North';
        $snagstatus='';
        if($cityvalue==''|| $regionvalue=='' || $areavalue==''){
            return '0';
        }
        if($type=='2'){
         
           foreach($formdata as $data){
                  $formname=$data['formname'];
                  for($j=0;$j<count($data['para']);$j++){
                   $status = false;
                   if(count($data['para'][$j]['value']['text']) == 0){
                       $status = false;
                   }else{
                       foreach($data['para'][$j]['value']['text'] as $key => $dat){
                           if($dat != ''){
                               $status = true;
                           }
                       }
                   }
                   if($data['para'][$j]['value']['c_co'] != 0){
                       $snagstatus = DB::table('audit_config')->where('id', $data['para'][$j]['value']['c_co'])->pluck('name')[0];
                   }else{
                       $snagstatus = '';
                   }
                   if($data['para'][$j]['value']['c_co'] != 0 || $data['para'][$j]['value']['d_do'] != 0 || $status || count($data['para'][$j]['value']['m_so'])>0){
                        DB::table('audit_result')->insert([
                         'type'=>$type,
                         'identification'=>$id,
                         'customer_id'=>$sequence,
                         'name'=>$siteid,
                         'media'=>$sitemedia,
                         'q_id'=>$data['para'][$j]['q_id'],
                         'value'=>json_encode($data['para'][$j]['value']),
                         'position'=>json_encode($location),
                         'status'=>'latest',
                         'worker_id'=>'99',
                         'City' => $cityvalue,
                         'Region' => $regionvalue,
                         'Area' => $areavalue,
                         'Snag_Status' => $snagstatus,
                         'dateEntered'=>$dateValue,
                         'form_name'=>$formname,
                         'severity'=>$data['para'][$j]['value']['sev']
                         ]);
                         $idincreae=$idincreae+1;
                   }
            } 
           }
        }
        
       }
       return response('0');
    }
    public function getaudittracks(){
        $data=DB::table('fiber')->where('id',80)->get();
        $sum=0;
         foreach($data as $track){
                $data = json_decode($track->data, true);
                 foreach($data['track'] as $tr){
                        for($i=0; $i < count($tr['data'])-1; $i++){
                           $val=$this->distance($tr['data'][$i]['lat'], $tr['data'][$i]['lng'], $tr['data'][$i+1]['lat'], $tr['data'][$i+1]['lng']);
                         
                           $sum = $sum + $val;
                        }
                    }
            }
        return $sum;
    }
    function distance($lat1, $lon1, $lat2, $lon2) {
        if($lat1==$lat2 && $lon1==$lon2){
            return 0;
        }
          $latFrom = deg2rad($lat1);
          $lonFrom = deg2rad($lon1);
          $latTo = deg2rad($lat2);
          $lonTo = deg2rad($lon2);
          $earthRadius=6371;
          $latDelta = $latTo - $latFrom;
          $lonDelta = $lonTo - $lonFrom;
        
          $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
          return round($angle * $earthRadius,5);
    }
}
