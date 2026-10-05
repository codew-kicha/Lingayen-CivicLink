{{-- Shared plain styles for dompdf printables (dompdf ignores the Tailwind build). --}}
<style>
    @page { margin: 16mm 15mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #2b2f38; }
    h1 { font-size: 14pt; margin: 0; }
    h2 { font-size: 10.5pt; margin: 6mm 0 2mm; border-bottom: 1px solid #9ca3af; padding-bottom: 1mm; }
    .head { border-bottom: 2px solid #1e2a47; padding-bottom: 3mm; margin-bottom: 4mm; }
    .muted { color: #6b7280; font-size: 8.5pt; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: 7.5pt; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #9ca3af; padding: 1.5mm; }
    td { padding: 2mm 1.5mm; border-bottom: 1px solid #d1d5db; vertical-align: top; }
    .line { border-bottom: 1px solid #6b7280; height: 7mm; }
    .box { display: inline-block; width: 3mm; height: 3mm; border: 1px solid #2b2f38; margin-right: 1.5mm; }
    .sign td { border: none; padding-top: 12mm; width: 50%; }
    .sign span { display: block; border-top: 1px solid #2b2f38; padding-top: 1mm; margin-right: 8mm; }
</style>
