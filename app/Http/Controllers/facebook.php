<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Mail;

class facebook extends Controller
{
    public $transanalytics=[["name"=>"OFC","open"=>false,"select"=>[],"data"=>['2 way duct','3 way duct','4 way duct','commercial building','commercial-count','comms-pole-2','concrete-pole','dc','drop-pole','FAT','Fiber-Interconnection',
    'fiber-reinforced-pole','Fiber-tension','Guy','hand hole 2 by 2','hand hole 3 by 3','hand hole 4 by 4','hand hole 5 by 5','joint enclosure','joint-usage-pole','joint-usage-with-power-transformer','Joint BOX','man-hole','over-head-guy',
    'pole-to-pole-guy','power-pole','power-transformer-pole','riser-pole','road-bore','rock-guy', 'Single Building','splice-colsure','steel-pole','telephone-pole','vault','protection hdpe','protection gi','FDH','ONT', 'tower', 'suspension_clamp']],
    ];
     public $fibercables=[["name"=>"Cable Types","open"=>false,"select"=>[],"data"=>["8F Figure-8 Aerial","24F Figure-8 Aerial","48F Figure-8 Aerial","8F ADSS Aerial","12F ADSS Aerial","24F ADSS Aerial","48F ADSS Aerial",
     "8F Buried","12F Buried","24F Buried ","48F Buried","96F Buried","144F Buried","288F Buried","8F Duct","12F Duct","24F Duct ","48F Duct","96F Duct","144F Duct","288F Duct","2F CLT Aerial"]],];
     

   protected function random_strings($length_of_string)
    {
    
        // String of all alphanumeric character
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    
        // Shuffle the $str_result and returns substring
        // of specified length
        return substr(str_shuffle($str_result), 
                        0, $length_of_string);
    }
    
    public function getTimeNow(){
        return strtotime("now");
    }

    
    public function register(Request $request){
        $request_content =  json_decode($request->getContent(),true);
        $dynamic_data = [];
        
        if ( $request_content["username"]){
            $dynamic_data["username"] = $request_content["username"];
            $checker = DB::table('users')->where('username',$dynamic_data["username"])->pluck('id');
            if(count($checker) > 0){
               return abort(500, 'username already exists');
            }

        }else{
            return abort(500, 'please provide username');
        }
        if ($request_content["name"]){
            $dynamic_data["name"] = $request_content["name"];
        }
        if ( $request_content["email"]){
            $dynamic_data["email"] = $request_content["email"];
            $checker = DB::table('users')->where('email',$dynamic_data["email"])->pluck('id');
            if(count($checker) > 0){
               return abort(500, 'username already exists');
            }
        }
        if ( $request_content["password"]){
            $dynamic_data["password"] = Hash::make($request_content["password"]);
        }
        $dynamic_data["access_token"] = $this->random_strings(64);
        $dynamic_data["refresh_token"] = $this->random_strings(64);
        $dynamic_data["access_token_expiry"] = now()->addMinutes("15");
        $dynamic_data["refresh_token_expiry"] = now()->addMinutes("60");
        $dynamic_data["created_at"] = now();

        DB::table('users')->insert($dynamic_data);
        
        
    }


    protected function auth_checker(Request $request){
        if ($request->hasHeader('X-Access-Token')) {
           
            $access_token = $request->header('X-Access-Token');
            $user_id = $request->header('X-User-ID');

            Log::info($access_token);
            Log::info($user_id);
           
            $checker = DB::table("users")->where('id',$user_id)->where("access_token",$access_token)->whereDate("access_token_expiry",">=",date('y-m-d',strtotime(now())))->whereTime("access_token_expiry",">=",date('H:i:s',strtotime(now())))->pluck('id');

            Log::info($checker);
            if (count($checker) > 0){
                return true;
            }else{
                return false;
            }
        }
        
    }
    public function token_exchange(Request $request){
        if ($request->hasHeader('X-Refresh-Token')) {
           
            $access_token = $request->header('X-Access-Token');
            $refresh_token = $request->header('X-Refresh-Token');
            $user_id = $request->header('X-User-ID');

            $checker = DB::table("users")->where('id',$user_id)->where("access_token",$access_token)->where("refresh_token",$refresh_token)->where("refresh_token_expiry",">",now())->pluck('id');
            if (count($checker) > 0){
                $dynamic_data = [];
                $dynamic_data["access_token"] = $this->random_strings(64);
                $dynamic_data["refresh_token"] = $this->random_strings(64);
                $dynamic_data["access_token_expiry"] = now()->addMinutes("15");
                $dynamic_data["refresh_token_expiry"] = now()->addMinutes("60");
                DB::table('users')->where('id',$user_id)->update($dynamic_data);

                $resp = DB::table('users')->where("id",$user_id)->select('id','email','name','access_token','refresh_token','access_token_expiry','refresh_token_expiry')->get();
                $final = array();
                   $final['id'] = $resp[0]->id;
                   $final['email'] = $resp[0]->email;
                   $final['name'] = $resp[0]->name;
                   $final['access_token'] = $resp[0]->access_token;
                   $final['refresh_token'] = $resp[0]->refresh_token;
                   $final['access_token_expiry'] = strtotime($resp[0]->access_token_expiry);
                   $final['refresh_token_expiry'] = strtotime($resp[0]->refresh_token_expiry);
                   return $final; 
            }else{
                return abort(500, 'token expired');
            }
        }
    }
    
