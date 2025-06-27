<?php
// Example: app/Http/Controllers/ClientDashboardController.php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Plot;
use App\Models\CemeteryBooking;


class CemeteryDocumentsController extends Controller
{
    public function index()
    {
        return view('cemetery.documents.index');
    }
}
