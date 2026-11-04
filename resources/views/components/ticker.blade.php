{{-- Ticker com o sufixo de bolsa esmaecido: "VALE3.SA" → VALE3<small>.SA</small>. --}}
@props(['value'])
@php
  $t = (string) ($value ?? '—');
  $base = str_contains($t, '.') ? \Illuminate\Support\Str::beforeLast($t, '.') : $t;
  $sufixo = $base !== $t ? '.' . \Illuminate\Support\Str::afterLast($t, '.') : '';
@endphp
{{ $base }}@if ($sufixo)<small>{{ $sufixo }}</small>@endif
