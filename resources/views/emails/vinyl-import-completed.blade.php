<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import Vinyls Terminé</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .stats { background: #e9ecef; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
        .stat-row { display: flex; justify-content: space-between; margin: 8px 0; }
        .errors { background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎵 Import des Images Vinyls Terminé</h1>
            <p>Rapport d'exécution du {{ \Carbon\Carbon::parse($startTime)->format('d/m/Y à H:i') }}</p>
        </div>

        <div class="stats">
            <h3>📊 Statistiques Globales</h3>
            <div class="stat-row">
                <strong>Jobs dispatchés:</strong>
                <span>{{ number_format($totalDispatched) }}</span>
            </div>
            <div class="stat-row">
                <strong>Images traitées:</strong>
                <span>{{ number_format($totalProcessed) }}</span>
            </div>
            <div class="stat-row">
                <strong>Images uploadées:</strong>
                <span class="{{ $totalUploaded > 0 ? 'success' : 'error' }}">
                    {{ number_format($totalUploaded) }}
                </span>
            </div>
            <div class="stat-row">
                <strong>Taux de réussite:</strong>
                <span class="{{ $totalProcessed > 0 && ($totalUploaded / $totalProcessed) > 0.8 ? 'success' : 'warning' }}">
                    {{ $totalProcessed > 0 ? round(($totalUploaded / $totalProcessed) * 100, 1) : 0 }}%
                </span>
            </div>
            <div class="stat-row">
                <strong>Durée totale:</strong>
                <span>{{ \Carbon\Carbon::parse($startTime)->diffForHumans(\Carbon\Carbon::parse($endTime), true) }}</span>
            </div>
        </div>

        @if($totalUploaded > 0)
            <div class="stats success">
                <h3>✅ Import Réussi</h3>
                <p>
                    {{ number_format($totalUploaded) }} images ont été uploadées avec succès sur S3.
                    @if($totalProcessed - $totalUploaded > 0)
                        {{ $totalProcessed - $totalUploaded }} images n'ont pas pu être traitées (URLs invalides, images par défaut, etc.).
                    @endif
                </p>
            </div>
        @else
            <div class="stats error">
                <h3>❌ Aucune Image Uploadée</h3>
                <p>Aucune image n'a pu être uploadée. Vérifiez les URLs d'images et la connectivité S3.</p>
            </div>
        @endif

        @if(count($errors) > 0)
            <div class="errors">
                <h3>⚠️ Erreurs Rencontrées ({{ count($errors) }})</h3>
                @foreach(array_slice($errors, 0, 10) as $error)
                    <p>• {{ $error }}</p>
                @endforeach
                @if(count($errors) > 10)
                    <p><em>... et {{ count($errors) - 10 }} autres erreurs</em></p>
                @endif
            </div>
        @endif

        <div class="stats">
            <h3>🚀 Actions Suivantes</h3>
            @php
                $remaining = \App\Models\Vinyl::whereNull('pochette')->count();
            @endphp
            @if($remaining > 0)
                <p class="warning">
                    ⚠️ Il reste encore <strong>{{ number_format($remaining) }} vinyls sans pochette</strong>.
                    Vous pouvez relancer l'import pour traiter les images manquantes.
                </p>
            @else
                <p class="success">
                    🎯 <strong>Tous les vinyls ont maintenant une pochette !</strong>
                </p>
            @endif
        </div>

        <hr style="margin: 30px 0;">
        <p style="font-size: 12px; color: #666;">
            Email généré automatiquement par le système d'import Vinyls Collection.<br>
            Commande exécutée le {{ \Carbon\Carbon::parse($endTime)->format('d/m/Y à H:i:s') }}
        </p>
    </div>
</body>
</html>