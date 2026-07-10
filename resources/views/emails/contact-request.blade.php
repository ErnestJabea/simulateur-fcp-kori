<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de contact – Kori Asset Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f5f1eb; font-family: 'Segoe UI', Arial, sans-serif; color: #3d2b1a; }
        .wrapper { max-width: 620px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }

        /* Header */
        .header { background: linear-gradient(135deg, #5c3a1e 0%, #8b5e3c 100%); padding: 36px 40px; text-align: center; }
        .header .logo-icon { font-size: 48px; margin-bottom: 12px; display: block; }
        .header h1 { color: #f5d68a; font-size: 20px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
        .header p { color: #d4b896; font-size: 13px; margin-top: 4px; }

        /* Badge type */
        .badge-wrap { text-align: center; padding: 24px 40px 0; }
        .badge { display: inline-block; padding: 8px 22px; border-radius: 50px; font-size: 14px; font-weight: 700; letter-spacing: 0.03em; }
        .badge-rdv { background: #d4edda; color: #155724; border: 1.5px solid #c3e6cb; }
        .badge-call { background: #cce5ff; color: #004085; border: 1.5px solid #b8daff; }

        /* Sections */
        .section { padding: 28px 40px; }
        .section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #8b5e3c; border-bottom: 2px solid #f0e8dc; padding-bottom: 8px; margin-bottom: 16px; }
        .info-grid { display: table; width: 100%; border-collapse: collapse; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; font-size: 13px; color: #8b6a50; font-weight: 600; padding: 7px 12px 7px 0; width: 40%; vertical-align: top; }
        .info-value { display: table-cell; font-size: 14px; color: #2d1a0a; font-weight: 500; padding: 7px 0; }
        .info-value.highlight { color: #5c3a1e; font-weight: 700; font-size: 15px; }

        /* Simulation card */
        .sim-card { background: linear-gradient(135deg, #fdf8f0 0%, #f5ede0 100%); border: 1.5px solid #e8d5bc; border-radius: 12px; padding: 20px 24px; margin-top: 4px; }
        .sim-amount { font-size: 26px; font-weight: 800; color: #5c3a1e; }
        .sim-label { font-size: 12px; color: #9b7a5a; margin-top: 2px; }

        /* Footer */
        .footer { background: #faf7f3; border-top: 1px solid #ede5da; padding: 24px 40px; text-align: center; }
        .footer p { font-size: 12px; color: #a08060; line-height: 1.6; }
        .footer strong { color: #5c3a1e; }

        /* Divider */
        .divider { height: 1px; background: #f0e8dc; margin: 0 40px; }

        /* WhatsApp badge */
        .wa-badge { display: inline-flex; align-items: center; gap: 6px; background: #25D366; color: white; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 50px; }
    </style>
</head>
<body>
    <div class="wrapper">

        <!-- En-tête -->
        <div class="header">
            <span class="logo-icon">🪙</span>
            <h1>Kori Asset Management</h1>
            <p>Simulateur FCP – Nouvelle demande de contact</p>
        </div>

        <!-- Badge type de demande -->
        <div class="badge-wrap">
            @if($type === 'appointment')
                <span class="badge badge-rdv">📅 Demande de rendez-vous en ligne</span>
            @else
                <span class="badge badge-call">📞 Demande de rappel téléphonique</span>
            @endif
        </div>

        <!-- Informations du prospect -->
        <div class="section">
            <div class="section-title">Informations du prospect</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Nom complet</span>
                    <span class="info-value highlight">{{ $leadName }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value">
                        <a href="mailto:{{ $leadEmail }}" style="color: #8b5e3c;">{{ $leadEmail }}</a>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Téléphone</span>
                    <span class="info-value">
                        <a href="tel:{{ $leadPhone }}" style="color: #8b5e3c;">{{ $leadPhone }}</a>
                        @if($whatsapp)
                            &nbsp;<span class="wa-badge">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="white"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.5-5.739-1.446L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.965C16.528 1.977 14.07 1.01 11.999 1.01 6.562 1.01 2.138 5.381 2.135 10.81c0 1.679.444 3.315 1.285 4.747l-.982 3.58 3.69-.968z"/></svg>
                                WhatsApp OK
                            </span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Détails de la simulation -->
        <div class="section">
            <div class="section-title">Paramètres de la simulation</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Fonds sélectionné</span>
                    <span class="info-value highlight">{{ $fundName }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Apport initial</span>
                    <span class="info-value">{{ number_format($initial, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Versement mensuel</span>
                    <span class="info-value">{{ number_format($periodic, 0, ',', ' ') }} FCFA / mois</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Durée</span>
                    <span class="info-value">{{ $duration }} ans</span>
                </div>
            </div>

            <!-- Capital final projeté -->
            <div class="sim-card" style="margin-top: 20px;">
                <div class="sim-label">Capital final projeté</div>
                <div class="sim-amount">{{ number_format($finalBalance, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p>
                Ce message a été généré automatiquement par le simulateur FCP de<br>
                <strong>Kori Asset Management</strong> — <a href="https://simulateur-fcp-kori.koriassetmanagement.com" style="color: #8b5e3c;">simulateur-fcp-kori.koriassetmanagement.com</a>
            </p>
            <p style="margin-top: 8px; color: #c0a080; font-size: 11px;">
                Reçu le {{ now()->format('d/m/Y à H:i') }}
            </p>
        </div>

    </div>
</body>
</html>
