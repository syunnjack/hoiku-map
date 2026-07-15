<?php

namespace App\Console\Commands;

use App\Models\Favorite;
use App\Models\VacancyReport;
use App\Support\LineMessaging;
use Illuminate\Console\Command;

class CheckVacancyWatches extends Command
{
    protected $signature = 'vacancy:check-watches';

    protected $description = 'ウォッチ登録された園に「空きあり」の口コミが新しく投稿されていないか確認し、LINEで通知する';

    public function handle(): int
    {
        $favorites = Favorite::with('lineUser')->get();

        foreach ($favorites as $favorite) {
            if (! $favorite->lineUser) {
                continue;
            }

            $since = $favorite->last_checked_report_id ?? 0;
            $newReports = VacancyReport::where('venue_id', $favorite->venue_id)
                ->where('id', '>', $since)
                ->get();

            if ($newReports->isEmpty()) {
                continue;
            }

            // 「空きなし」報告では通知しない。空きが出た時だけ通知するのが本サイトの価値。
            $availableReports = $newReports->where('status', 'あり');

            if ($availableReports->isNotEmpty()) {
                $latest = $availableReports->first();
                $favorite->loadMissing('venue');
                LineMessaging::push(
                    $favorite->lineUser->line_user_id,
                    "「{$favorite->venue->name}」で空きありの報告がありました"
                    . ($latest->age_group ? "（{$latest->age_group}）" : '')
                    . '。お早めにご確認ください。'
                );
            }

            // last_checked_report_idは検知カーソル。idは常に厳密単調増加のため、
            // created_at(秒精度)を使った場合に起こりうる同一秒内の複数投稿の取りこぼしが起きない。
            $favorite->update(['last_checked_report_id' => $newReports->max('id')]);
        }

        return self::SUCCESS;
    }
}
