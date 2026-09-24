<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('attendance.index', [
            'schedules' => Schedule::query()
                ->with('course')
                ->active()
                ->whereHas('course', fn ($query) => $query->active())
                ->orderBy('schedule_date')
                ->orderBy('start_time')
                ->get(),
        ]);
    }
}
