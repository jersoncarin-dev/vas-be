<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Pusher\PushNotifications\PushNotifications;

class AdminStaffController extends Controller
{
    public function medicines()
    {
        return view('staff.home',[
            'medicines' => Medicine::latest()
                ->paginate()
        ]);
    }

    public function medicineDelete(Request $request)
    {
        if($medicine = Medicine::find($request->id)) {
            $medicine->delete();

            return redirect()->back()->withMessage('Medicine deleted successfully');
        }

        return abort(404);
    }

    public function medicineShow(Request $request)
    {
        if($medicine = Medicine::find($request->id)) {
            return response()->json($medicine);
        }

        return abort(404);
    }

    public function medicineEdit(Request $request)
    {
        $file = $request->file('thumbnail');
        $thumbnail = null;

        if(!is_null($file)) {
            $thumbnail = url('dist/img/'.$file->getClientOriginalName());
            $file->move(public_path('dist/img'),$file->getClientOriginalName());
        }

        if($medicine = Medicine::find($request->id)) {
            $fields = [
                'name' => $request->name,
                'description' => $request->description,
                'is_available' => $request->available == 'on'
            ];

            if(!is_null($thumbnail)) {
                $fields['thumbnail'] = $thumbnail;
            }

            $medicine->update($fields);

            return redirect()->back()->withMessage("Medicine successfully updated");
        }

        return abort(404);
    }

    public function medicineAdd(Request $request)
    {
        $file = $request->file('thumbnail');

        $thumbnail = url('dist/img/'.$file->getClientOriginalName());
        $file->move(public_path('dist/img'),$file->getClientOriginalName());

        Medicine::create([
            'name' => $request->name,
            'description' => $request->description,
            'thumbnail' => $thumbnail,
            'is_available' => $request->available == 'on'
        ]);

        return redirect()->back()->withMessage("Medicine added successfully");
    }

    public function appointments()
    {
        $calendars = Appointment::with('user')
            ->where('is_approved',false)
            ->latest()
            ->get()
            ->map(function($appointment) {
                return [
                    'id' => $appointment->id,
                    'calendarId' => $appointment->id,
                    'title' => $appointment->user->name.'('.$appointment->name.')',
                    'category' => 'allday',
                    'dueDateClass' => '',
                    'start' => $appointment->appointment_date,
                    'end' => $appointment->appointment_date,
                ];
            });

        return view('staff.appointments',[
            'calendars' => $calendars
        ]);
    }

    public function rejectOrApprove(Request $request)
    {
        if(!($appointment = Appointment::find($request->id))) {
            return abort(404);
        }

        $approve = false;

        if($request->has('reject')) {
            $appointment->delete();
        } else if($request->has('approve')) {
            $appointment->update([
                'is_approved' => true
            ]);

            $approve = true;
        } else {
            return abort(500);
        }

        return redirect()
            ->back()
            ->withMessage('Appointment has been ' . ($approve ? 'approved' : 'rejected') . 'successfully.');
    }

    public function reminders()
    {
        $clients = User::where('role','CLIENT')->get();

        return view('staff.reminders',compact('clients'));
    }

    public function sendReminder(Request $request,PushNotifications $pn)
    {
        $pn->publishToUsers(
            array("user_id_".$request->id),
            array(
              "web" => array(
                "notification" => array(
                  "title" => $request->title ?? 'No title',
                  "body" => $request->message ?? 'No body'
                )
              )
          ));

        return redirect()->back()->withMessage("Reminder send successfully");
    }

    public function users()
    {
        return view('staff.users',[
            'users' => User::latest()
                ->when(Auth::user()->role === 'STAFF',function($query) {
                    $query->where('role','CLIENT');
                })
                ->paginate()
        ]);
    }

    public function userDelete(Request $request)
    {
        if($user = User::find($request->id)) {
            $user->delete();

            return redirect()->back()->withMessage('User deleted successfully');
        }

        return abort(404);
    }

    public function userShow(Request $request)
    {
        if($user = User::with('detail')->find($request->id)) {
            return response()->json($user);
        }

        return abort(404);
    }

    public function userAdd(Request $request)
    {
        $file = $request->file('avatar');
        $avatar = url('dist/img/avatar.png');

        if(!is_null($file)) {
            $avatar = url('dist/img/'.$file->getClientOriginalName());
            $file->move(public_path('dist/img'),$file->getClientOriginalName());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'avatar' => $avatar
        ]);

        $user->detail()->create([
            'pet_name' => $request->pet_name,
            'pet_category' => $request->pet_category,
            'address' => $request->address,
            'contact_number' => $request->contact_number
        ]);

        return redirect()->back()->withMessage("User added successfully");
    }

    public function userEdit(Request $request)
    {
        $file = $request->file('avatar');
        $avatar = url('dist/img/avatar.png');

        if(!is_null($file)) {
            $avatar = url('dist/img/'.$file->getClientOriginalName());
            $file->move(public_path('dist/img'),$file->getClientOriginalName());
        }

        if(!($user = User::find($request->id))) {
            return abort(404);
        }

        $fields = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if($request->has('password')) {
            $fields['password'] = Hash::make($request->password);
        }

        if(!is_null($file)) {
            $fields['avatar'] = $avatar;
        }

        $user->update($fields);

        $user->detail()->create([
            'pet_name' => $request->pet_name,
            'pet_category' => $request->pet_category,
            'address' => $request->address,
            'contact_number' => $request->contact_number
        ]);

        return redirect()->back()->withMessage("User edited successfully");
    }
}
