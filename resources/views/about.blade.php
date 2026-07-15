@extends('layouts.plain')

@section('title', 'このサイトについて | ' . config('app.name'))
@section('description', config('app.name') . 'の運営方針、データの取り扱い、口コミ・LINE通知・見学相談受付の仕組みについて説明しています。')

@section('content')
<div class="container my-4" style="max-width: 720px;">
  <h1 class="h4 fw-bold mb-4">このサイトについて</h1>

  <section class="mb-4">
    <h2 class="h6">サイトの目的</h2>
    <p class="text-muted small">
      「{{ config('app.name') }}」は、認可保育園・幼稚園の場所を地図から探せる投稿型マップです。新しい園は誰でもログイン不要・匿名で投稿でき、
      実際に利用している（利用していた）保護者の方が空き状況の口コミや写真付き口コミを投稿することで情報が更新されていきます。
      自治体のサイトでは更新が遅くなりがちな「今の空き状況」がリアルタイムに近い形で分かることが特徴です。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">空き状況の口コミについて</h2>
    <p class="text-muted small">
      掲載している空き状況（年齢クラス別のあり/なし/要問合せ）は、利用者からの投稿によるものです。運営による事実確認は行っておらず、
      自治体の窓口や園への直接確認と異なる場合があります。入園を検討される際は、必ず自治体窓口または園に直接ご確認ください。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">お子様の安全・プライバシーへの配慮について</h2>
    <p class="text-muted small">
      写真付き口コミでは、園庭や園舎の様子などの写真投稿を想定しており、お子様のお顔が写った写真は投稿しないようお願いしています。
      不適切な写真を発見した場合は速やかに削除などの対応を行います。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">LINE通知について</h2>
    <p class="text-muted small">
      各園のページから「🔔 空きありの報告が投稿されたらLINEで通知」を選ぶと、LINEログインのうえその園を通知対象として登録できます。
      「空きなし」の報告では通知せず、「空きあり」の報告があった時だけお知らせすることで、待機している方に必要な情報だけをお届けします。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">見学・入園相談について</h2>
    <p class="text-muted small">
      各園のページから「📮 LINEで見学・入園相談する」を選ぶと、LINEログインのうえ受け付けます。
      受付完了はLINE公式アカウントからお知らせしますが、当サイトは見学の調整や入園相談の対応そのものは行っておりません。
      お急ぎの場合は、掲載している電話番号へ直接お問い合わせいただくか、各園の公式サイト・自治体窓口もあわせてご確認ください。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">口コミ・投稿について</h2>
    <p class="text-muted small">
      口コミ（写真を含む）や新規園の投稿は、どなたでもログイン不要で行えます。投稿内容は運営による事前確認を行わず即時反映されますが、
      不適切な投稿を発見した場合は内容を精査のうえ削除などの対応を行います。
    </p>
  </section>

  <a href="{{ route('venues.index') }}" class="d-block text-center text-muted mt-4">トップページに戻る</a>
</div>
@endsection
