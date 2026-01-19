<title>{{ $seo_title ?? config('app.name') }}</title>

<meta name="description" content="{{ $seo_description ?? '' }}">
<meta name="keywords" content="{{ $seo_keywords ?? '' }}">

<meta property="og:title" content="{{ $seo_title ?? '' }}">
<meta property="og:description" content="{{ $seo_description ?? '' }}">
<meta property="og:type" content="article">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $og_image ?? asset('default-og.jpg') }}">
