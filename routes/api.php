<?php

use Illuminate\Http\Request;


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

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});
Route::post('login','ApiLoginController@login');

Route::middleware('auth:api')->group(function(){

//    All user info routes
    Route::get('getUserData','UsersController@getData');
    Route::get('getUserDataList','UsersController@getAll');

//    Stored procedures
    Route::post('getDataStoredPorcedur','StoredPorcedurController@getData');
    Route::post('getListStoredPorcedur','StoredPorcedurController@getList');
    Route::post('addDataStoredPorcedur','StoredPorcedurController@addData');
    Route::post('editDataStoredPorcedur','StoredPorcedurController@editData');
    Route::post('deleteDataStoredPorcedur','StoredPorcedurController@deleteData');
    Route::post('schemeDataStoredPorcedur','StoredPorcedurController@getScheme');
    Route::post('enumListDataStoredPorcedur','StoredPorcedurController@getEnumList');

//    Stored procedures with multi fields and 'foreach'
    Route::post('addListSelectDataStoredPorcedur','StoredPorcedurController@addSelectData');
    Route::post('editListSelectDataStoredPorcedur','StoredPorcedurController@editSelectData');
    Route::post('deleteListSelectDataStoredPorcedur','StoredPorcedurController@deleteSelectData');
    Route::post('getListSelectDataStoredPorcedur','StoredPorcedurController@getListSelect');

//    Calculator procedurs
    Route::post('CalculationForm','CalculatorController@getFieldScheme');
    Route::post('Calculations','CalculatorController@getCalculations');
    Route::post('singleCalculation','CalculatorController@singleCalculation');


});

