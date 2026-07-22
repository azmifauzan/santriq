<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $today = now()->format('Y-m-d');

        $totalStudents = Student::where('status', 'active')->count();
        $todayAttendanceCount = Attendance::where('date', $today)->where('status', 'hadir')->count();
        $unpaidInvoicesCount = Invoice::where('status', 'unpaid')->count();
        $pendingLeavesCount = LeaveRequest::where('status', 'pending')->count();

        $recentAttendances = Attendance::with('student')
            ->where('date', $today)
            ->latest('updated_at')
            ->take(5)
            ->get();

        $recentAchievements = Achievement::with('student')
            ->latest('achieved_at')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_students' => $totalStudents,
                'today_attendance_count' => $todayAttendanceCount,
                'unpaid_invoices_count' => $unpaidInvoicesCount,
                'pending_leaves_count' => $pendingLeavesCount,
            ],
            'recent_attendances' => $recentAttendances,
            'recent_achievements' => $recentAchievements,
        ]);
    }
}
