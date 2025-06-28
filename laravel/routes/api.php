use App\Http\Controllers\AnimeController;

Route::prefix('anime')->group(function () {
Route::get('/', [AnimeController::class, 'index']);
Route::get('/{id}', [AnimeController::class, 'show']);
Route::post('/scrape', [AnimeController::class, 'scrape']);
Route::get('/stats', [AnimeController::class, 'stats']);
Route::get('/platforms/list', [AnimeController::class, 'platforms']);
});
