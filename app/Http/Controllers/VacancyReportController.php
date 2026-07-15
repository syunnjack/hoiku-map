<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Support\ContentModeration;
use Illuminate\Http\Request;

class VacancyReportController extends Controller
{
    public function store(Request $request, Venue $venue)
    {
        if (! empty($request->input('website'))) {
            return back()->with('success', '投稿を受け付けました。');
        }

        $validated = $request->validate([
            'age_group' => 'nullable|string|max:20',
            'status' => 'required|in:あり,なし,要問合せ',
            'comment' => 'nullable|string|max:1000',
            'nickname' => 'nullable|string|max:30',
        ]);

        if (! empty($validated['comment']) && ContentModeration::containsNgWord($validated['comment'])) {
            return back()->withErrors(['comment' => '投稿内容に使用できない文字列が含まれています。'])->withInput();
        }

        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("vacancy-report:{$venue->id}:{$ipHash}", 30)) {
            return back()->withErrors(['status' => '投稿間隔が短すぎます。しばらく待ってから再度お試しください。'])->withInput();
        }

        $venue->vacancyReports()->create([
            'age_group' => $validated['age_group'] ?? null,
            'status' => $validated['status'],
            'comment' => $validated['comment'] ?? null,
            'nickname' => ($validated['nickname'] ?? '') !== '' ? $validated['nickname'] : '匿名',
            'ip_hash' => $ipHash,
        ]);

        return back()->with('success', '空き状況の口コミを投稿しました。ありがとうございます。');
    }
}
