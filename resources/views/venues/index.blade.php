@extends('layouts.plain')

@php
    $pageTitle = $area
        ? $area . 'の保育園・幼稚園' . number_format($total) . '園｜' . config('app.name')
        : config('app.name') . ' | 保育園・幼稚園を地図から探す';
    $pageDescription = $area
        ? $area . 'の保育園・幼稚園' . number_format($total) . '園を、地図と一覧から探せます。空き状況の口コミは利用者の投稿です。'
        : '全国' . number_format($total) . '園の保育園・幼稚園を地図から探せます。現在地から近い園を調べたり、空き状況の口コミを確認できます。';
@endphp

@section('title', $pageTitle)
@section('description', $pageDescription)

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'WebSite',
  'name' => config('app.name'),
  'url' => url('/'),
  'description' => '全国の保育園・幼稚園を地図から探せるマップ。空き状況の口コミは利用者の投稿。',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
{{-- 投稿が0件のときは itemListElement が空になる。空のItemListはGoogleに
     無効な項目として扱われるため、1件以上あるときだけ出力する。 --}}
@if ($venues->isNotEmpty())
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'ItemList',
  'itemListElement' => $venues->take(50)->values()->map(function ($venue, $i) {
      return [
          '@type' => 'ListItem',
          'position' => $i + 1,
          'url' => url("/venues/{$venue->id}"),
          'name' => $venue->name,
      ];
  })->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif
@if ($area)
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'BreadcrumbList',
  'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => config('app.name'), 'item' => url('/')],
      ['@type' => 'ListItem', 'position' => 2, 'name' => $area, 'item' => route('venues.area', $areaSlug)],
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif
@endpush