    public function login(Request $request){
        $dynamic_data = [];
        $request_content =  json_decode($request->getContent(),true);
        if ($request_content["username"] && $request_content["password"]){
            $checker = DB::table('users')->where('username',$request_content["username"])->orWhere('email',$request_content["username"])->pluck('id');
            if(count($checker) > 0){
               $db_password = DB::table('users')->where('id',$checker[0])->pluck('password')[0];
               
               if (Hash::check($request_content["password"], $db_password)){
                    $dynamic_data["access_token"] = $this->random_strings(64);
                    $dynamic_data["refresh_token"] = $this->random_strings(64);
                    $dynamic_data["access_token_expiry"] = now()->addMinutes("15");
                    $dynamic_data["refresh_token_expiry"] = now()->addMinutes("60");
                    $dynamic_data["updated_at"] = now();
        
                    DB::table('users')->where('id',$checker[0])->update($dynamic_data);
                   $resp = DB::table('users')->where('id', $checker[0])->select('id','email','name','access_token','refresh_token','access_token_expiry','refresh_token_expiry')->get();
                   $final = array();
                   $final['id'] = $resp[0]->id;
                   $final['email'] = $resp[0]->email;
                   $final['name'] = $resp[0]->name;
                   $final['access_token'] = $resp[0]->access_token;
                   $final['refresh_token'] = $resp[0]->refresh_token;
                   $final['access_token_expiry'] = strtotime($resp[0]->access_token_expiry);
                   $final['refresh_token_expiry'] = strtotime($resp[0]->refresh_token_expiry);
                   return $final; 
               }else{
                return abort(500, 'incorrect password'); 
               }
            }else{
                return abort(500, 'username or password incorrect'); 
            }
        }else{
            return abort(500, 'no username and password');
        }
    }

    

    public function dummy_test(Request $request){
        $stat = $this->auth_checker($request);
        if($stat != false){
            return "all ok";
        }else{
            return 'login needed'; 
        }
    }

    public function test(Request $request){ 

        $final = array();

        $monitoring = array();

        $final['first name'] = "jaffer";
        $final['last name'] = "mansoor";
        $final['monitoring'] = $monitoring;

        $response = Http::post('https://joynaudits.com/api/testing');

        $final['hello'] = $response->json();

        return $final;
    }
   

