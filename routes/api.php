<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\facebook;

use App\Http\Controllers\TaskmanagementController;

use App\Http\Controllers\AppController;

use App\Http\Controllers\auditFormsController;

use App\Http\Controllers\AuditController;

use App\Http\Controllers\CustomerFiberController;

use App\Http\Controllers\AuditorsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/reactTest', [facebook::class, 'test']);

Route::post('/snagDashboardInit', [facebook::class, 'snagDashboardInit']);

Route::post('/auditDashboardInit', [facebook::class, 'auditDashboardInit']);

Route::post('/inspectionDashboardInit', [facebook::class, 'inspectionDashboardInit']);

Route::post('/snagReportingInit', [facebook::class, 'snagReportingInit']);

Route::post('/snagReportingGetSnags', [facebook::class, 'snagReportingGetSnags']);

Route::post('/snagReportingGetMembers', [facebook::class, 'snagReportingGetMembers']);

Route::post('/projectDashboardInit', [facebook::class, 'projectDashboardInit']);

Route::post('/risksInit', [facebook::class, 'risksInit']);

Route::post('/issuesInit', [facebook::class, 'issuesInit']);

Route::post('/login', [facebook::class, 'login']);

Route::post('/register', [facebook::class, 'register']);

Route::post('/refresh/token', [facebook::class, 'token_exchange']);

Route::post('/dummy_test', [facebook::class, 'dummy_test']);

Route::post('/markerClicked', [facebook::class, 'markerClicked']);

Route::post('/overviewDashboardInit', [facebook::class, 'overviewDashboardInit']);

Route::post('/getTimeNow', [facebook::class, 'getTimeNow']);

Route::post('/getPlannedSites', [facebook::class, 'getPlannedSites']);

Route::post('/getPotentialSites', [facebook::class, 'getPotentialSites']);

Route::post('/getOtdrResults', [facebook::class, 'getOtdrResults']);

Route::post('/getFiberCity', [facebook::class, 'getFiberCity']);

/* sendMail */
Route::get('/sendMail', [facebook::class, 'sendMail']);
Route::get('/sendMail2', [facebook::class, 'sendMail2']);
/* sendMail */

/* Password Reset Route */
Route::post('enterVerificationCode', [facebook::class, 'enterVerificationCode']);

Route::post('verifyVerificationCode', [facebook::class, 'verifyVerificationCode']);

Route::post('resetPassword', [facebook::class, 'resetPassword']);
/* Password Reset Route */

/*credentials reset*/

Route::post('/resetCredentialsInit', [facebook::class, 'resetCredentialsInit']);

Route::post('/resetCredentials', [facebook::class, 'resetCredentials']);

/*credential reset*/

/*checker seperate */
Route::post('/checkerSeperate' , [facebook::class, 'checkerSeperate']);
/*checker seperate */

/* Task Management Routes */
Route::post('/getMembers', [TaskmanagementController:: class, 'getMembers']);
Route::post('/addMember', [TaskmanagementController:: class, 'addMember']);
Route::post('/addFunction', [TaskmanagementController:: class, 'addFunction']);

Route::post('/getTasks', [TaskmanagementController:: class, 'getTasks']);
Route::post('/addTask', [TaskmanagementController:: class, 'addTasks']);
Route::post('/assignMembers', [TaskmanagementController:: class, 'assignMembers']);

Route::post('/createTaskSnag', [TaskmanagementController:: class, 'createTaskSnag']);

Route::post('/getSnags', [TaskmanagementController:: class, 'getSnags']);

Route::post('/taskDelete', [TaskmanagementController:: class, 'taskDelete']);

Route::post('/memberDelete', [TaskmanagementController:: class, 'memberDelete']);
/* Task Management Routes */

/* Task App Routes */

Route::post('/appLogin', [AppController:: class, 'appLogin']);

Route::post('/getTasksApp', [AppController:: class, 'getTasksApp']);

Route::post('/getSingleTaskApp', [AppController:: class, 'getSingleTaskApp']);

Route::post('/getSnagsApp', [AppController:: class, 'getSnagsApp']);

Route::post('/taskImagesApp', [AppController:: class, 'taskImagesApp']);

Route::post('/taskCompleteApp', [AppController:: class, 'taskCompleteApp']);

Route::post('/taskAcceptanceStatusApp', [AppController:: class, 'taskAcceptanceStatusApp']);

/* Task App Routes */


/* Audit Form Routes */

Route::post('/auditInit', [auditFormsController::class, 'init']);

/* Audit Form Routes */

/* Audit App Routes */
Route::post('/auditlogin', [AuditController::class, 'auditlogin']);
Route::get('/getauditfields',[AuditController::class,'getauditfields']);
Route::post('/getauditsites',[AuditController::class,'getauditsites']);
Route::post('/uploadauditform',[AuditController::class,'uploadauditform']);
Route::post('/uploadauditmedia',[AuditController::class,'uploadauditmedia']);
Route::post('/auditsnags',[AuditController::class,'auditsnags']);
Route::post('/uploadsnagmedia',[AuditController::class,'uploadsnagmedia']);
Route::post('/uploadsnagform',[AuditController::class,'uploadsnagform']);

Route::get('/auditsnagsget',[AuditController::class,'auditsnagsget']);
Route::get('/visitrevisit',[AuditController::class,'visitrevisit']);
Route::post('/auditdatapopulation',[AuditController::class,'auditdatapopulation']);
Route::get('/getaudittracks',[AuditController::class,'getaudittracks']);
/* Audit App Routes */


/* Fiber App Routes */
Route::post('/fiberlogin', [CustomerFiberController::class, 'fiberlogin']);
Route::post('/trackdata', [CustomerFiberController::class, 'trackdata']);
Route::post('/fiber/trackmedia', [CustomerFiberController::class, 'trackmedia']);
Route::post('/customer/fiber', [CustomerFiberController::class, 'fiber']);
Route::post('/customer/fiber/delete', [CustomerFiberController::class, 'fiberdelete']);
/* Fiber App Routes */


/* Create Auditor */
Route::post('/addAuditor', [AuditorsController::class, 'addAuditor']);
/* Create Auditor */