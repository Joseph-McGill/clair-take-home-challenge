<?php

use App\Http\Controllers\Dispatcher;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return view('welcome');
});

// Dispatch Controller
Route::get('/dispatch', [Dispatcher::class, 'dispatchJobs']);