    public function snagDashboardInit(Request $request){

        $stat = $this->auth_checker($request);
        if($stat == false){
            return "login";
        }

        $final = array();

        $sequence = 2;
        //$questions = DB::table('audit_result')->where('customer_id', 145)->get();
        $months = DB::table('audit_result')->distinct('dateEntered')->where('customer_id', $sequence)->where('Snag_Status', '<>', "")->orderBy('dateEntered', 'asc')->pluck('dateEntered');

        $typesIncoming = DB::table('audit_config')->where('type', 'form')->where('d_id', $sequence)->get();

        $dates = array(); 
        $types = array();
        $days = ['15', '30', '60', '90'];

        foreach($typesIncoming as $type){
            array_push($types, $type->name);
        }
        
        //$types = ['Form1', 'Form2'];


        $cityWiseOpenClosedSnags = array();
        $cityTypeWiseSnags = array();
        $citySevWiseSnags = array();
        $snagAging = array();
        $timeToResolve = array();
        
        $cities = DB::table('audit_result')->where('customer_id',$sequence)->where('Snag_Status', '<>', "")->distinct()->pluck('City');
        
        $tempSev = array();
        $tempSev['low'] = array();
        $tempSev['medium'] = array();
        $tempSev['high'] = array();
        
        /*city wise open closed snags*/ 
        foreach($cities as $city){
            
            $temp = array();
            $temp['city'] = $city;
            $temp['openSnags'] = array();
            $temp['closedSnags'] = array();
            $temp['total'] = array();
            foreach($months as $month){
                $openSnags = 0;
                $closedSnags = 0;
                $openSnags = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->where('status', 'latest')->where('Snag_Status', "NOK")->where('dateEntered', $month)->count();
                $closedSnags = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->where('closed_status', "yes")->where('dateEntered', $month)->count();
         
                
                array_push($temp['openSnags'], $openSnags);
                array_push($temp['closedSnags'], $closedSnags);
            }

            foreach($temp['openSnags'] as $key => $value){
                $total = 0;
                $total = $temp['openSnags'][$key] + $temp['closedSnags'][$key];
                array_push($temp['total'], $total);
            }

            array_push($cityWiseOpenClosedSnags, $temp);
            
            $temp = array();
            $temp2['city'] = $city;
            $temp2['low'] = array();
            $temp2['medium'] = array();
            $temp2['high'] = array();


            foreach($types as $type){
                $low = 0;
                $medium = 0;
                $high = 0;

                $low = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->where('form_name', $type)->where('status', 'latest')->where('Snag_Status', 'NOK')->where('severity', '1')->count();
                $medium = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->where('form_name', $type)->where('status', 'latest')->where('Snag_Status', 'NOK')->where('severity', '2')->count();
                $high = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->where('form_name', $type)->where('status', 'latest')->where('Snag_Status', 'NOK')->where('severity', '3')->count();
               
                array_push($temp2['low'], $low);
                array_push($temp2['medium'], $medium);
                array_push($temp2['high'], $high);
            }

            array_push($cityTypeWiseSnags, $temp2);
            
            $low = 0;
            $medium = 0;
            $high = 0;
            $low = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->where('status', 'latest')->where('Snag_Status', 'NOK')->where('severity', '1')->count();
            $medium = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->where('status', 'latest')->where('Snag_Status', 'NOK')->where('severity', '2')->count();
            $high = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->where('status', 'latest')->where('Snag_Status', 'NOK')->where('severity', '3')->count();
    
            array_push($tempSev['low'], $low);
            array_push($tempSev['medium'], $medium);
            array_push($tempSev['high'], $high);
        }
        
        $temp['city'] = 'Total';
        $temp['openSnags'] = array();
        $temp['closedSnags'] = array();
        $temp['total'] = array();
        
        $totalOpen = array();
        $totalClosed = array();
        $totalTotal = array();
        
        foreach($cityWiseOpenClosedSnags as $item){
            array_push($totalOpen, $item['openSnags']);
            array_push($totalClosed, $item['closedSnags']);
            array_push($totalTotal, $item['total']);
        }

        $temp['openSnags'] = $this->arraySum($totalOpen);
        $temp['closedSnags'] = $this->arraySum($totalClosed);
        $temp['total'] = $this->arraySum($totalTotal);
        array_push($cityWiseOpenClosedSnags, $temp);
        
        $temp2['city'] = 'Total';
        $temp2['low'] = array();
        $temp2['medium'] = array();
        $temp2['high'] = array();
        
        $totalLow = array();
        $totalMedium = array();
        $totalHigh = array();
        
        foreach($cityTypeWiseSnags as $item){
            array_push($totalLow, $item['low']);
            array_push($totalMedium, $item['medium']);
            array_push($totalHigh, $item['high']);
        }
        
        $temp2['low'] = $this->arraySum($totalLow);
        $temp2['medium'] = $this->arraySum($totalMedium);
        $temp2['high'] = $this->arraySum($totalHigh);
        array_push($cityTypeWiseSnags, $temp2);
        
        array_push($citySevWiseSnags, $tempSev);
        
        /*city wise open closed snags*/

        /* snag Aging */

        foreach($cities as $city){
            $temp = array();
            $temp['city'] = $city;
            $temp['low'] = array();
            $temp['medium'] = array();
            $temp['high'] = array();
            $results = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->where('Snag_Status', 'NOK')->where('closed_status', null)->where('visit', 'first')->select('created', 'severity')->get();

            for($i = 0; $i < count($days); $i++){
                $low = 0;
                $medium = 0;
                $high = 0;

                        Log::info('current checking day' . ' ' . $days[$i]);
                    foreach($results as $key=>$result){
                        
                        $now = strtotime(date("Y-m-d")); //this has to be changed to now
                        $your_date = strtotime($result->created);
                        $datediff = $now - $your_date;
                        $daysPending = round($datediff / (60 * 60 * 24));
                        if($i != count($days) - 1){
                            $condition = $days[$i] <= $daysPending && $daysPending < $days[$i + 1];
                        }else{
                            $condition = $daysPending > $days[$i];
                        }
                        
                        if($condition){
                            $sev= $result->severity;
                            Log::info('catch made');
                            if($sev != ""){
                                    if($sev == '1'){
                                        $low = $low + 1;
                                    }
                                    if($sev == '2'){
                                        $medium = $medium + 1;
                                    }if($sev == '3'){
                                        $high = $high + 1;
                                    }
                            }
                        }

                    }

                array_push($temp['low'], $low);
                array_push($temp['medium'], $medium);
                array_push($temp['high'], $high);
            }

            array_push($snagAging, $temp);
        }

            $temp['city'] = 'Total';
            $temp['low'] = array();
            $temp['medium'] = array();
            $temp['high'] = array();
            
            $totalLow = array();
            $totalMedium = array();
            $totalHigh = array();
            
            foreach($snagAging as $item){
                array_push($totalLow, $item['low']);
                array_push($totalMedium, $item['medium']);
                array_push($totalHigh, $item['high']);
            }
            
            $temp['low'] = $this->arraySum($totalLow);
            $temp['medium'] = $this->arraySum($totalMedium);
            $temp['high'] = $this->arraySum($totalHigh);
            array_push($snagAging, $temp);
           
        /* snag Aging */

        /* time to Resolve */

        foreach($cities as $city){
            $temp = array();
            $temp['city'] = $city;
            $temp['low'] = array();
            $temp['medium'] = array();
            $temp['high'] = array();
            
            $results = DB::table('audit_result')->where('customer_id', $sequence)->where('Snag_Status', 'NOK')->where('City', $city)->where('closed_status', 'yes')->select('created', 'severity', 'dateOfClosing')->get();

            for($i = 0; $i < count($days); $i++){
                $low = 0;
                $medium = 0;
                $high = 0;
                foreach($results as $key=>$result){
                    $sev= $result->severity;
                    if($sev != ""){
                        $dateNow = strtotime($result->created);
                        $dateOld = strtotime($result->dateOfClosing);
                        $datediff = $dateOld - $dateNow;
                        $daystoClose = round($datediff / (60 * 60 * 24));
                        if($i != count($days) - 1){
                            $condition = $days[$i] <= $daystoClose && $daystoClose < $days[$i + 1];
                        }else{
                            $condition = $daystoClose > $days[$i];
                        }
                        
                        Log::info($condition);

                        if($condition){
                            if($sev == '1'){
                                $low = $low + 1;
                            }
                            if($sev == '2'){
                                $medium = $medium + 1;
                            }
                            if($sev == '3'){
                                $high = $high + 1;
                            }
                        }
                    }
                }

                array_push($temp['low'], $low);
                array_push($temp['medium'], $medium);
                array_push($temp['high'], $high);
            }

            array_push($timeToResolve, $temp);

        }

            $temp['city'] = 'Total';
            $temp['low'] = array();
            $temp['medium'] = array();
            $temp['high'] = array();
            
            $totalLow = array();
            $totalMedium = array();
            $totalHigh = array();
            
            foreach($timeToResolve as $item){
                array_push($totalLow, $item['low']);
                array_push($totalMedium, $item['medium']);
                array_push($totalHigh, $item['high']);
            }
            
            $temp['low'] = $this->arraySum($totalLow);
            $temp['medium'] = $this->arraySum($totalMedium);
            $temp['high'] = $this->arraySum($totalHigh);
            array_push($timeToResolve, $temp);
        /* time to Resolve */


        $final['cities'] = $cities;
        $final['months'] = $months;
        $final['cityWiseOpenClosedSnags'] = $cityWiseOpenClosedSnags;
        $final['cityTypeWiseSnags'] = $cityTypeWiseSnags;
        $final['citySevWiseSnags'] = $citySevWiseSnags;
        $final['types'] = $types;
        $final['days'] = $days;
        $final['snagAging'] = $snagAging;
        $final['timeToResolve'] = $timeToResolve;
        

        return $final;
    }

