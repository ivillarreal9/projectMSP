<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Licencias Meraki por vencer</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

        {{-- ── Header ─────────────────────────────────────────────────── --}}
        <tr>
          <td style="background:linear-gradient(135deg,#1e1b4b 0%,#312e81 100%);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
            <p style="margin:0 0 8px;font-size:11px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:#a5b4fc;">OVNICOM ANALYTICS</p>
            <h1 style="margin:0 0 4px;font-size:22px;font-weight:700;color:#ffffff;">Licencias Meraki por Vencer</h1>
            <p style="margin:0;font-size:13px;color:#c7d2fe;">Generado el {{ $generatedAt }}</p>
          </td>
        </tr>

        {{-- ── Resumen ─────────────────────────────────────────────────── --}}
        <tr>
          <td style="background:#ffffff;padding:24px 40px 16px;">
            <p style="margin:0 0 16px;font-size:13px;color:#6b7280;">
              Se encontraron <strong style="color:#111827;">{{ $summary['total'] }} licencia(s)</strong>
              que vencen en los próximos 90 días distribuidas en las siguientes categorías:
            </p>
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td width="33%" style="padding:0 6px 0 0;">
                  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:14px;text-align:center;">
                    <p style="margin:0 0 4px;font-size:28px;font-weight:700;color:#dc2626;">{{ $summary['critical'] }}</p>
                    <p style="margin:0;font-size:11px;font-weight:600;color:#dc2626;text-transform:uppercase;letter-spacing:0.5px;">Críticas</p>
                    <p style="margin:2px 0 0;font-size:11px;color:#ef4444;">Menos de 30 días</p>
                  </div>
                </td>
                <td width="33%" style="padding:0 3px;">
                  <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:14px;text-align:center;">
                    <p style="margin:0 0 4px;font-size:28px;font-weight:700;color:#ea580c;">{{ $summary['warning'] }}</p>
                    <p style="margin:0;font-size:11px;font-weight:600;color:#ea580c;text-transform:uppercase;letter-spacing:0.5px;">Advertencia</p>
                    <p style="margin:2px 0 0;font-size:11px;color:#f97316;">30 – 59 días</p>
                  </div>
                </td>
                <td width="33%" style="padding:0 0 0 6px;">
                  <div style="background:#fefce8;border:1px solid #fde68a;border-radius:8px;padding:14px;text-align:center;">
                    <p style="margin:0 0 4px;font-size:28px;font-weight:700;color:#ca8a04;">{{ $summary['notice'] }}</p>
                    <p style="margin:0;font-size:11px;font-weight:600;color:#ca8a04;text-transform:uppercase;letter-spacing:0.5px;">Aviso</p>
                    <p style="margin:2px 0 0;font-size:11px;color:#eab308;">60 – 90 días</p>
                  </div>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- ── Por organización ────────────────────────────────────────── --}}
        @foreach ($byOrg as $org)
        <tr>
          <td style="background:#ffffff;padding:20px 40px 8px;">
            <p style="margin:0 0 12px;font-size:13px;font-weight:700;color:#1e1b4b;border-left:3px solid #4f46e5;padding-left:10px;">
              {{ $org['orgName'] }}
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:12px;">
              <thead>
                <tr style="background:#f9fafb;">
                  <th style="padding:8px 10px;text-align:left;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Licencia</th>
                  <th style="padding:8px 10px;text-align:left;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Dispositivo</th>
                  <th style="padding:8px 10px;text-align:left;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Serial</th>
                  <th style="padding:8px 10px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Vence</th>
                  <th style="padding:8px 10px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Días</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($org['licenses'] as $i => $lic)
                @php
                  $bg = $i % 2 === 0 ? '#ffffff' : '#f9fafb';
                  [$badgeBg, $badgeColor, $daysBg, $daysColor] = match($lic['urgency']) {
                    'critical' => ['#fef2f2', '#dc2626', '#fef2f2', '#dc2626'],
                    'warning'  => ['#fff7ed', '#ea580c', '#fff7ed', '#ea580c'],
                    default    => ['#fefce8', '#ca8a04', '#fefce8', '#ca8a04'],
                  };
                @endphp
                <tr style="background:{{ $bg }};">
                  <td style="padding:8px 10px;color:#111827;border-bottom:1px solid #f3f4f6;">
                    <span style="display:inline-block;background:{{ $badgeBg }};color:{{ $badgeColor }};font-size:11px;font-weight:600;padding:2px 7px;border-radius:9999px;">
                      {{ $lic['licenseType'] }}
                    </span>
                  </td>
                  <td style="padding:8px 10px;color:#374151;border-bottom:1px solid #f3f4f6;">{{ $lic['deviceName'] }}</td>
                  <td style="padding:8px 10px;color:#6b7280;font-size:11px;font-family:monospace;border-bottom:1px solid #f3f4f6;">{{ $lic['serial'] }}</td>
                  <td style="padding:8px 10px;text-align:center;color:#374151;border-bottom:1px solid #f3f4f6;">{{ $lic['expiresAt'] }}</td>
                  <td style="padding:8px 10px;text-align:center;border-bottom:1px solid #f3f4f6;">
                    <span style="display:inline-block;background:{{ $daysBg }};color:{{ $daysColor }};font-size:12px;font-weight:700;padding:3px 10px;border-radius:9999px;min-width:36px;">
                      {{ $lic['daysLeft'] }}
                    </span>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </td>
        </tr>
        @endforeach

        {{-- ── CTA ─────────────────────────────────────────────────────── --}}
        <tr>
          <td style="background:#ffffff;padding:24px 40px;text-align:center;">
            <a href="{{ config('app.url') }}/admin/meraki/licenses"
               style="display:inline-block;background:#4f46e5;color:#ffffff;font-size:13px;font-weight:600;text-decoration:none;padding:12px 28px;border-radius:8px;">
              Ver licencias en el sistema →
            </a>
          </td>
        </tr>

        {{-- ── Footer ──────────────────────────────────────────────────── --}}
        <tr>
          <td style="background:#f9fafb;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
            <p style="margin:0 0 4px;font-size:11px;color:#9ca3af;">
              Este correo es generado automáticamente por <strong>Ovnicom Analytics</strong>.
            </p>
            <p style="margin:0;font-size:11px;color:#d1d5db;">
              Umbral de alerta: 90 días · Enviado el {{ $generatedAt }}
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
