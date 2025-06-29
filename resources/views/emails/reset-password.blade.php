<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réinitialisation de votre mot de passe</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #718096;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #3B82F6;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            color: white;
            margin: 0;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .content {
            padding: 30px;
        }
        .content h2 {
            color: #2d3748;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .content p {
            margin-bottom: 15px;
            color: #4a5568;
        }
        .button {
            display: inline-block;
            background-color: #3B82F6;
            color: white !important;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #2563eb;
        }
        .footer {
            background-color: #f7fafc;
            padding: 20px;
            font-size: 12px;
            color: #a0aec0;
            border-top: 1px solid #e2e8f0;
        }
        .subcopy {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                </svg>
                Vinyls Collection
            </h1>
        </div>
        
        <div class="content">
            <h2>Réinitialisation de votre mot de passe</h2>
            
            <p>Bonjour {{ $user->name }},</p>
            
            <p>Vous recevez cet email parce que nous avons reçu une demande de réinitialisation de mot de passe pour votre compte Vinyls Collection.</p>
            
            <p style="text-align: center;">
                <a href="{{ $url }}" class="button">Réinitialiser le mot de passe</a>
            </p>
            
            <p>Ce lien de réinitialisation expirera dans {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes.</p>
            
            <p>Si vous n'avez pas demandé de réinitialisation de mot de passe, aucune action supplémentaire n'est requise.</p>
            
            <p>Cordialement,<br>L'équipe {{ config('app.name') }}</p>
            
            <div class="subcopy">
                <p>Si vous avez des difficultés à cliquer sur le bouton "Réinitialiser le mot de passe", copiez et collez l'URL ci-dessous dans votre navigateur web :</p>
                <p>{{ $url }}</p>
            </div>
        </div>
    </div>
</body>
</html>