    public function auditDashboardInit(Request $request){

        $stat = $this->auth_checker($request);
        if($stat == false){
            return "login";
        }

        $final = array();
        
        $sequence = 2;

        //$questions = DB::table('audit_result')->where('customer_id', 21)->where('Snag_Status', '<>', "")->get();
        $months = DB::table('audit_result')->distinct('dateEntered')->where('customer_id', $sequence)->where('Snag_Status', '<>', "")->orderBy('dateEntered', 'asc')->pluck('dateEntered');
        //$dates =["2022-02-10"];
        $tracks = DB::table('fiber')->where('user_id', $sequence)->select('data')->get();

        /* new deployment inspection data */

            $deploymentInspectionCitiesNew = DB::table('fiber')->where('user_id', $sequence)->distinct('city')->pluck('city');
            
            $deploymentInspectionDates = DB::table('fiber')->where('user_id', $sequence)->orderBy('dateEntered','asc')->pluck('dateEntered');

            $deploymentInspectionDatesUnique = array();

            foreach($deploymentInspectionDates as $item){
                if(!in_array(date('y-m-d',strtotime($item)), $deploymentInspectionDatesUnique) && $item != null){
                    
                    array_push($deploymentInspectionDatesUnique,date('y-m-d',strtotime($item)));
                }          
            }

            $deploymentInspectionMonths = array();

            foreach($deploymentInspectionDatesUnique as $item){
                if(!in_array(date('Y-m',strtotime($item)), $deploymentInspectionMonths)){
                    array_push($deploymentInspectionMonths,date('Y-m',strtotime($item)));
                } 
            }

            $deploymentInspectionNew = array();

            foreach($deploymentInspectionCitiesNew as $city){
                
                $temp['name'] = $city;
                $temp['data'] = array();
                $temp['type'] = 'column';
                
                foreach($deploymentInspectionMonths as $mon){
                    $list = explode("-", $mon);
                    $tracksNew = DB::table('fiber')->where('user_id', $sequence)->where('city', $city)->whereMonth('dateEntered', $list[1])->whereYear('dateEntered', $list[0])->select('data')->get();

                    $sum = 0;
                    foreach($tracksNew as $track){
                        $data = json_decode($track->data, true);
                            foreach($data['track'] as $tr){
                                for($i=0; $i < count($tr['data']); $i++){
                                    if($i != count($tr['data'])-1){
                                        $sum = $sum + $this->distance($tr['data'][$i]['lat'], $tr['data'][$i]['lng'], $tr['data'][$i+1]['lat'], $tr['data'][$i+1]['lng'], 'K');
                                    }
                                }
                            }
                    }

                    $sum = round($sum, 1);
                    array_push($temp['data'], $sum);
                }

                array_push($deploymentInspectionNew, $temp);
                
            }

            $temp = array();
            $temp['name'] = 'Total Acc';
            $temp['data'] = array();
            $temp['type'] = 'spline';

            $myarrays = array();
            foreach($deploymentInspectionNew as $ins){
                array_push($myarrays, $ins['data']);
            }

            $temporary = array();

            if(count($myarrays) > 0){
                $temporary = $this->arraySum($myarrays);
            }

            $sum = 0;
            foreach($temporary as $tompo){
                $sum = $sum + $tompo;
                $sum = round($sum, 1);
                array_push($temp['data'], $sum);
            }

            array_push($deploymentInspectionNew, $temp);

            $temp =array();
            $temp['name'] = 'Monthly Total';
            $temp['data'] = array();
            $temp['type'] = 'spline';

            foreach($temporary as $tompo){
                array_push($temp['data'], round($tompo, 1));
            }

            array_push($deploymentInspectionNew, $temp);


        /* new deployment inspection data */
        

        $numberOfSpotAudits = array();
        $numberOfRevisits = array();
        
        $deploymentInspectionCities = array();
        $deploymentInspection = array();
        
        foreach($tracks as $track){
            $data = json_decode($track->data, true);
            if(!in_array($data['city'], $deploymentInspectionCities)){
                array_push($deploymentInspectionCities, $data['city']);
            }
        }
        
        foreach($deploymentInspectionCities as $city){
            
            $sum = 0;
            foreach($tracks as $track){
                $data = json_decode($track->data, true);
                if($data['city'] == $city){
                    foreach($data['track'] as $tr){
                        for($i=0; $i < count($tr['data']); $i++){
                            if($i != count($tr['data'])-1){
                                $sum = $sum + $this->distance($tr['data'][$i]['lat'], $tr['data'][$i]['lng'], $tr['data'][$i+1]['lat'], $tr['data'][$i+1]['lng'], 'K');
                            }
                        }
                    }
                }
            }
            
            $sum = round($sum, 1);
            array_push($deploymentInspection, $sum);
            
        }
        
        
        $cities = DB::table('audit_result')->where('customer_id', $sequence)->where('Snag_Status', '<>', "")->distinct()->pluck('City');

        foreach($cities as $city){
            $temp = array();
            $temp['city'] = $city;
            $temp['Number Of Audits'] = array();
            $temp2['city'] = $city;
            $temp2['Number Of Revisits'] = array();
            foreach($months as $m){
                $numOfAudits = 0;
                $tempResults = array();
                $tempResults2 = array();
                $numOfRevisits = 0;
                $result = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->where('dateEntered', $m)->distinct()->pluck('identification');
                $old = DB::table('audit_result')->whereIn('identification', $result)->where('dateEntered', $m)->where('visit', 'revisit')->count();
                
                array_push($temp2['Number Of Revisits'], $old);
                 
                array_push($temp['Number Of Audits'], count($result));
            }
            
            array_push($numberOfSpotAudits, $temp);
            array_push($numberOfRevisits, $temp2);
            
        }
        
        Log::info($numberOfSpotAudits);
        Log::info($numberOfRevisits);

        $temp = array();
        $temp['city'] = 'Total';
        $temp['Number Of Audits'] = array();
        
        $temp2['city'] = 'Total';
        $temp2['Number Of Revisits'] = array();

        $myarrays = array();
        foreach($numberOfSpotAudits as $spotAudit){
            array_push($myarrays, $spotAudit['Number Of Audits']);
        }

        if(count($myarrays) > 0){
            $temp['Number Of Audits'] = $this->arraySum($myarrays);
        }
        
        $myarrays2 = array();
         foreach($numberOfRevisits as $revisit){
             array_push($myarrays, $revisit['Number Of Revisits']);
         }
         
          if(count($myarrays2) > 0){
             $temp2['Number Of Revisits'] = $this->arraySum($myarrays);
         }

         array_push($numberOfRevisits, $temp2);

        array_push($numberOfSpotAudits, $temp);

        $auditDataCity = array();

        foreach($cities as $city){

            $result = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->distinct()->pluck('identification');
            array_push($auditDataCity, count($result));

        }

        $final['auditDataCity'] = $auditDataCity;
        $final['auditCities'] = $cities;

        $final['months'] = $months;
        $final['spotAudits'] = $numberOfSpotAudits;
        $final['numberOfRevisits'] = $numberOfRevisits;
        $final['deploymentInspectionCities'] = $deploymentInspectionCities;
        $final['deploymentInspection'] = $deploymentInspection;

        $final['deploymentInspectionCitiesNew'] = $deploymentInspectionCitiesNew;
        $final['deploymentInspectionMonths'] = $deploymentInspectionMonths;
        $final['deploymenyInspectionNew'] = $deploymentInspectionNew;

        return $final;
    } 

