<?php

namespace App\Http\Controllers\Customer\Fiber;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Storage;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Arr;
use DateTime;
use DatePeriod;
use DateInterval;
use Auth;
use Carbon;
use File;
class CustomerFiberController extends Controller
{
   public $fiberanalytics=[["name"=>"Anchoring Guying","open"=>false,"select"=>[],"data"=>['building-guy','down-guy-with-anchor','down-guy-with-anchor-or-set-cable-system','over-head-guy','pole-to-pole-guy', 'push-brace','rock-guy','sidewalk-down-guy-with-anchor',
    'sidewalk-down-guy-with-anchor-or-set-cable-system','tree-guy']],
    ["name"=>"Cable Support","open"=>false,"select"=>[],"data"=>[ 'extension-arm','mid-span-crossover','slack-span-strand','tension-strand',]],
    ["name"=>"House Drop Designation","open"=>false,"select"=>[],"data"=>[  'commercial-count','house-count','house-count-actual-or-potential','In-Building','multiple-dwelling-units']],
    ["name"=>"OFC-OSP","open"=>false,"select"=>[],"data"=>[  'Aerial Cable','central-office','Connector','direct-burried-conduit-routing','FDH','Fiber-Interconnection','Fiber-tension','Guy','hand-hole','Junction-Block','man-hole','Multipler','ONT','Optical-Cross-Connect',
    'Optical-Distribution-Frame','Optical-Line-Terminal','Optical-Repeator','Optical-strage-looop','Patch-Pannel','Pedastol', 'point-of-presence','power-transformer','pullbox','push-brace','rack','riser','road-bore','shelter-hut','slack-loop',
    'slack-snowshoe','splice-colsure','Splitor','traffic-cabinet','UG Cable','underground-routing','vault'],],
    ["name"=>"Optical Devices","open"=>false,"select"=>[],"data"=>['By Directional Mux & Demux','Demultiplexer','Fiber node Forward & Reverse','Multiplyer','Optical Transmitter (Forward & Reverse)','Optical-amplifier','Optical-node-forward & Reverse','Optical-node-forward-only','Optical-transmitter-forward-only']],
    ["name"=>"Optical Splice Symbols","open"=>false,"select"=>[],"data"=>['2-way-splice','3-way-splice','4-way-splice','Above 4-way-splice','mid-entry-splice-or-ring-cut']],
    ["name"=>"Poles","open"=>false,"select"=>[],"data"=>[   'comms-pole-2','concrete-pole','drop-pole','fiber-reinforced-pole','joint-usage-pole','joint-usage-with-power-trasnformer','power-pole','power-transformer-platform','power-transformer-pole',
                                                         'riser-pole','steel-pole','Street-Light','telephone-pole','transmission-line-contact']],
    ["name"=>"Signal Processing","open"=>false,"select"=>[],"data"=>[ "head-end", "primary-hub", "secondary-hub"]],
    ["name"=>"Spliting Devices","open"=>false,"select"=>[],"data"=>[ "2-way-spliter","3-way-spliter","Directional",]],
    ["name"=>"Subscriber Taps","open"=>false,"select"=>[],"data"=>[    '1-output-directional-tap','2-output-directional-tap','3-output-directional-tap','4-output-directional-tap','8-output-directional-tap']],
    ["name"=>"Wireless Devices","open"=>false,"select"=>[],"data"=>[ 'directional-customer-premises-equipment','directional-wireless-hub','omni-directional-customer-premises-equipment','omni-directional-wireless-hub']],
                       
    ];
    public $transanalytics=[["name"=>"OFC","open"=>false,"select"=>[],"data"=>['2 way duct','3 way duct','4 way duct','commercial building','commercial-count','comms-pole-2','concrete-pole','dc','drop-pole','FAT','Fiber-Interconnection',
    'fiber-reinforced-pole','Fiber-tension','Guy','hand hole 2 by 2','hand hole 3 by 3','hand hole 4 by 4','hand hole 5 by 5','joint enclosure','joint-usage-pole','joint-usage-with-power-transformer','Joint BOX','man-hole','over-head-guy',
    'pole-to-pole-guy','power-pole','power-transformer-pole','riser-pole','road-bore','rock-guy', 'Single Building','splice-colsure','steel-pole','telephone-pole','vault','protection hdpe','protection gi','FDH','ONT', 'tower', 'suspension_clamp']],
    ];
     public $fibercables=[["name"=>"Cable Types","open"=>false,"select"=>[],"data"=>["8F Figure-8 Aerial","24F Figure-8 Aerial","48F Figure-8 Aerial","8F ADSS Aerial","12F ADSS Aerial","24F ADSS Aerial","48F ADSS Aerial",
     "8F Buried","12F Buried","24F Buried ","48F Buried","96F Buried","144F Buried","288F Buried","8F Duct","12F Duct","24F Duct ","48F Duct","96F Duct","144F Duct","288F Duct","2F CLT Aerial"]],];
    public function fiberlogin(Request $request){
        
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
    public function trackdata(Request $request){
    if($request->input('data')){
        $data=json_decode($request->input('data'),true);
       /* to make sure */
       $city=$data['city'];
       $region=$data['region'];
        DB::table('fiber')->insert(['data'=>$request->input('data'), 'city' => $city, 'region' => $region, 'user_id'=>$request->input('userid'),'worker_id'=>$request->input('workerid')]);
        return ('data entered');
    }
    return ('There is a problem in data entering');
    }
    public function trackmedia(Request $request){
          if($request->file('file') && $request->input('data')){
                $data=json_decode($request->input('data'));
                $type=$data->type;
                $Name = $request->file('file')->getClientOriginalName();
                DB::table('fibermedia')->insert([
                    'data'=>$request->input('data'),
                    'uploadedby'=>$request->input('userid')
                    ]);
                $destinationPath = public_path('../../public_html/FiberAppMedia'.'/'.$request->input('userid').'/'.$type);
                $request->file('file')->move($destinationPath,$Name);
             
          }
        if($request->input('track')){
                $Name = $request->file('file')->getClientOriginalName();
                $destinationPath = public_path('../../public_html/FiberAppMedia'.'/'.$request->input('userid').'/'.$request->input('track').'/'.$request->input('type'));
                $request->file('file')->move($destinationPath,$Name);

          } 
              return "0";
        
    }
    public function fiber(){
        $sequence = 2;
        if(Auth()->user()){
           $sequence=Auth()->user()->id;
        }
        Log::info($sequence); 
        $data=[];
        $data['data']=DB::table('fiber')->where('user_id',$sequence)->where('id', '>' , 94)->select('id', 'data', 'row_status', 'row_detail')->get();
        $data['ana']=$this->transanalytics ; 
        $data['cable']=$this->fibercables ;
        $data['id']=$sequence;
        //$data['kmldata']=DB::table('kml_tracks')->get();
        
        $data['forms'] = array();
        $data['auditMarkers'] = array();
        
        $forms = DB::table('audit_config')->where('d_id', $sequence)->where('type', 'form')->select('id', 'name')->get();
        
        foreach($forms as $form){
            $temp = array();
            $temp['name'] = $form->name;
            $temp['checklists'] = array();
            
            $formchecklists = DB::table('audit_config')->where('d_id', $form->id)->where('type', 'checklist')->select('name', 'id')->get();
            
            foreach($formchecklists as $checkl){
                $tempe = array();
                $tempe['name'] = $checkl->name;
                $tempe['headings'] = array();
                
                $headings = DB::table('audit_config')->where('d_id', $checkl->id)->where('type', 'heading')->pluck('name');
                
                foreach($headings as $head){
                    array_push($tempe['headings'], $head);
                }
                
                array_push($temp['checklists'], $tempe);
            }
           
            array_push($data['forms'], $temp);
        }
        
        $data['auditMarkers'] = DB::table('audit_result')->where('customer_id', $sequence)->distinct()->pluck('position');
        
        return $data;

    }
    public function fiberdelete(Request $request){
        $cust_id = 145;
        if(Auth()->user()){
           $cust_id=Auth()->user()->id;
        }
        $sequence =$request->input('id');
        $name=$request->input('name');
        Log::info($sequence);
        Log::info($name);
        $data=[];
        $data['data']=DB::table('fiber')->where('id',$sequence)->delete();
        File::deleteDirectory(public_path('FiberAppMedia/'.$cust_id. '/' .$name));
        return 'Deleted';
    }
    public function kmlEntry(Request $request){
        $startId = 0;
        $folderId = 0;
        $data = json_decode($request->input('arr'));
        for($i = 0; $i< count($data); $i++){
           if($data[$i]->type == "Start"){
              $startId = DB::table('kml_tracks')->insertGetId([
                  'name'=>$data[$i]->name,
                  'type'=>$data[$i]->type,
                  'parent_id'=>$data[$i]->parent_id,
                  'data'=>'null',
                  'style'=>'null',
                  ]); 
           }else if($data[$i]->type == "Folder"){
                $folderId = DB::table('kml_tracks')->insertGetId([
                  'name'=>$data[$i]->name,
                  'type'=>$data[$i]->type,
                  'parent_id'=>$startId,
                  'data'=>'null',
                  'style'=>'null',
                  ]);
           }else{
               DB::table('kml_tracks')->insert([
                  'name'=>$data[$i]->name,
                  'type'=>$data[$i]->type,
                  'parent_id'=>$folderId,
                  'data'=>json_encode($data[$i]->data),
                  'style'=>$data[$i]->style,
                  ]);
           }
            
        }
    }
}
