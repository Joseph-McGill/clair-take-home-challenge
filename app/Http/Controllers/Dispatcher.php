<?php

namespace App\Http\Controllers;

use App\Jobs\SyncPayItems;
use App\Models\Business;

class Dispatcher extends Controller
{
  public function dispatchJobs()
  {
    echo "Queuing jobs";
    Business::where('enabled', true)
      ->get()
      ->map(function (Business $business) {
        SyncPayItems::dispatch($business);
      });
  }
}