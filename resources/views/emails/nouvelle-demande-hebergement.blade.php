<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Nouvelle demande d'hébergement - Afridays</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      line-height: 1.6;
      color: #333;
    }

    .container {
      max-width: 600px;
      margin: 0 auto;
      padding: 20px;
    }

    .header {
      background: linear-gradient(135deg, #3B82F6, #1E40AF);
      color: white;
      padding: 20px;
      text-align: center;
      border-radius: 8px 8px 0 0;
    }

    .content {
      background: #f8f9fa;
      padding: 30px;
      border-radius: 0 0 8px 8px;
    }

    .section {
      background: white;
      margin: 20px 0;
      padding: 20px;
      border-radius: 6px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .section h3 {
      color: #1E40AF;
      margin-top: 0;
      border-bottom: 2px solid #E5E7EB;
      padding-bottom: 10px;
    }

    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .info-item {
      margin-bottom: 10px;
    }

    .label {
      font-weight: bold;
      color: #374151;
    }

    .value {
      color: #6B7280;
    }

    .services-list {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 10px;
    }

    .service-tag {
      background: #DBEAFE;
      color: #1E40AF;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
    }

    .footer {
      text-align: center;
      margin-top: 30px;
      color: #6B7280;
      font-size: 14px;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="header">
      <h1>📨 Nouvelle demande d'hébergement</h1>
      <p>Afridays - Plateforme de réservation</p>
    </div>

    <div class="content">
      <p><strong>Une nouvelle demande d'ajout d'hébergement a été reçue.</strong></p>

      <div class="section">
        <h3>👤 Informations du propriétaire</h3>
        <div class="info-grid">
          <div class="info-item">
            <span class="label">Nom complet :</span><br>
            <span class="value">{{ $nom_complet }}</span>
          </div>
          <div class="info-item">
            <span class="label">Email :</span><br>
            <span class="value"><a href="mailto:{{ $email }}">{{ $email }}</a></span>
          </div>
          <div class="info-item">
            <span class="label">Téléphone :</span><br>
            <span class="value">{{ $telephone }}</span>
          </div>
        </div>
      </div>

      <div class="section">
        <h3>🏨 Informations de l'établissement</h3>

        <div class="info-item">
          <span class="label">Nom de l'établissement :</span><br>
          <span class="value">{{ $etablissement['nom'] }}</span>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <span class="label">Type :</span><br>
            <span class="value">{{ $etablissement['type'] }}</span>
          </div>
          <div class="info-item">
            <span class="label">Ville :</span><br>
            <span class="value">{{ $etablissement['ville'] }}</span>
          </div>
          @if($etablissement['quartier'])
          <div class="info-item">
            <span class="label">Quartier :</span><br>
            <span class="value">{{ $etablissement['quartier'] }}</span>
          </div>
          @endif
        </div>

        <div class="info-item">
          <span class="label">Adresse complète :</span><br>
          <span class="value">{{ $etablissement['adresse'] }}</span>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <span class="label">Nombre de chambres :</span><br>
            <span class="value">{{ $etablissement['chambres'] }}</span>
          </div>
          <div class="info-item">
            <span class="label">Capacité maximum :</span><br>
            <span class="value">{{ $etablissement['capacite'] }} personnes</span>
          </div>
          <div class="info-item">
            <span class="label">Prix par nuit :</span><br>
            <span class="value">{{ number_format($etablissement['prix'], 0, ',', ' ') }} FCFA</span>
          </div>
        </div>
      </div>

      @if(count($etablissement['services']) > 0)
      <div class="section">
        <h3>🛎️ Services et équipements</h3>
        <div class="services-list">
          @foreach($etablissement['services'] as $service)
          <span class="service-tag">{{ $service }}</span>
          @endforeach
        </div>
      </div>
      @endif

      <div class="section">
        <h3>📝 Description</h3>
        <p style="line-height: 1.6;">{{ $etablissement['description'] }}</p>

        @if($etablissement['message'])
        <div style="margin-top: 20px; padding: 15px; background: #F3F4F6; border-radius: 4px;">
          <span class="label">Message supplémentaire :</span><br>
          <span class="value">{{ $etablissement['message'] }}</span>
        </div>
        @endif
      </div>

      <div class="footer">
        <p>📧 Email reçu le {{ date('d/m/Y à H:i') }}</p>
        <p>Répondez directement à cet email pour contacter le propriétaire.</p>
      </div>
    </div>
  </div>
</body>

</html>