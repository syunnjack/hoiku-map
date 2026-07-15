@extends('layouts.plain')

@section('title', config('app.name') . ' | 空きが出たらLINEで通知が届く保育園・幼稚園マップ')
@section('description', '全国の認可保育園・幼稚園を地図から検索できる投稿型マップです。現在地から近い園をワンタップで見つけられ、空き状況の口コミや写真付き口コミをリアルタイムで確認できます。')

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'WebSite',
  'name' => config('app.name'),
  'url' => url('/'),
  'description' => '全国の認可保育園・幼稚園を地図から検索できる投稿型マップ。空き状況の口コミや写真付き口コミを確認できる。',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
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
@endpush

@section('content')
<div class="container my-4">
  <div class="text-center mb-4">
    <h1 class="fw-bold h3">🧸 保育園マップ</h1>
    <p class="text-muted">現在地から近い園をすぐ見つける・空きが出たらLINEでお知らせ</p>
    <a href="{{ route('venues.create') }}" class="btn btn-hoiku shadow-sm px-4">➕ 保育園・幼稚園を投稿</a>
  </div>

  <div class="d-flex justify-content-center mb-3">
    <button id="locateButton" class="btn btn-outline-primary">📍 現在地から近い順に探す</button>
  </div>
  <p id="locateMessage" class="text-center text-muted small mb-3"></p>

  <div id="map" data-venues="{{ $venues->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'area' => $v->area, 'lat' => $v->lat, 'lng' => $v->lng])->toJson() }}" style="height: 360px;" class="rounded shadow-sm border mb-4"></div>

  <form method="GET" action="{{ route('venues.index') }}" class="row g-2 mb-4">
    <div class="col-md-4">
      <label class="form-label">エリア</label>
      <select name="area" class="form-select">
        <option value="">すべて</option>
        @foreach($areas as $area)
          <option value="{{ $area }}" @selected(request('area') == $area)>{{ $area }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2 align-self-end">
      <button type="submit" class="btn btn-outline-primary w-100">絞り込む</button>
    </div>
  </form>

  <div class="row" id="venueList">
    @forelse($venues as $venue)
      @php $latestVacancy = $venue->vacancyReports->first(); @endphp
      <div class="col-md-6 col-lg-4 mb-3" data-venue-card data-lat="{{ $venue->lat }}" data-lng="{{ $venue->lng }}">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h2 class="h6 card-title">
              <a href="{{ route('venues.show', $venue) }}" class="text-decoration-none">{{ $venue->name }}</a>
              <span class="badge bg-secondary float-end">{{ $venue->area ?? '未設定' }}</span>
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
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('map');
    const venues = JSON.parse(mapEl.dataset.venues || '[]');

    const map = L.map('map').setView([35.6812, 139.7671], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    venues.forEach(function (v) {
      L.marker([v.lat, v.lng]).addTo(map)
        .bindPopup('<a href="/venues/' + v.id + '">' + v.name + '</a><br><small>' + (v.area || '') + '</small>');
    });

    function haversineKm(lat1, lng1, lat2, lng2) {
      const R = 6371;
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLng = (lng2 - lng1) * Math.PI / 180;
      const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
      return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
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

        map.setView([userLat, userLng], 11);
        L.marker([userLat, userLng], { title: '現在地' })
          .addTo(map)
          .bindPopup('現在地')
          .openPopup();

        const cards = Array.from(document.querySelectorAll('[data-venue-card]'));
        cards.forEach(function (card) {
          const lat = parseFloat(card.dataset.lat);
          const lng = parseFloat(card.dataset.lng);
          const distance = haversineKm(userLat, userLng, lat, lng);
          card.dataset.distance = distance;
          const label = card.querySelector('.distance-label');
          if (label) label.textContent = '現在地から約' + distance.toFixed(1) + 'km';
        });

        cards.sort(function (a, b) {
          return parseFloat(a.dataset.distance) - parseFloat(b.dataset.distance);
        });

        const list = document.getElementById('venueList');
        cards.forEach(function (card) { list.appendChild(card); });

        locateMessage.textContent = '現在地から近い順に並び替えました。';
      }, function () {
        locateMessage.textContent = '現在地を取得できませんでした。ブラウザの位置情報許可をご確認ください。';
      });
    });
  });
</script>
@endsection
