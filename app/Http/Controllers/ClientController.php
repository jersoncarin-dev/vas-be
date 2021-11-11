<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function medicines()
    {
        return view('client.home',[
            'products' => Medicine::latest()->paginate()
        ]);
    }

    public function appoint(Request $request)
    {
        $date = $request->appointment_date ?? date('Y-m-d');

        Appointment::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'appointment_date' => $date,
            'purpose' => $request->purpose,
            'is_approved' => false
        ]);

        return redirect()
            ->back()
            ->withMessage("New appointment has been appointed successfully.");
    }

    public function appointments()
    {
        $appointments = Appointment::where('user_id',Auth::id())
            ->latest()
            ->paginate();

        return view('client.appointments',[
            'appointments' => $appointments
        ]);
    }

    public function cancelAppointments(Request $request)
    {
        $appointment = Appointment::find($request->id);

        if(!$appointment) {
            return abort(404);
        }

        if($appointment->is_approved) {
            return abort(403);
        }

        $appointment->delete();

        return redirect()->back()->withMessage("Appointment successfully cancelled.");
    }
}
