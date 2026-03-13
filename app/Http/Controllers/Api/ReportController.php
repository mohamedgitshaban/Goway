<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'report_text' => 'required|string',
        ]);

        $user = $request->user();

        $report = Report::create([
            'user_id' => $user->id,
            'report_text' => $data['report_text'],
        ]);

        // send email to admins and sender
        $admins = User::whereIn('usertype', [User::ROLE_ADMIN])->get();
        foreach ($admins as $admin) {
            Mail::raw("New report from {$user->name}: {$data['report_text']}", function ($m) use ($admin) {
                $m->to($admin->email)->subject('New user report');
            });
        }

        Mail::raw("We received your report: {$data['report_text']}", function ($m) use ($user) {
            if ($user->email) $m->to($user->email)->subject('Report received');
        });

        return response()->json($report, 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (! in_array($user->usertype, [User::ROLE_ADMIN], true)) {
            return response()->json(['message' => 'غير متاح لهذا المستخدم'], 403);
        }

        return response()->json(Report::with('user')->paginate(25));
    }
}
