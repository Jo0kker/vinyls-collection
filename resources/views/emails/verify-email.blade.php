<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérifiez votre adresse e-mail</title>
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
            <h2>Vérifiez votre adresse e-mail</h2>
            
            <p>Bonjour {{ $user->name }},</p>
            
            <p>Merci de vous être inscrit sur Vinyls Collection ! Pour commencer à utiliser votre compte, veuillez cliquer sur le bouton ci-dessous pour vérifier votre adresse e-mail.</p>
            
            <p style="text-align: center;">
                <a href="{{ $url }}" class="button">Vérifier mon adresse e-mail</a>
            </p>
            
            <p>Si vous n'avez pas créé de compte sur Vinyls Collection, aucune action supplémentaire n'est requise.</p>
            
            <p>Bienvenue dans la communauté des passionnés de vinyles !</p>
            
            <p>Cordialement,<br>L'équipe {{ config('app.name') }}</p>
            
            <div class="subcopy">
                <p>Si vous avez des difficultés à cliquer sur le bouton "Vérifier mon adresse e-mail", copiez et collez l'URL ci-dessous dans votre navigateur web :</p>
                <p>{{ $url }}</p>
            </div>
        </div>
    </div>
</body>
</html>