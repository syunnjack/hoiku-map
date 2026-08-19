<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Support\ContentModeration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VenueController extends Controller
{
    /** 1ページに載せる園の数。全件を1ページに出すとHTMLが数十MBになる。 */
    private const PER_PAGE = 60;

    public function index(Request $request)
    {
        // 以前は全件（3万件超）を1ページに描いていて、トップページのHTMLが
        // 38MBあった。一覧はページ送りにし、地図もそのページの分だけ出す。
        if ($request->filled('area')) {
            $slug = Venue::slugForArea((string) $request->input('area'));

            if ($slug !== null) {
                return redirect()->route('venues.area', ['areaSlug' => $slug], 301);
            }
        }

        $venues = Venue::query()
            ->with(['vacancyReports' => fn ($q) => $q->latest()->limit(1)])
            ->latest()
            ->paginate(self::PER_PAGE);

        return view('venues.index', [
            'venues' => $venues,
            'areaCounts' => $this->areaCounts(),
            'area' => null,
            'areaSlug' => null,
            'total' => Venue::count(),
        ]);
    }

    public function area(string $areaSlug)
    {
        $area = Venue::areaForSlug($areaSlug);

        if ($area === null) {
            throw new NotFoundHttpException;
        }

        $venues = Venue::query()
            ->where('area', $area)
            ->with(['vacancyReports' => fn ($q) => $q->latest()->limit(1)])
            ->orderBy('name')
            ->paginate(self::PER_PAGE);

        if ($venues->total() === 0) {
            throw new NotFoundHttpException;
        }

        return view('venues.index', [
            'venues' => $venues,
            'areaCounts' => $this->areaCounts(),
            'area' => $area,
            'areaSlug' => $areaSlug,
            'total' => $venues->total(),
        ]);
    }

    /** 現在地から近い園を返す。地図と一覧は全件を持たないので、必要なときだけ問い合わせる。 */
    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];
        $latDelta = 0.35;                                     // おおよそ40km
        $lngDelta = 0.35 / max(cos(deg2rad($lat)), 0.01);

        $venues = Venue::query()
            ->whereBetween('lat', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('lng', [$lng - $lngDelta, $lng + $lngDelta])
            ->limit(400)
            ->get(['id', 'name', 'area', 'facility_type', 'lat', 'lng'])
            ->map(function (Venue $venue) use ($lat, $lng) {
                $venue->setAttribute('distance_km', round($venue->distanceKmFrom($lat, $lng), 1));

                return $venue;
            })
            ->sortBy('distance_km')
            ->take(30)
            ->values();

        return response()->json([
            'venues' => $venues->map(fn (Venue $venue) => [
                'id' => $venue->id,
                'name' => $venue->name,
                'area' => $venue->area,
                'facilityType' => $venue->facility_type,
                'lat' => (float) $venue->lat,
                'lng' => (float) $venue->lng,
                'distanceKm' => $venue->getAttribute('distance_km'),
                'url' => route('venues.show', $venue),
            ]),
        ]);
    }

    /** 都道府県ごとの掲載件数（多い順）。 */
    private function areaCounts()
    {
        return Venue::query()
            ->selectRaw('area, COUNT(*) as total')
            ->whereNotNull('area')
            ->groupBy('area')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'area' => $row->area,
                'slug' => Venue::slugForArea($row->area),
                'total' => (int) $row->total,
            ])
            ->filter(fn (array $row) => $row['slug'] !== null)
            ->values();
    }

    public function create()
    {
        return view('venues.create');
    }

    public function store(Request $request)
    {
        if (! empty($request->input('website'))) {
            return redirect()->route('venues.thanks');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'facility_type' => 'nullable|string|max:20',
            'area' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if (ContentModeration::containsNgWord($validated['name'] . ' ' . ($validated['description'] ?? ''))) {
            return back()->withErrors(['name' => '投稿内容に使用できない文字列が含まれています。'])->withInput();
        }

        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("venue-create:{$ipHash}", 30)) {
            return back()->withErrors(['name' => '投稿間隔が短すぎます。しばらく待ってから再度お試しください。'])->withInput();
        }

        Venue::create($validated);

        return redirect()->route('venues.thanks');
    }

    public function show(Venue $venue)
    {
        $venue->load(['reviews' => fn ($q) => $q->latest()]);
        $venue->load(['vacancyReports' => fn ($q) => $q->latest()]);

        $isWatching = session('line_user_local_id')
            ? $venue->favorites()->where('line_user_id', session('line_user_local_id'))->exists()
            : false;

        $hasRequestedDocument = session('line_user_local_id')
            ? $venue->documentRequests()->where('line_user_id', session('line_user_local_id'))->exists()
            : false;

        return view('venues.show', compact('venue', 'isWatching', 'hasRequestedDocument'));
    }

    public function like(Request $request, Venue $venue)
    {
        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("like:{$venue->id}:{$ipHash}", 60)) {
            return response()->json(['error' => 'いいね！は少し時間を空けてから再度お試しください。'], 429);
        }

        $venue->increment('likes_count');
        $venue->refresh();

        return response()->json(['likes_count' => $venue->likes_count]);
    }

    public function sitemap()
    {
        $venues = Venue::select('id', 'updated_at')->get();
        $areaSlugs = Venue::query()
            ->whereNotNull('area')
            ->distinct()
            ->pluck('area')
            ->map(fn (string $area) => Venue::slugForArea($area))
            ->filter()
            ->values();

        $xml = view('sitemap', compact('venues', 'areaSlugs'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
