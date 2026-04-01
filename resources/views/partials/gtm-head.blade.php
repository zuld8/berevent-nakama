@php
    // Prefer explicitly passed $gtmId, otherwise derive from $org if available
    $gtmId = $gtmId ?? data_get($org?->meta_json ?? [], 'analytics.gtm_id');
@endphp
@if (!empty($gtmId))
    <!-- Google Tag Manager -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gtmId }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{{ $gtmId }}');
    </script>
    <!-- End Google Tag Manager -->
@endif