@section('content')
<div class="container my-4">
  <div class="text-center mb-4">
    @if($area)
      <nav aria-label="パンくず" class="small mb-2">
        <a href="{{ route('venues.index') }}">保育園マップ</a>
        <span class="text-muted mx-1">/</span><span class="text-muted">{{ $area }}</span>
      </nav>
      <h1 class="fw-bold h3">{{ $area }}の保育園・幼稚園</h1>
      <p class="text-muted">{{ number_format($total) }}園を掲載しています。空き状況の口コミは利用者の投稿です。</p>
    @else
      <h1 class="fw-bold h3">🧸 保育園マップ</h1>
      <p class="text-muted">全国{{ number_format($total) }}園から、現在地や都道府県で探せます。空きが出たらLINEでお知らせします。</p>
    @endif
  </div>

  <div class="d-flex justify-content-center mb-3">
    <a href="{{ route('venues.create') }}" class="btn btn-outline-secondary btn-sm me-2">➕ 保育園・幼稚園を投稿</a>
    <button id="locateButton" class="btn btn-outline-primary btn-sm">📍 現在地から近い順に探す</button>
  </div>
  <p id="locateMessage" class="text-center text-muted small mb-3"></p>

  {{-- 地図に渡すのはこのページに出ている園だけ。全件を渡すとHTMLが数十MBになる。 --}}
  <div id="map"
       data-venues="{{ $venues->getCollection()->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'area' => $v->area, 'lat' => $v->lat, 'lng' => $v->lng])->toJson() }}"
       data-nearby-url="{{ route('venues.nearby') }}"
       style="height: 360px;" class="rounded shadow-sm border mb-4"></div>

  @if($areaCounts->isNotEmpty())
    <h2 class="h6">都道府県から探す</h2>
    <p class="d-flex flex-wrap gap-2 mb-4">
      @foreach($areaCounts as $row)
        <a href="{{ route('venues.area', $row['slug']) }}"
           class="btn btn-sm {{ $areaSlug === $row['slug'] ? 'btn-primary' : 'btn-outline-secondary' }}">
          {{ $row['area'] }} <span class="text-muted">{{ number_format($row['total']) }}</span>
        </a>
      @endforeach
    </p>
  @endif

  <div class="row" id="venueList">
    @forelse($venues as $venue)
      @php $latestVacancy = $venue->vacancyReports->first(); @endphp
      <div class="col-md-6 col-lg-4 mb-3" data-venue-card data-lat="{{ $venue->lat }}" data-lng="{{ $venue->lng }}">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h2 class="h6 card-title">
              <a href="{{ route('venues.show', $venue) }}" class="text-decoration-none">{{ $venue->name }}</a>
              @if($venue->area_slug)
                <a href="{{ route('venues.area', $venue->area_slug) }}" class="badge bg-secondary float-end text-decoration-none">{{ $venue->area }}</a>
              @else
                <span class="badge bg-secondary float-end">未設定</span>
              @endif
            </h2>
            @if($venue->facility_type)
              <span class="badge bg-light text-dark border mb-1">{{ $venue->facility_type }}</span>
            @endif
            <p class="card-text text-muted small">{{ $venue->description }}</p>
            <small class="d-block">
              @if($latestVacancy)
                @if($latestVacancy->status === 'あり')
                  <span class="badge badge-vacancy-yes">空きあり{{ $latestVacancy->age_group ? '（' . $latestVacancy->age_group . '）' : '' }}</span>
                @elseif($latestVacancy->status === 'なし')
                  <span class="badge badge-vacancy-no">空きなし{{ $latestVacancy->age_group ? '（' . $latestVacancy->age_group . '）' : '' }}</span>
                @else
                  <span class="badge badge-vacancy-check">要問合せ</span>
                @endif
              @else
                <span class="text-muted">空き状況：まだ口コミがありません</span>
              @endif
            </small>
            <small class="text-muted d-block distance-label"></small>
          </div>
        </div>
      </div>
    @empty
      <p class="text-muted">該当する保育園・幼稚園がありません。</p>
    @endforelse
  </div>

  <div class="d-flex justify-content-center my-3">
    {{ $venues->onEachSide(1)->links() }}
  </div>

  <p class="text-muted small">
    園の名称・位置・住所は OpenStreetMap のデータをもとにしています（© OpenStreetMap contributors、ODbL 1.0）。
    空き状況と口コミは利用者の投稿で、当サイトでは内容を確認していません。入園の可否は必ず園または自治体にご確認ください。
  </p>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('map');
    const venues = JSON.parse(mapEl.dataset.venues || '[]');
    const nearbyUrl = mapEl.dataset.nearbyUrl;

    const map = L.map('map').setView([35.6812, 139.7671], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const markers = venues.map(function (v) {
      return L.marker([v.lat, v.lng]).addTo(map)
        .bindPopup('<a href="/venues/' + v.id + '">' + v.name + '</a><br><small>' + (v.area || '') + '</small>');
    });

    if (markers.length) {
      map.fitBounds(L.featureGroup(markers).getBounds().pad(0.15));
    }

    const locateButton = document.getElementById('locateButton');
    const locateMessage = document.getElementById('locateMessage');

    locateButton.addEventListener('click', function () {
      if (!navigator.geolocation) {
        locateMessage.textContent = 'このブラウザは現在地取得に対応していません。';
        return;
      }

      locateMessage.textContent = '現在地を取得しています…';

      navigator.geolocation.getCurrentPosition(function (position) {
        const userLat = position.coords.latitude;
        const userLng = position.coords.longitude;

        map.setView([userLat, userLng], 12);
        L.marker([userLat, userLng], { title: '現在地' })
          .addTo(map)
          .bindPopup('現在地')
          .openPopup();

        // 近い園はサーバーに問い合わせる（ページに全件を持たせない）。
        fetch(nearbyUrl + '?lat=' + userLat + '&lng=' + userLng)
          .then(function (response) { return response.json(); })
          .then(function (data) {
            const list = document.getElementById('venueList');
            if (!data.venues || !data.venues.length) {
              locateMessage.textContent = '現在地の近くに掲載中の園が見つかりませんでした。';
              return;
            }

            list.innerHTML = data.venues.map(function (v) {
              return '<div class="col-md-6 col-lg-4 mb-3"><div class="card h-100 shadow-sm"><div class="card-body">'
                + '<h2 class="h6 card-title"><a class="text-decoration-none" href="' + v.url + '">' + v.name + '</a>'
                + '<span class="badge bg-secondary float-end">' + (v.area || '') + '</span></h2>'
                + (v.facilityType ? '<span class="badge bg-light text-dark border mb-1">' + v.facilityType + '</span>' : '')
                + '<small class="text-muted d-block">現在地から約' + v.distanceKm + 'km</small>'
                + '</div></div></div>';
            }).join('');

            data.venues.forEach(function (v) {
              L.marker([v.lat, v.lng]).addTo(map)
                .bindPopup('<a href="' + v.url + '">' + v.name + '</a>');
            });

            locateMessage.textContent = '現在地から近い' + data.venues.length + '園を表示しました。';
          })
          .catch(function () {
            locateMessage.textContent = '近くの園を取得できませんでした。時間をおいてお試しください。';
          });
      }, function () {
        locateMessage.textContent = '現在地を取得できませんでした。ブラウザの位置情報許可をご確認ください。';
      });
    });
  });
</script>
@endsection