    public function inspectionDashboardInit(Request $request){

        $stat = $this->auth_checker($request);
        if($stat == false){
            return "login";
        }

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

    public function snagReportingInit(Request $request){

        $stat = $this->auth_checker($request);
        if($stat == false){
            return "login";
        }

        $final = array();
        
        $sequence = 2;
        
        $exclude = ['City', 'Region', 'Area'];
       
        $categories = DB::table('audit_result')->where('customer_id', $sequence)->distinct()->pluck('form_name');
         
        $descriptions = DB::table('audit_result')->where('customer_id', $sequence)->whereNotIn('q_name', $exclude)->distinct()->pluck('q_name');
        
        $areas = DB::table('audit_result')->where('customer_id', $sequence)->distinct()->pluck('Area');
        
        $media = DB::table('audit_result')->where('customer_id', $sequence)->select('media')->distinct()->select('identification', 'name', 'media')->get();
        
        
        
        $final['categories'] = $categories;
        $final['descriptions'] = $descriptions;
        $final['areas'] = $areas;
        $final['media'] = $media;

        $response =$final;
        
        $tasksPositions = DB::table('tasks')->whereNotNull('location')->select('location')->get();
        
        $locations = array();
        
        foreach($tasksPositions as $position){
            if(!in_array($position->location, $locations)){
                array_push($locations, $position->location);
            }
        }
        
        $final1 = $response;
        $final1['taskPositions'] = $locations;

        return $final1;
    }
    
    function snagReportingGetSnags(Request $request){
        $final = array();
        
        $snags = array();
        
        $sequence = 2;
        $cities = $request->input('cities');
        $severities = $request->input('severities');
        $categories = $request->input('categories');
        $snags = $request->input('snags');
        $areas = $request->input('areas');
        $statuses = $request->input('statuses');
        $actions = $request->input('actions');

        Log::info($actions);
        
        $questions = DB::table('audit_result')->where('customer_id', $sequence)->where('Snag_Status', 'NOK')->whereIn('form_name', $categories)->whereIn('q_name', $snags)->whereIn('severity', $severities)->whereIn('City', $cities)->whereIn('actionStatus', $actions)->select('id', 'Snag_Status', 'Remarks', 'form_name','q_name', 'q_id', 'identification', 'created', 'dateOfClosing', 'severity', 'closed_status', 'City', 'Region', 'Area', 'position', 'actionStatus', 'task_assignment_status')->paginate(1000);
       
        $final['snags'] = $questions;
        
        return $final;
        
    }
    

    public function projectDashboardInit(Request $request){

        $stat = $this->auth_checker($request);
        if($stat == false){
            return "login";
        }

        $response = Http::post('https://joynaudits.com/api/projectDashboardInit');

        return $response->json();
    }
    
    public function risksInit(Request $request){

        $stat = $this->auth_checker($request);
        if($stat == false){
            return "login";
        }
        
        $final = array();
        
        $risks = DB::table('2_risk_table')->where('chronology', 'latest')->get();
        
        $final['risks'] = $risks;
        
        return $final;
    }
    
    public function issuesInit(Request $request){

        $stat = $this->auth_checker($request);
        if($stat == false){
            return "login";
        }
        
        $final = array();
        
        $issues = DB::table('2_issues_table')->where('chronology', 'latest')->get();
        
        $final['issues'] = $issues;
        
        return $final;
    }
    
    public function markerClicked(Request $request){

        if($request->input('position')){
            $position = $request->input('position');
            $position = json_encode($position);
        }else{
            $position = '{"lat":"31.41","lng":"73.11"}';
        }

        Log::info($position);
        
        $final = array();
        
        $sequence = 2;
        
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
                $questions = DB::table('audit_result')->where('customer_id', $sequence)->where('dateEntered', $date)->where('position', $position)->get();
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
    }
    
    public function getPlannedSites(Request $request){
        
        $final = array();
        $sequence = 2;

        $city = $request->input('city');

        $sites = DB::table('planned_sites')->where('city', $city)->select('latitude', 'longitude')->get();

        $final['sites'] = $sites;

        return $final;
    }
    
    public function getPotentialSites(Request $request){
        
        $final = array();

        $region = $request->input('region');

        $sites = DB::table('potential_sites')->where('Region', $region)->select('Latitude', 'Longitude')->get();

        $final['sites'] = $sites;

        return $final;
    }
    
    public function getOtdrResults(Request $request){
        
        $final = array();

        $results = DB::table('otdr_results')->get();

        $final['results'] = $results;

        return $final;
        
    }
    
    public function getFiberCity(Request $request){
        
        $city = 'Gujranwala';
        
        $response = Http::post('https://joynaudits.com/api/getFiberCity', [
            'city' => $city,
        ]);

        return $response->json();
    }


    public function overviewDashboardInit(Request $request){

        $stat = $this->auth_checker($request);
        if($stat == false){
            return "login";
        }
        
        $final = array();
        $sequence = 2;
        $allData = DB::table('2_scope_nt')->where('chronology', 'latest')->get();
        $operators = DB::table('2_scope_nt')->select('Operator')->where('chronology', 'latest')->distinct()->pluck('Operator');
        $Areas = DB::table('2_scope_nt')->select('Area')->where('chronology', 'latest')->distinct()->pluck('Area');

        $Operators = ['Total'];

        foreach($operators as $op){
            array_push($Operators, $op);
        }

        $barChartParameters = ['Planned', 'Build', 'Accepted'];
        $cityWiseChartData = array();
        $totalBarChartData = array();
        $totalPieChartData = array();
        $totalSites = 0;
        
        foreach($barChartParameters as $bar){
            
            $temp = array();
            $temp['name'] = $bar;
            $temp['data'] = array();

            foreach($Operators as $op){
                $sum = 0;
                if($op == 'Total'){
                    if($bar == 'Planned'){
                        $sum = DB::table('2_scope_nt')->where('chronology', 'latest')->sum('Planned Length');
                    }else if($bar == 'Build'){
                        $sum = DB::table('2_scope_nt')->where('chronology', 'latest')->sum('Build (km)');
                    }else{
                        $sum = DB::table('2_scope_nt')->where('chronology', 'latest')->sum('Accepted (km)');
                    }
                }else{
                    if($bar == 'Planned'){
                        $sum = DB::table('2_scope_nt')->where('Operator', $op)->where('chronology', 'latest')->sum('Planned Length');
                    }else if($bar == 'Build'){
                        $sum = DB::table('2_scope_nt')->where('Operator', $op)->where('chronology', 'latest')->sum('Build (km)');
                    }else{
                        $sum = DB::table('2_scope_nt')->where('Operator', $op)->where('chronology', 'latest')->sum('Accepted (km)');
                    }
                }
                array_push($temp['data'], (int)$sum);
            }

            array_push($totalBarChartData, $temp);

        }

        foreach($Operators as $op1){

            if($op1 != 'Total'){
                $temp = array();
                $temp['name'] = $op1;
                $temp['y'] = 0;

                $temp['y'] = DB::table('2_scope_nt')->where('Operator', $op1)->where('chronology', 'latest')->sum('Integrated Site');

                $temp['y'] = (int)$temp['y'];
                array_push($totalPieChartData, $temp);
            }
        }

        foreach($totalPieChartData as $tpcd){
            $totalSites = $totalSites + $tpcd['y'];
        }


        foreach($Areas as $area){

            $temporary = array();
            $temporary['city'] = $area;
            $temporary['barChartData'] = array();
            $temporary['pieChartData'] = array();
            $temporary['totalSites'] = 0;

            foreach($barChartParameters as $bar){
            
                $temp = array();
                $temp['name'] = $bar;
                $temp['data'] = array();
    
                foreach($Operators as $op){
                    $sum = 0;
                    if($op == 'Total'){
                        if($bar == 'Planned'){
                            $sum = DB::table('2_scope_nt')->where('Area', $area)->where('chronology', 'latest')->sum('Planned Length');
                        }else if($bar == 'Build'){
                            $sum = DB::table('2_scope_nt')->where('Area', $area)->where('chronology', 'latest')->sum('Build (km)');
                        }else{
                            $sum = DB::table('2_scope_nt')->where('Area', $area)->where('chronology', 'latest')->sum('Accepted (km)');
                        }
                    }else{
                        if($bar == 'Planned'){
                            $sum = DB::table('2_scope_nt')->where('Area', $area)->where('Operator', $op)->where('chronology', 'latest')->sum('Planned Length');
                        }else if($bar == 'Build'){
                            $sum = DB::table('2_scope_nt')->where('Area', $area)->where('Operator', $op)->where('chronology', 'latest')->sum('Build (km)');
                        }else{
                            $sum = DB::table('2_scope_nt')->where('Area', $area)->where('Operator', $op)->where('chronology', 'latest')->sum('Accepted (km)');
                        }
                    }
                    array_push($temp['data'], (int)$sum);
                }

                array_push($temporary['barChartData'], $temp);
    
            }

            foreach($Operators as $op1){

                if($op1 != 'Total'){
                    $temp = array();
                    $temp['name'] = $op1;
                    $temp['y'] = 0;

                    $temp['y'] = DB::table('2_scope_nt')->where('Operator', $op1)->where('Area', $area)->where('chronology', 'latest')->sum('Integrated Site');

                    $temp['y'] = (int)$temp['y'];

                    array_push($temporary['pieChartData'], $temp);
                }
            }

            foreach($temporary['pieChartData'] as $tompo){
                $temporary['totalSites'] = $temporary['totalSites'] + $tompo['y'];
            }
            

            array_push($cityWiseChartData, $temporary);

        }

        /*previously all info was from scope_nt now we have to repeat the audit dashboard functionality */

        $months = DB::table('audit_result')->distinct('dateEntered')->where('customer_id', $sequence)->where('Snag_Status', '<>', "")->orderBy('dateEntered', 'asc')->pluck('dateEntered');

        $tracks = DB::table('fiber')->where('user_id', $sequence)->select('data')->get();
        $deploymentInspectionCities = array();



        $numberOfSpotAudits = array();
        $numberOfRevisits = array();

        $deploymentInspection = array();

        foreach($tracks as $track){
            $data = json_decode($track->data, true);
            if(!in_array($data['city'], $deploymentInspectionCities)){
                array_push($deploymentInspectionCities, $data['city']);
            }
        }

        foreach($deploymentInspectionCities as $city){
            
            $sum = 0;
            foreach($tracks as $track){
                $data = json_decode($track->data, true);
                if($data['city'] == $city){
                    foreach($data['track'] as $tr){
                        for($i=0; $i < count($tr['data']); $i++){
                            if($i != count($tr['data'])-1){
                                $sum = $sum + $this->distance($tr['data'][$i]['lat'], $tr['data'][$i]['lng'], $tr['data'][$i+1]['lat'], $tr['data'][$i+1]['lng'], 'K');
                            }
                        }
                    }
                }
            }
            
            $sum = round($sum, 1);
            array_push($deploymentInspection, $sum);
            
        }


        $cities = DB::table('audit_result')->where('customer_id', $sequence)->where('Snag_Status', '<>', "")->distinct()->pluck('City');

        foreach($cities as $city){
            $temp = array();
            $temp['city'] = $city;
            $temp['Number Of Audits'] = array();
            $temp2['city'] = $city;
            $temp2['Number Of Revisits'] = array();
            foreach($months as $m){
                $numOfAudits = 0;
                $tempResults = array();
                $tempResults2 = array();
                $numOfRevisits = 0;
                $result = DB::table('audit_result')->where('customer_id', $sequence)->where('City', $city)->where('dateEntered', $m)->distinct()->pluck('identification');
                $old = DB::table('audit_result')->whereIn('identification', $result)->where('dateEntered', $m)->where('visit', 'revisit')->count();
                
                array_push($temp2['Number Of Revisits'], $old);
                
                array_push($temp['Number Of Audits'], count($result));
            }
            
            array_push($numberOfSpotAudits, $temp);
            array_push($numberOfRevisits, $temp2);
            
        }

        Log::info($numberOfSpotAudits);
        Log::info($numberOfRevisits);

        $temp = array();
        $temp['city'] = 'Total';
        $temp['Number Of Audits'] = array();

        $temp2['city'] = 'Total';
        $temp2['Number Of Revisits'] = array();

        $myarrays = array();
        foreach($numberOfSpotAudits as $spotAudit){
            array_push($myarrays, $spotAudit['Number Of Audits']);
        }

        if(count($myarrays) > 0){
            $temp['Number Of Audits'] = $this->arraySum($myarrays);
        }

        $myarrays2 = array();
        foreach($numberOfRevisits as $revisit){
            array_push($myarrays, $revisit['Number Of Revisits']);
        }
        
        if(count($myarrays2) > 0){
            $temp2['Number Of Revisits'] = $this->arraySum($myarrays);
        }

        array_push($numberOfRevisits, $temp2);

        array_push($numberOfSpotAudits, $temp);

        $final['months'] = $months;
        $final['spotAudits'] = $numberOfSpotAudits;
        $final['numberOfRevisits'] = $numberOfRevisits;
        $final['deploymentInspectionCities'] = $deploymentInspectionCities;
        $final['deploymentInspection'] = $deploymentInspection;

        /**/

        $final['totalBarChartData'] = $totalBarChartData;
        $final['totalPieChartData'] = $totalPieChartData;
        $final['totalSites'] = $totalSites;
        $final['cityWiseChartData'] = $cityWiseChartData;
        $final['allData'] = $allData;
        $final['Operators'] = $Operators;

        return $final;
        
    }

    /* credentials reset */

    public function resetCredentialsInit(Request $request){

        $stat = $this->auth_checker($request);
        if($stat == false){
            return "login";
        }

        $final = array();

        $id = $request->header('X-User-ID');

        $user = DB::table('users')->where('id', $id)->select('email', 'username')->get();

        $final['username'] = $user[0]->username;
        $final['email'] = $user[0]->email;

        return $final;

    }

    public function resetCredentials(Request $request){

        $final = array();

        $username = $request->input('username');
        $email = $request->input('email');
        $existingPassword = $request->input('existingPassword');
        $newPassword = $request->input('newPassword');
        $id = $request->input('id');


        $db_password = DB::table('users')->where('id',$id)->pluck('password')[0];

        if (Hash::check($existingPassword, $db_password)){

            $dynamic_data["password"] = Hash::make($newPassword);
            $dynamic_data["username"] = $username;
            $dynamic_data["email"] = $email;

            DB::table('users')->where('id',$id)->update($dynamic_data);
            return 'Credentials Updated';

        }else{
            return abort(500, 'incorrect existing password');
        }

        
    }
    /* credentials reset */

    public function sendMail(Request $request){

        $users = ['jaffermansoor18.jm@gmail.com'];
        $data = ['email' => 'jaffer@gmail.com', 'password' => 'diabolical'];

        Mail::send('memberCreateMail', $data, function($messages) use ($users){
            foreach($users as $user){
                $messages->to($user);
                $messages->subject('memberCreate');
            }
        });

        return 'sent';

    }
    
    public function sendMail2(Request $request){

        $users = ['salmanmansoor04@gmail.com'];
        $data = ['task' => 'task1', 'startDate' => '---', 'dueDate' => '---'];

        Mail::send('taskAssignedMail', $data, function($messages) use ($users){
            foreach($users as $user){
                $messages->to($user);
                $messages->subject('Task Assigned');
            }
        });

        return 'sent2';

    }

    /* password reset functions */

        public function enterVerificationCode(Request $request){
            $final = array();

            $email = $request->input('email');

            $checker = DB::table('users')->Where('email',$email)->pluck('id');

            if(count($checker) > 0){
                $fourRandomDigit = mt_rand(1000,9999);
                $users = [$email];
                $data = ['code' => $fourRandomDigit];

                DB::table('users')
                ->where('id', $checker[0])
                ->update(['verificationCode' => $fourRandomDigit]);

                Mail::send('mail', $data, function($messages) use ($users){
                    foreach($users as $user){
                        $messages->to($user);
                        $messages->subject('Verification Code');
                    }
                });

                return 'ok';

            }else{
                return abort(500, 'This Email does not exist in our Records');
            }

            return $final;
        }

        public function verifyVerificationCode(Request $request){

            $final = array();

            $verificationCode = $request->input('verificationCode');
            $email = $request->input('email');

            $currentVerificationCode = DB::table('users')->where('email', $email)->pluck('verificationCode')[0];

            if($verificationCode != $currentVerificationCode){
                return abort(500, 'Invalid Verification Code');
            }else{
                return 'ok';
            }
        }

        public function resetPassword(Request $request){

            $final = array();

            $email = $request->input('email');
            $password = $request->input('password');

            $dynamic_data["password"] = Hash::make($password);

            DB::table('users')->where('email',$email)->update($dynamic_data);

            return 'ok';
        }
    /* password reset functions */
    
    /*checker seperate */
    
    public function checkerSeperate(Request $request){
        $stat = $this->auth_checker($request);
        if($stat == false){
            return "login";
        }
        return "ok";
    }
    
    /*checker seperate */
    

    /* helper functions */

    function arraySum($a){
        
        $num = count($a[0]);
        $final = array();
        for($i = 0; $i < $num; $i++){
            $sum = 0;
            foreach($a as $aa){
                $sum = $sum + $aa[$i];
            }
            array_push($final, $sum);
        }

        return $final;
    }
    
    private function date_sort($a, $b) {
        return strtotime($a) - strtotime($b);
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