@php
    $type = $type ?? ($weather['weather_type'] ?? 'partly_cloudy');
    $sizeClass = $sizeClass ?? 'w-14 h-14';
@endphp
@if ($type === 'sunny')
    <svg class="{{ $sizeClass }} animate-[spin_20s_linear_infinite]" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="32" cy="32" r="14" fill="url(#sunGrad)" />
        <g stroke="#F59E0B" stroke-width="3" stroke-linecap="round">
            <line x1="32" y1="4" x2="32" y2="10" />
            <line x1="32" y1="54" x2="32" y2="60" />
            <line x1="4" y1="32" x2="10" y2="32" />
            <line x1="54" y1="32" x2="60" y2="32" />
            <line x1="12.2" y1="12.2" x2="16.5" y2="16.5" />
            <line x1="47.5" y1="47.5" x2="51.8" y2="51.8" />
            <line x1="12.2" y1="51.8" x2="16.5" y2="47.5" />
            <line x1="47.5" y1="16.5" x2="51.8" y2="12.2" />
        </g>
        <defs>
            <radialGradient id="sunGrad" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(32 32) rotate(90) scale(14)">
                <stop stop-color="#FCD34D" />
                <stop offset="1" stop-color="#F59E0B" />
            </radialGradient>
        </defs>
    </svg>
@elseif ($type === 'partly_cloudy')
    <svg class="{{ $sizeClass }}" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="38" cy="22" r="11" fill="url(#sunPartly)" />
        <g stroke="#F59E0B" stroke-width="2.5" stroke-linecap="round">
            <line x1="38" y1="6" x2="38" y2="9" />
            <line x1="52" y1="14" x2="50" y2="16" />
            <line x1="54" y1="22" x2="51" y2="22" />
            <line x1="26" y1="14" x2="28" y2="16" />
        </g>
        <path d="M20 48H44C47.866 48 51 44.866 51 41C51 37.134 47.866 34 44 34C43.7 34 43.4 34.02 43.1 34.06C42.06 28.32 37.07 24 31 24C24.37 24 19 29.37 19 36C19 36.35 19.01 36.69 19.05 37.03C15.65 37.56 13 40.48 13 44C13 46.21 14.79 48 17 48H20Z" fill="url(#cloudGrad)" stroke="#CBD5E1" stroke-width="1.5" />
        <defs>
            <linearGradient id="sunPartly" x1="27" y1="11" x2="49" y2="33" gradientUnits="userSpaceOnUse">
                <stop stop-color="#FDE047" />
                <stop offset="1" stop-color="#EA580C" />
            </linearGradient>
            <linearGradient id="cloudGrad" x1="13" y1="24" x2="51" y2="48" gradientUnits="userSpaceOnUse">
                <stop stop-color="#FFFFFF" />
                <stop offset="1" stop-color="#E2E8F0" />
            </linearGradient>
        </defs>
    </svg>
@elseif ($type === 'rainy')
    <svg class="{{ $sizeClass }}" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M19 40H45C48.3 40 51 37.3 51 34C51 30.7 48.3 28 45 28C44.7 28 44.4 28.02 44.1 28.06C43.1 23.32 38.9 20 34 20C28.5 20 24 24.5 24 30C24 30.3 24.01 30.6 24.05 30.9C21.1 31.4 19 33.9 19 36.8C19 38.6 20.4 40 22.2 40H19Z" fill="#CBD5E1" stroke="#94A3B8" stroke-width="1.5" />
        <line x1="24" y1="44" x2="21" y2="52" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" />
        <line x1="33" y1="44" x2="30" y2="52" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" />
        <line x1="42" y1="44" x2="39" y2="52" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" />
    </svg>
@elseif ($type === 'thunderstorm')
    <svg class="{{ $sizeClass }}" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M19 36H45C48.3 36 51 33.3 51 30C51 26.7 48.3 24 45 24C44.7 24 44.4 24.02 44.1 24.06C43.1 19.32 38.9 16 34 16C28.5 16 24 20.5 24 26C24 26.3 24.01 26.6 24.05 26.9C21.1 27.4 19 29.9 19 32.8C19 34.6 20.4 36 22.2 36H19Z" fill="#475569" />
        <polygon points="34,34 26,46 33,46 29,58 41,44 34,44" fill="#FACC15" stroke="#EAB308" stroke-width="1" />
    </svg>
@else
    <svg class="{{ $sizeClass }}" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M18 46H46C50 46 53 43 53 39C53 35.2 50 32 46.2 32C45.9 32 45.5 32.02 45.2 32.06C44.1 26.3 39 22 33 22C26.4 22 21 27.4 21 34C21 34.3 21.01 34.7 21.05 35C17.6 35.6 15 38.5 15 42C15 44.2 16.8 46 19 46H18Z" fill="#E2E8F0" stroke="#94A3B8" stroke-width="1.5" />
    </svg>
@endif
