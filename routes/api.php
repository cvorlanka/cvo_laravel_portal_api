<?php

use App\Http\Controllers\FrameSearchController;
use Illuminate\Support\Facades\Route;

// All routes here are prefixed with /api (see bootstrap/app.php).

Route::prefix('frames')->controller(FrameSearchController::class)->group(function () {
    Route::get('territories', 'userTerritories'); // ?email=
    Route::get('search', 'frameSearch');          // ?territory_id=&search=&period=
});
