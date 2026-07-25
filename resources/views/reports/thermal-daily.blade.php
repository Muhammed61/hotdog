================================
       KAFE GUN SONU RAPORU
================================
@if($start_date === $end_date)
Tarih: {{ $formatted_start_date }}
@else
Tarih: {{ $formatted_start_date }} - {{ $formatted_end_date }}
@endif
Saat Araligi: {{ $start_time }} - {{ $end_time }}
Rapor Saati: {{ now()->format('H:i') }}
================================

GENEL OZET
--------------------------------
Toplam Siparis : {{ $total_orders }}
Toplam Gelir   : {{ number_format($total_revenue, 2) }}
Toplam Indirim : {{ number_format($total_discount, 2) }}
Brut Tutar     : {{ number_format($total_original, 2) }}

ODEME YONTEMLERI
--------------------------------
Nakit      : {{ number_format($total_cash, 2) }}
Kart       : {{ number_format($total_card, 2) }}
Toplam     : {{ number_format($total_revenue, 2) }}

================================
Rapor Olusturma: {{ now()->format('d.m.Y H:i:s') }}
Kullanici: {{ auth()->user()->name }}
================================